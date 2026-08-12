<?php

declare(strict_types=1);

namespace Tests\Support;

use Error;
use JTL\DB\DbInterface;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\SchemaOwnershipGuard;
use RuntimeException;
use stdClass;

/**
 * Zustandsbehaftete Testdatenbank mit echten Commit-/Rollback-Grenzen.
 *
 * Sie ersetzt keinen SQL-Parser. Sie bildet nur die für diese Repository-Tests
 * benötigten Operationen ab und protokolliert SQL und Bindings vollständig.
 * Dadurch prüfen die Tests die Transaktionswirkung der echten Repositories,
 * ohne sie an PDO statt an JTLs DbInterface zu koppeln.
 */
final class TransactionalDatabaseFake implements DbInterface
{
    public string $currentSchema = 'task3';
    public bool $reverseMetadataRows = false;
    public ?string $duplicateMetadataRows = null;

    /** @var array<string, list<stdClass>> Seitenweise Rückgaben der fünf JTL-Scannerquellen. */
    public array $scannerRows = [];

    /** @var array<string, string> */
    private array $markers = [];

    /** @var array<string, array{engine: string, collation: string, columns: list<array<string, mixed>>, indexes: list<array<string, mixed>>, foreign_keys: list<array<string, mixed>>}> */
    private array $schemas = [];

    /** @var array<string, array{id: int, label: string, status: string, position: string, theme: string, local_path: string}> */
    private array $assets = [];

    /** @var array<string, array<string, mixed>> */
    private array $usages = [];

    /** @var array<string, true> Technische Deduplizierung des laufenden Scans. */
    private array $scanUsages = [];

    private int $nextAssetId = 1;

    /** @var array<string, string> */
    private array $philosophies = [];

    /** @var null|array{assets: array<string, array{id: int, label: string, status: string, position: string, theme: string, local_path: string}>, usages: array<string, array<string, mixed>>, philosophies: array<string, string>} */
    private ?array $snapshot = null;

    /** @var list<array{sql: string, params: array<string, mixed>}> */
    public array $statements = [];

    public int $begins = 0;
    public int $commits = 0;
    public int $rollbacks = 0;
    public ?string $failOnAssetKey = null;
    public bool $failWithError = false;
    public bool $failRollback = false;
    public bool $returnFalseOnRollback = false;
    public int $forUpdateSelections = 0;
    public bool $lockAvailable = true;
    public mixed $lockAcquireMetadata = '1';
    public mixed $lockReleaseMetadata = '1';
    public int $lockRequests = 0;
    public int $lockReleases = 0;
    public ?int $failCreateNumber = null;
    public ?string $alterFingerprintBeforeCleanup = null;
    public ?string $alterEngineBeforeCleanup = null;
    /** @var null|array{string, string, int} */
    public ?array $alterIndexBeforeCleanup = null;
    /** @var null|array{string, string} */
    public ?array $alterForeignKeySchemaBeforeCleanup = null;
    public ?string $foreignTableBeforeCreate = null;
    /** @var list<string> */
    public array $droppedTables = [];
    private int $createCount = 0;

    public function setMarker(string $table, string $marker): void
    {
        $this->markers[$table] = $marker;
        $this->schemas[$table] = $this->defaultSchema($table);
    }

    public function setColumnType(string $table, string $column, string $type): void
    {
        $this->setColumnValue($table, $column, 'type', $type);
    }

    public function setColumnDefault(string $table, string $column, mixed $default): void
    {
        $this->setColumnValue($table, $column, 'default', $default);
    }

    public function setColumnExtra(string $table, string $column, string $extra): void
    {
        $this->setColumnValue($table, $column, 'extra', $extra);
    }

    public function setEngine(string $table, string $engine): void
    {
        $this->schemas[$table]['engine'] = $engine;
    }

    public function setReferencedSchema(string $table, string $schema): void
    {
        foreach ($this->schemas[$table]['foreign_keys'] as &$foreignKey) {
            $foreignKey['referenced_schema'] = $schema;
        }
        unset($foreignKey);
    }

    /** @param array<string, string> $statuses */
    public function seedAssets(array $statuses): void
    {
        foreach ($statuses as $assetKey => $status) {
            $canonicalKey = $this->canonicalAssetKey($assetKey);
            $this->assets[$canonicalKey] = [
                'id' => $this->nextAssetId++,
                'label' => $assetKey,
                'status' => $status,
                'position' => 'bottom-right',
                'theme' => 'auto',
                'local_path' => '/media/' . $assetKey . '.jpg',
            ];
        }
    }

    public function seedScanAsset(string $assetKey, string $localPath, string $status): void
    {
        $this->assets[$assetKey] = [
            'id' => $this->nextAssetId++,
            'label' => $assetKey,
            'status' => $status,
            'position' => 'bottom-right',
            'theme' => 'auto',
            'local_path' => $localPath,
        ];
    }

    public function seedScanUsage(
        string $assetKey,
        string $localPath,
        string $sourceReference,
        string $sourceType = 'product',
    ): void {
        if (!isset($this->assets[$assetKey])) {
            $this->seedScanAsset($assetKey, $localPath, 'unreviewed');
        }
        $assetId = $this->assets[$assetKey]['id'];
        $key = implode('|', [(string) $assetId, $sourceType, hash('sha256', $sourceReference)]);
        $this->usages[$key] = [
            'asset_id' => $assetId,
            'source_type' => $sourceType,
            'source_reference' => $sourceReference,
            'source_reference_hash' => hash('sha256', $sourceReference),
            'context' => null,
            'is_present' => 1,
        ];
    }

    public function statusForAsset(string $assetKey): ?string
    {
        return $this->assets[$assetKey]['status'] ?? null;
    }

    public function localPathForAsset(string $assetKey): ?string
    {
        return $this->assets[$assetKey]['local_path'] ?? null;
    }

    public function usageIsPresent(string $sourceReference): bool
    {
        foreach ($this->usages as $usage) {
            if (($usage['source_reference'] ?? null) === $sourceReference) {
                return ($usage['is_present'] ?? null) === 1;
            }
        }

        return false;
    }

    public function hasUsage(string $sourceReference): bool
    {
        foreach ($this->usages as $usage) {
            if (($usage['source_reference'] ?? null) === $sourceReference) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, string> */
    public function assetStatuses(): array
    {
        $statuses = [];
        foreach ($this->assets as $asset) {
            $statuses[$asset['label']] = $asset['status'];
        }

        return $statuses;
    }

    public function usageCount(): int
    {
        return count($this->usages);
    }

    public function assetCount(): int
    {
        return count($this->assets);
    }

    public function philosophyCount(): int
    {
        return count($this->philosophies);
    }

    /** @return array<string, string> */
    public function philosophies(): array
    {
        return $this->philosophies;
    }

    /** @return list<string> */
    public function existingTables(): array
    {
        return array_keys($this->markers);
    }

    public function getSingleObject(string $stmt, array $params = []): ?stdClass
    {
        $this->statements[] = ['sql' => $stmt, 'params' => $params];
        if (str_contains($stmt, 'GET_LOCK')) {
            ++$this->lockRequests;

            return (object) ['acquired' => $this->lockAvailable ? $this->lockAcquireMetadata : '0'];
        }
        if (str_contains($stmt, 'RELEASE_LOCK')) {
            ++$this->lockReleases;

            return (object) ['released' => $this->lockReleaseMetadata];
        }
        if (str_contains($stmt, 'FOR UPDATE')) {
            ++$this->forUpdateSelections;
            $assetKey = $params['asset_key'] ?? null;

            return is_string($assetKey) && isset($this->assets[$assetKey]) ? (object) ['id' => 1] : null;
        }
        if (str_contains($stmt, 'FROM `xplugin_mgd_ai_asset`') && isset($params['asset_key'])) {
            $assetKey = $params['asset_key'];
            if (is_string($assetKey) && isset($this->assets[$assetKey])) {
                return (object) ['id' => $this->assets[$assetKey]['id']];
            }

            return null;
        }
        $table = $params['table_name'] ?? null;
        if (!is_string($table) || !array_key_exists($table, $this->markers)) {
            return null;
        }

        return (object) [
            'current_schema' => $this->currentSchema,
            'ownership_marker' => $this->markers[$table],
            'table_engine' => $this->schemas[$table]['engine'],
            'table_collation' => $this->schemas[$table]['collation'],
        ];
    }

    /** @return stdClass[] */
    public function getObjects(string $stmt, array $params = []): array
    {
        $this->statements[] = ['sql' => $stmt, 'params' => $params];
        foreach ($this->scannerRows as $table => $rows) {
            if (str_contains($stmt, 'FROM `' . $table . '`')) {
                $offset = $params['offset'] ?? 0;
                $limit = $params['limit'] ?? 100;

                return is_int($offset) && is_int($limit) ? array_slice($rows, $offset, $limit) : [];
            }
        }
        $table = $params['table_name'] ?? null;
        if (!is_string($table) || !isset($this->schemas[$table])) {
            return [];
        }
        $kind = match (true) {
            str_contains($stmt, 'INFORMATION_SCHEMA`.`COLUMNS') => 'columns',
            str_contains($stmt, 'INFORMATION_SCHEMA`.`STATISTICS') => 'indexes',
            str_contains($stmt, 'INFORMATION_SCHEMA`.`KEY_COLUMN_USAGE') => 'foreign_keys',
            default => null,
        };
        if ($kind === null) {
            return [];
        }

        return array_map(static fn(array $row): stdClass => (object) $row, $this->metadataRows($table, $kind));
    }

    public function getAffectedRows(string $stmt, array $params = []): int
    {
        $this->statements[] = ['sql' => $stmt, 'params' => $params];

        if (str_starts_with(ltrim($stmt), 'CREATE TEMPORARY TABLE')) {
            $this->scanUsages = [];

            return 0;
        }
        if (str_starts_with(ltrim($stmt), 'DROP TEMPORARY TABLE')) {
            $this->scanUsages = [];

            return 0;
        }

        if (str_starts_with(ltrim($stmt), 'CREATE TABLE')) {
            ++$this->createCount;
            if ($this->foreignTableBeforeCreate !== null
                && str_contains($stmt, '`' . $this->foreignTableBeforeCreate . '`')) {
                $this->markers[$this->foreignTableBeforeCreate] = 'fremder-marker';
                $this->schemas[$this->foreignTableBeforeCreate] = $this->defaultSchema($this->foreignTableBeforeCreate);
            }
            if ($this->createCount === $this->failCreateNumber) {
                if ($this->alterFingerprintBeforeCleanup !== null) {
                    $this->setColumnType($this->alterFingerprintBeforeCleanup, 'asset_key', 'varchar(64)');
                }
                if ($this->alterEngineBeforeCleanup !== null) {
                    $this->setEngine($this->alterEngineBeforeCleanup, 'MyISAM');
                }
                if ($this->alterIndexBeforeCleanup !== null) {
                    [$table, $index, $subPart] = $this->alterIndexBeforeCleanup;
                    foreach ($this->schemas[$table]['indexes'] as &$row) {
                        if (($row['name'] ?? null) === $index) {
                            $row['sub_part'] = $subPart;
                        }
                    }
                    unset($row);
                }
                if ($this->alterForeignKeySchemaBeforeCleanup !== null) {
                    [$table, $schema] = $this->alterForeignKeySchemaBeforeCleanup;
                    $this->setReferencedSchema($table, $schema);
                }
                throw new RuntimeException(sprintf('Erzwungener CREATE-Fehler #%d.', $this->createCount));
            }
            if (preg_match('/CREATE TABLE `([^`]+)`/', $stmt, $treffer) === 1
                && preg_match("/COMMENT='([^']+)'/", $stmt, $marker) === 1) {
                $table = $treffer[1];
                if (isset($this->markers[$table])) {
                    throw new RuntimeException('Tabelle erschien zwischen Preflight und CREATE.');
                }
                $this->markers[$table] = $marker[1];
                $this->schemas[$table] = $this->defaultSchema($table);
            }

            return 0;
        }

        if (str_starts_with(ltrim($stmt), 'DROP TABLE')) {
            if (preg_match('/DROP TABLE `([^`]+)`/', $stmt, $treffer) !== 1) {
                throw new RuntimeException('DROP ohne festen Tabellennamen.');
            }
            $table = $treffer[1];
            unset($this->markers[$table], $this->schemas[$table]);
            $this->droppedTables[] = $table;

            return 0;
        }

        if (str_contains($stmt, 'UPDATE `xplugin_mgd_ai_asset`')) {
            $assetKey = $params['asset_key'] ?? null;
            if (!is_string($assetKey)) {
                throw new RuntimeException('Asset-Schlüssel fehlt im Binding.');
            }
            if ($this->failOnAssetKey !== null && $assetKey === $this->canonicalAssetKey($this->failOnAssetKey)) {
                if ($this->failWithError) {
                    throw new Error('Erzwungener Error beim dritten Asset.');
                }
                throw new RuntimeException('Erzwungener Fehler beim dritten Asset.');
            }
            if (!isset($this->assets[$assetKey])) {
                return 0;
            }

            $this->assets[$assetKey]['status'] = $this->stringParameter($params, 'status', 'unreviewed');
            $this->assets[$assetKey]['position'] = $this->stringParameter($params, 'position', 'bottom-right');
            $this->assets[$assetKey]['theme'] = $this->stringParameter($params, 'theme', 'auto');

            return 1;
        }

        if (str_contains($stmt, 'INSERT INTO `xplugin_mgd_ai_asset`')) {
            $assetKey = $this->stringParameter($params, 'asset_key');
            if (isset($this->assets[$assetKey]) && !array_key_exists('status', $params)) {
                $this->assets[$assetKey]['local_path'] = $this->stringParameter($params, 'local_path');

                return 0;
            }
            $this->assets[$assetKey] = [
                'id' => $this->assets[$assetKey]['id'] ?? $this->nextAssetId++,
                'label' => $assetKey,
                'status' => $this->stringParameter($params, 'status', 'unreviewed'),
                'position' => $this->stringParameter($params, 'position', 'bottom-right'),
                'theme' => $this->stringParameter($params, 'theme', 'auto'),
                'local_path' => $this->stringParameter($params, 'local_path'),
            ];

            return 1;
        }

        if (str_contains($stmt, 'INSERT IGNORE INTO `tmp_mgd_ai_scan_usage`')) {
            $assetId = $params['asset_id'] ?? null;
            if (!is_int($assetId)) {
                throw new RuntimeException('Technische Asset-ID fehlt im Scan-Binding.');
            }
            $key = implode('|', [
                (string) $assetId,
                $this->stringParameter($params, 'source_type'),
                $this->stringParameter($params, 'source_reference_hash'),
            ]);
            if (isset($this->scanUsages[$key])) {
                return 0;
            }
            $this->scanUsages[$key] = true;

            return 1;
        }

        if (str_contains($stmt, 'INSERT INTO `xplugin_mgd_ai_usage`')) {
            $assetId = $params['asset_id'] ?? null;
            if (!is_int($assetId)) {
                throw new RuntimeException('Technische Asset-ID fehlt im Binding.');
            }
            $key = implode('|', [
                (string) $assetId,
                $this->stringParameter($params, 'source_type'),
                $this->stringParameter($params, 'source_reference_hash'),
            ]);
            $this->usages[$key] = $params;

            return 1;
        }

        if (str_contains($stmt, 'UPDATE `xplugin_mgd_ai_usage` AS `usage`')) {
            $affected = 0;
            foreach ($this->usages as $key => &$usage) {
                if (!isset($this->scanUsages[$key])
                    && ($usage['is_present'] ?? null) === 1
                    && in_array(
                        $usage['source_type'] ?? null,
                        ['product', 'category', 'manufacturer', 'banner', 'opc'],
                        true,
                    )
                ) {
                    $usage['is_present'] = 0;
                    ++$affected;
                }
            }
            unset($usage);

            return $affected;
        }

        if (str_contains($stmt, 'INSERT INTO `xplugin_mgd_ai_philosophy`')) {
            $language = $this->stringParameter($params, 'language');
            $this->philosophies[$language] = $this->stringParameter($params, 'content');

            return 1;
        }

        return 1;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function stringParameter(array $params, string $name, string $default = ''): string
    {
        $value = $params[$name] ?? $default;
        if (!is_string($value)) {
            throw new RuntimeException(sprintf('String-Binding %s besitzt einen falschen Typ.', $name));
        }

        return $value;
    }

    public function beginTransaction(): bool
    {
        ++$this->begins;
        $this->snapshot = [
            'assets' => $this->assets,
            'usages' => $this->usages,
            'philosophies' => $this->philosophies,
        ];

        return true;
    }

    public function commit(): bool
    {
        ++$this->commits;
        $this->snapshot = null;

        return true;
    }

    public function rollback(): bool
    {
        ++$this->rollbacks;
        if ($this->snapshot !== null) {
            $this->assets = $this->snapshot['assets'];
            $this->usages = $this->snapshot['usages'];
            $this->philosophies = $this->snapshot['philosophies'];
            $this->snapshot = null;
        }
        if ($this->failRollback) {
            throw new RuntimeException('Erzwungener Fehler beim Rollback.');
        }
        if ($this->returnFalseOnRollback) {
            return false;
        }

        return true;
    }

    private function canonicalAssetKey(string $key): string
    {
        return preg_match('/^[a-f0-9]{64}$/i', $key) === 1 ? strtolower($key) : hash('sha256', $key);
    }

    private function setColumnValue(string $table, string $column, string $field, mixed $value): void
    {
        foreach ($this->schemas[$table]['columns'] as $index => $row) {
            if (($row['name'] ?? null) === $column) {
                $this->schemas[$table]['columns'][$index][$field] = $value;
            }
        }
    }

    /**
     * @param 'columns'|'indexes'|'foreign_keys' $kind
     * @return list<array<string, mixed>>
     */
    private function metadataRows(string $table, string $kind): array
    {
        $rows = match ($kind) {
            'columns' => $this->schemas[$table]['columns'],
            'indexes' => $this->schemas[$table]['indexes'],
            'foreign_keys' => $this->schemas[$table]['foreign_keys'],
        };
        if ($this->duplicateMetadataRows === $kind && $rows !== []) {
            $rows[] = $rows[0];
        }
        if ($this->reverseMetadataRows) {
            $rows = array_reverse($rows);
        }

        return $rows;
    }

    /** @return array{engine: string, collation: string, columns: list<array<string, mixed>>, indexes: list<array<string, mixed>>, foreign_keys: list<array<string, mixed>>} */
    private function defaultSchema(string $table): array
    {
        $columns = match ($table) {
            'xplugin_mgd_ai_asset' => [
                ['id', 'bigint(20) unsigned', 'NO', null, null, 'auto_increment'],
                ['asset_key', 'char(64)', 'NO', null, 'ascii_bin', ''],
                ['local_path', 'varchar(1024)', 'NO', null, 'utf8mb4_unicode_ci', ''],
                ['status', "enum('unreviewed','none','generated','partially-generated','modified','deepfake')", 'NO', 'unreviewed', 'utf8mb4_unicode_ci', ''],
                ['position', "enum('top-left','top-right','bottom-left','bottom-right')", 'NO', 'bottom-right', 'utf8mb4_unicode_ci', ''],
                ['theme', "enum('auto','light','dark')", 'NO', 'auto', 'utf8mb4_unicode_ci', ''],
                ['created_at', 'timestamp', 'NO', 'current_timestamp()', null, ''],
                ['updated_at', 'timestamp', 'NO', 'current_timestamp()', null, 'on update current_timestamp()'],
            ],
            'xplugin_mgd_ai_usage' => [
                ['id', 'bigint(20) unsigned', 'NO', null, null, 'auto_increment'],
                ['asset_id', 'bigint(20) unsigned', 'NO', null, null, ''],
                ['source_type', "enum('product','category','manufacturer','banner','opc','custom-local-manual','unknown')", 'NO', 'unknown', 'utf8mb4_unicode_ci', ''],
                ['source_reference', 'varchar(255)', 'NO', null, 'utf8mb4_bin', ''],
                ['source_reference_hash', 'char(64)', 'NO', null, 'ascii_bin', ''],
                ['context', 'varchar(500)', 'YES', null, 'utf8mb4_unicode_ci', ''],
                ['last_seen_at', 'timestamp', 'NO', 'current_timestamp()', null, ''],
                ['is_present', 'tinyint(1) unsigned', 'NO', '1', null, ''],
            ],
            'xplugin_mgd_ai_philosophy' => [
                ['id', 'bigint(20) unsigned', 'NO', null, null, 'auto_increment'],
                ['language', 'varchar(12)', 'NO', null, 'ascii_bin', ''],
                ['content', 'text', 'NO', null, 'utf8mb4_unicode_ci', ''],
                ['created_at', 'timestamp', 'NO', 'current_timestamp()', null, ''],
                ['updated_at', 'timestamp', 'NO', 'current_timestamp()', null, 'on update current_timestamp()'],
            ],
            default => throw new RuntimeException('Unbekanntes Testschema.'),
        };
        $columnRows = [];
        foreach ($columns as $index => [$name, $type, $nullable, $default, $collation, $extra]) {
            $columnRows[] = compact('name', 'type', 'nullable', 'default', 'collation', 'extra')
                + ['ordinal' => (string) ($index + 1)];
        }

        if ($table === 'xplugin_mgd_ai_asset') {
            $indexes = [
                ['PRIMARY', '0', '1', 'id'],
                ['uq_mgd_ai_asset_key', '0', '1', 'asset_key'],
                ['idx_mgd_ai_asset_status', '1', '1', 'status'],
            ];
        } elseif ($table === 'xplugin_mgd_ai_usage') {
            $indexes = [
                ['PRIMARY', '0', '1', 'id'],
                ['uq_mgd_ai_usage_source', '0', '1', 'asset_id'],
                ['uq_mgd_ai_usage_source', '0', '2', 'source_type'],
                ['uq_mgd_ai_usage_source', '0', '3', 'source_reference_hash'],
                ['idx_mgd_ai_usage_present_seen', '1', '1', 'is_present'],
                ['idx_mgd_ai_usage_present_seen', '1', '2', 'last_seen_at'],
            ];
        } else {
            $indexes = [
                ['PRIMARY', '0', '1', 'id'],
                ['uq_mgd_ai_philosophy_language', '0', '1', 'language'],
            ];
        }
        $indexRows = [];
        foreach ($indexes as $row) {
            $indexRows[] = [
                'name' => $row[0], 'non_unique' => $row[1], 'sequence' => $row[2],
                'column' => $row[3], 'sub_part' => null, 'collation' => 'A', 'type' => 'BTREE',
            ];
        }
        $foreignKeys = $table === 'xplugin_mgd_ai_usage' ? [[
            'name' => 'fk_mgd_ai_usage_asset', 'column' => 'asset_id',
            'referenced_schema' => $this->currentSchema,
            'referenced_table' => 'xplugin_mgd_ai_asset', 'referenced_column' => 'id',
            'sequence' => '1', 'update_rule' => 'RESTRICT', 'delete_rule' => 'CASCADE',
        ]] : [];

        return [
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
            'columns' => $columnRows,
            'indexes' => $indexRows,
            'foreign_keys' => $foreignKeys,
        ];
    }
}
