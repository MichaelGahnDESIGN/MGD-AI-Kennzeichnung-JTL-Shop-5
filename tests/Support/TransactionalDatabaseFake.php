<?php

declare(strict_types=1);

namespace Tests\Support;

use Error;
use JTL\DB\DbInterface;
use PDO;
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
    private readonly TransactionStatePdo $pdo;
    public string $currentSchema = 'task3';
    public bool $reverseMetadataRows = false;
    public ?string $duplicateMetadataRows = null;

    /** @var array<string, list<stdClass>> Seitenweise Rückgaben der fünf JTL-Scannerquellen. */
    public array $scannerRows = [];
    public int $scannerPayloadsSuppressed = 0;
    /** @var list<stdClass> */
    public array $lastScannerResult = [];

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
    private int $nextUsageId = 1;

    /** @var array<string, string> */
    private array $philosophies = [];

    /** @var array<string, string> Persistente Token-Hashes mit UTC-Ablaufzeit. */
    private array $confirmationClaims = [];
    private bool $confirmationClaimsFail = false;
    private bool $confirmationClaimPurgeFails = false;
    private ?string $confirmationDatabaseNow = null;

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
    public bool $failCommit = false;
    public bool $returnFalseOnCommit = false;
    public bool $failTemporaryDropOnce = false;
    public int $temporaryTableDrops = 0;
    private bool $temporaryScanTableExists = false;
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

    public function __construct()
    {
        $this->pdo = new TransactionStatePdo();
    }

    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    /** Modelliert eine vom aufrufenden JTL-Code bereits geöffnete Transaktion. */
    public function beginOuterTransactionForTest(): void
    {
        $this->pdo->transactionActive = true;
    }

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
            'id' => $this->nextUsageId++,
            'asset_id' => $assetId,
            'source_type' => $sourceType,
            'source_reference' => $sourceReference,
            'source_reference_hash' => hash('sha256', $sourceReference),
            'context' => null,
            'is_present' => 1,
        ];
    }

    /** Erzeugt eine explizit als veraltet markierte Plugin-Fundstelle für Bereinigungstests. */
    public function seedStaleUsage(string $sourceReference = 'veraltet'): int
    {
        $this->seedScanUsage('cleanup-asset', '/media/cleanup.jpg', $sourceReference);
        foreach ($this->usages as &$usage) {
            if (($usage['source_reference'] ?? null) !== $sourceReference) {
                continue;
            }
            $usage['is_present'] = 0;
            $id = $usage['id'] ?? 0;
            unset($usage);

            return is_int($id) ? $id : 0;
        }
        unset($usage);

        return 0;
    }

    public function statusForAsset(string $assetKey): ?string
    {
        return $this->assets[$assetKey]['status'] ?? null;
    }

    public function localPathForAsset(string $assetKey): ?string
    {
        return $this->assets[$assetKey]['local_path'] ?? null;
    }

    /** @return array{status: string, position: string, theme: string}|null */
    public function presentationForAsset(string $assetKey): ?array
    {
        if (!isset($this->assets[$assetKey])) {
            return null;
        }

        return [
            'status' => $this->assets[$assetKey]['status'],
            'position' => $this->assets[$assetKey]['position'],
            'theme' => $this->assets[$assetKey]['theme'],
        ];
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

    /** Erzwingt ausschließlich für Sicherheits-Claim-Tests einen DB-Ausfall. */
    public function failConfirmationClaimsForTest(): void
    {
        $this->confirmationClaimsFail = true;
    }

    public function failConfirmationClaimPurgeForTest(): void
    {
        $this->confirmationClaimPurgeFails = true;
    }

    public function seedConfirmationClaimForTest(string $tokenHash, string $expiresAt): void
    {
        $this->confirmationClaims[$tokenHash] = $expiresAt;
    }

    public function hasConfirmationClaimForTest(string $tokenHash): bool
    {
        return isset($this->confirmationClaims[$tokenHash]);
    }

    public function confirmationClaimCountForTest(): int
    {
        return count($this->confirmationClaims);
    }

    /** Setzt ausschließlich im Test die autoritative UTC-Uhr der Datenbank. */
    public function setConfirmationDatabaseNowForTest(string $databaseNow): void
    {
        $this->confirmationDatabaseNow = $databaseNow;
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
            $id = $params['id'] ?? null;
            if (is_int($id)) {
                if (str_contains($stmt, 'xplugin_mgd_ai_usage')) {
                    foreach ($this->usages as $usage) {
                        if (($usage['id'] ?? null) === $id && ($usage['is_present'] ?? null) === 0) {
                            return (object) ['id' => $id];
                        }
                    }

                    return null;
                }
                foreach ($this->assets as $asset) {
                    if ($asset['id'] === $id) {
                        return (object) ['id' => $id];
                    }
                }

                return null;
            }
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
        if (str_contains($stmt, 'FROM `xplugin_mgd_ai_philosophy`') && isset($params['language'])) {
            $language = $params['language'];
            if (is_string($language) && isset($this->philosophies[$language])) {
                return (object) ['content' => $this->philosophies[$language]];
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
        if (str_contains($stmt, 'INNER JOIN `xplugin_mgd_ai_usage`')) {
            $sichtbar = ['generated' => true, 'partially-generated' => true, 'modified' => true, 'deepfake' => true];
            $ergebnis = [];
            foreach ($this->assets as $asset) {
                if (!isset($sichtbar[$asset['status']])) {
                    continue;
                }
                $quelle = null;
                foreach ($this->usages as $usage) {
                    if ($usage['asset_id'] === $asset['id'] && $usage['is_present'] === 1) {
                        $kandidat = $usage['source_type'];
                        $quelle = is_string($kandidat) && ($quelle === null || $kandidat < $quelle) ? $kandidat : $quelle;
                    }
                }
                if ($quelle !== null) {
                    $ergebnis[] = (object) [
                        'local_path' => $asset['local_path'],
                        'status' => $asset['status'],
                        'position' => $asset['position'],
                        'theme' => $asset['theme'],
                        'source_type' => $quelle,
                    ];
                }
            }

            return array_slice($ergebnis, 0, 500);
        }
        if (str_contains($stmt, 'FROM `xplugin_mgd_ai_asset`') && str_contains($stmt, ' IN (')) {
            $requested = $this->integerParameterSet($params);

            return array_values(array_map(
                static fn(array $asset): stdClass => (object) ['id' => $asset['id']],
                array_filter($this->assets, static fn(array $asset): bool => isset($requested[$asset['id']])),
            ));
        }
        if (str_contains($stmt, 'FROM `xplugin_mgd_ai_usage`') && str_contains($stmt, ' IN (')) {
            $requested = $this->integerParameterSet($params);

            return array_values(array_map(
                static fn(array $usage): stdClass => (object) ['id' => $usage['id']],
                array_filter(
                    $this->usages,
                    static function (array $usage) use ($requested): bool {
                        $id = $usage['id'] ?? null;

                        return is_int($id) && isset($requested[$id])
                            && ($usage['is_present'] ?? null) === 0;
                    },
                ),
            ));
        }
        foreach ($this->scannerRows as $table => $rows) {
            if (str_contains($stmt, 'FROM `' . $table . '`')) {
                $offset = $params['offset'] ?? 0;
                $limit = $params['limit'] ?? 100;

                $result = is_int($offset) && is_int($limit) ? array_slice($rows, $offset, $limit) : [];
                if ($table === 'topcpage' && isset($params['max_json_bytes']) && is_int($params['max_json_bytes'])) {
                    $result = array_map(function (stdClass $row) use ($params): stdClass {
                        $copy = clone $row;
                        $json = $row->areas_json ?? null;
                        $bytes = $row->json_bytes ?? (is_string($json) ? strlen($json) : null);
                        $copy->json_bytes = $bytes;
                        if (is_int($bytes) && $bytes > $params['max_json_bytes']) {
                            $copy->areas_json = null;
                            ++$this->scannerPayloadsSuppressed;
                        }

                        return $copy;
                    }, $result);
                }
                $this->lastScannerResult = $result;

                return $result;
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

    /**
     * @param array<string, mixed> $params
     *
     * @return array<int, true>
     */
    private function integerParameterSet(array $params): array
    {
        $result = [];
        foreach ($params as $value) {
            if (is_int($value)) {
                $result[$value] = true;
            }
        }

        return $result;
    }

    public function getAffectedRows(string $stmt, array $params = []): int
    {
        $this->statements[] = ['sql' => $stmt, 'params' => $params];

        if (str_starts_with(ltrim($stmt), 'CREATE TEMPORARY TABLE')) {
            if ($this->temporaryScanTableExists) {
                throw new RuntimeException('Temporäre Scantabelle existiert bereits.');
            }
            $this->temporaryScanTableExists = true;
            $this->scanUsages = [];

            return 0;
        }
        if (str_starts_with(ltrim($stmt), 'DROP TEMPORARY TABLE')) {
            ++$this->temporaryTableDrops;
            if ($this->failTemporaryDropOnce) {
                $this->failTemporaryDropOnce = false;
                throw new RuntimeException('Erzwungener Fehler beim Löschen der temporären Scantabelle.');
            }
            $this->temporaryScanTableExists = false;
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

        if (str_starts_with(ltrim($stmt), 'DELETE FROM `xplugin_mgd_ai_confirmation_claim`')) {
            if ($this->confirmationClaimPurgeFails) {
                throw new RuntimeException('Erzwungener interner Claim-Purge-Fehler.');
            }
            $databaseNow = $this->confirmationDatabaseNow ?? gmdate('Y-m-d H:i:s');
            $retentionBoundary = gmdate('Y-m-d H:i:s', strtotime($databaseNow . ' UTC') - 86400);
            asort($this->confirmationClaims, SORT_STRING);
            $removed = 0;
            foreach ($this->confirmationClaims as $tokenHash => $expiresAt) {
                if ($expiresAt > $retentionBoundary || $removed >= 1000) {
                    continue;
                }
                unset($this->confirmationClaims[$tokenHash]);
                ++$removed;
            }

            return $removed;
        }

        if (str_contains($stmt, 'INSERT IGNORE INTO `xplugin_mgd_ai_confirmation_claim`')) {
            if ($this->confirmationClaimsFail) {
                throw new RuntimeException('Erzwungener interner Claim-Datenbankfehler.');
            }
            $tokenHash = $params['token_hash'] ?? null;
            if (!is_string($tokenHash)) {
                throw new RuntimeException('Claim ohne gebundenen Token-Hash.');
            }
            if (isset($this->confirmationClaims[$tokenHash])) {
                return 0;
            }
            $expiresAtValue = $params['expires_at_value'] ?? null;
            $expiresAtGuard = $params['expires_at_guard'] ?? null;
            if (!is_string($expiresAtValue) || !is_string($expiresAtGuard) || $expiresAtValue !== $expiresAtGuard) {
                throw new RuntimeException('Claim ohne gebundene Ablaufzeit.');
            }
            $databaseNow = $this->confirmationDatabaseNow ?? gmdate('Y-m-d H:i:s');
            if ($expiresAtGuard <= $databaseNow) {
                return 0;
            }
            $this->confirmationClaims[$tokenHash] = $expiresAtValue;

            return 1;
        }

        if (str_contains($stmt, 'UPDATE `xplugin_mgd_ai_asset`')) {
            $id = $params['id'] ?? null;
            if (is_int($id)) {
                foreach ($this->assets as &$asset) {
                    if ($asset['id'] !== $id) {
                        continue;
                    }
                    foreach (['status', 'position', 'theme'] as $field) {
                        if (isset($params[$field]) && is_string($params[$field])) {
                            $asset[$field] = $params[$field];
                        }
                    }
                    unset($asset);

                    return 1;
                }
                unset($asset);

                return 0;
            }
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
            $sourceType = $this->stringParameter($params, 'source_type');
            foreach ($this->usages as $key => &$usage) {
                if (!isset($this->scanUsages[$key])
                    && ($usage['is_present'] ?? null) === 1
                    && ($usage['source_type'] ?? null) === $sourceType
                ) {
                    $usage['is_present'] = 0;
                    ++$affected;
                }
            }
            unset($usage);

            return $affected;
        }

        if (str_contains($stmt, 'DELETE FROM `xplugin_mgd_ai_usage`')) {
            $id = $params['id'] ?? null;
            foreach ($this->usages as $key => $usage) {
                if (($usage['id'] ?? null) === $id && ($usage['is_present'] ?? null) === 0) {
                    unset($this->usages[$key]);

                    return 1;
                }
            }

            return 0;
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
        if ($this->failCommit) {
            throw new RuntimeException('Erzwungener Commit-Fehler.');
        }
        if ($this->returnFalseOnCommit) {
            return false;
        }
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
            'xplugin_mgd_ai_confirmation_claim' => [
                ['token_hash', 'char(64)', 'NO', null, 'ascii_bin', ''],
                ['expires_at', 'datetime(6)', 'NO', null, null, ''],
                ['claimed_at', 'timestamp(6)', 'NO', 'current_timestamp(6)', null, 'DEFAULT_GENERATED'],
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
        } elseif ($table === 'xplugin_mgd_ai_philosophy') {
            $indexes = [
                ['PRIMARY', '0', '1', 'id'],
                ['uq_mgd_ai_philosophy_language', '0', '1', 'language'],
            ];
        } else {
            $indexes = [
                ['PRIMARY', '0', '1', 'token_hash'],
                ['idx_mgd_ai_confirmation_expires', '1', '1', 'expires_at'],
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

/** Minimales PDO-Doppel ausschließlich für den offiziellen inTransaction-Vertrag. */
final class TransactionStatePdo extends PDO
{
    public bool $transactionActive = false;

    public function __construct() {}

    public function inTransaction(): bool
    {
        return $this->transactionActive;
    }
}
