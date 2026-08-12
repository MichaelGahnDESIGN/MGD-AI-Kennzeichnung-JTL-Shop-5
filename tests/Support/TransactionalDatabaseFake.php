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
    /** @var array<string, string> */
    private array $markers = [];

    /** @var array<string, string> */
    private array $fingerprints = [];

    /** @var array<string, array{label: string, status: string, position: string, theme: string, local_path: string}> */
    private array $assets = [];

    /** @var array<string, array<string, mixed>> */
    private array $usages = [];

    /** @var array<string, string> */
    private array $philosophies = [];

    /** @var null|array{assets: array<string, array{label: string, status: string, position: string, theme: string, local_path: string}>, usages: array<string, array<string, mixed>>, philosophies: array<string, string>} */
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
    public ?string $foreignTableBeforeCreate = null;
    /** @var list<string> */
    public array $droppedTables = [];
    private int $createCount = 0;

    public function setMarker(string $table, string $marker): void
    {
        $this->markers[$table] = $marker;
        $this->fingerprints[$table] = SchemaOwnershipGuard::expectedFingerprint($table);
    }

    public function setFingerprint(string $table, string $fingerprint): void
    {
        $this->fingerprints[$table] = $fingerprint;
    }

    /** @param array<string, string> $statuses */
    public function seedAssets(array $statuses): void
    {
        foreach ($statuses as $assetKey => $status) {
            $canonicalKey = $this->canonicalAssetKey($assetKey);
            $this->assets[$canonicalKey] = [
                'label' => $assetKey,
                'status' => $status,
                'position' => 'bottom-right',
                'theme' => 'auto',
                'local_path' => '/media/' . $assetKey . '.jpg',
            ];
        }
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
        $table = $params['table_name'] ?? null;
        if (!is_string($table) || !array_key_exists($table, $this->markers)) {
            return null;
        }

        return (object) [
            'ownership_marker' => $this->markers[$table],
            'calculated_fingerprint' => $this->fingerprints[$table] ?? '',
        ];
    }

    public function getAffectedRows(string $stmt, array $params = []): int
    {
        $this->statements[] = ['sql' => $stmt, 'params' => $params];

        if (str_starts_with(ltrim($stmt), 'CREATE TABLE')) {
            ++$this->createCount;
            if ($this->foreignTableBeforeCreate !== null
                && str_contains($stmt, '`' . $this->foreignTableBeforeCreate . '`')) {
                $this->markers[$this->foreignTableBeforeCreate] = 'fremder-marker';
                $this->fingerprints[$this->foreignTableBeforeCreate] = 'fremdes-schema';
            }
            if ($this->createCount === $this->failCreateNumber) {
                if ($this->alterFingerprintBeforeCleanup !== null) {
                    $this->fingerprints[$this->alterFingerprintBeforeCleanup] = 'inzwischen-veraendert';
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
                $this->fingerprints[$table] = SchemaOwnershipGuard::expectedFingerprint($table);
            }

            return 0;
        }

        if (str_starts_with(ltrim($stmt), 'DROP TABLE')) {
            if (preg_match('/DROP TABLE `([^`]+)`/', $stmt, $treffer) !== 1) {
                throw new RuntimeException('DROP ohne festen Tabellennamen.');
            }
            $table = $treffer[1];
            unset($this->markers[$table], $this->fingerprints[$table]);
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
            $this->assets[$assetKey] = [
                'label' => $assetKey,
                'status' => $this->stringParameter($params, 'status', 'unreviewed'),
                'position' => $this->stringParameter($params, 'position', 'bottom-right'),
                'theme' => $this->stringParameter($params, 'theme', 'auto'),
                'local_path' => $this->stringParameter($params, 'local_path'),
            ];

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
}
