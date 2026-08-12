<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database;

use JTL\DB\DbInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\AssetNotFoundException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AdminAssetRepositoryInterface;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelPosition;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelStatus;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelTheme;
use RuntimeException;
use Throwable;

/** Speichert technische Bildkennzeichnungen ohne frei zusammengesetzte SQL-Werte. */
final class AssetRepository implements AdminAssetRepositoryInterface
{
    private const TABLE = 'xplugin_mgd_ai_asset';

    private readonly SchemaOwnershipGuard $ownership;
    private bool $scanSessionActive = false;

    public function __construct(private readonly DbInterface $db)
    {
        $this->ownership = new SchemaOwnershipGuard($db);
    }

    /**
     * Legt ein Asset anhand seines stabilen technischen Schlüssels an oder
     * aktualisiert es. Der lokale Pfad wird begrenzt und lexikalisch bereinigt;
     * ein Dateisystemzugriff oder die Speicherung externer URLs findet nicht statt.
     */
    public function upsert(
        mixed $assetKey,
        mixed $localPath,
        mixed $status = null,
        mixed $position = null,
        mixed $theme = null,
    ): void {
        $this->ownership->assertOwned(self::TABLE);
        $normalKey = $this->canonicalAssetKey($assetKey);
        $normalPath = $this->normalPath($localPath);
        if ($normalKey === '' || $normalPath === '') {
            throw new RuntimeException('Asset-Schlüssel und lokaler Pfad dürfen nicht leer sein.');
        }

        $this->db->getAffectedRows(
            <<<'SQL'
                INSERT INTO `xplugin_mgd_ai_asset`
                    (`asset_key`, `local_path`, `status`, `position`, `theme`, `created_at`, `updated_at`)
                VALUES
                    (:asset_key, :local_path, :status, :position, :theme, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE
                    `local_path` = VALUES(`local_path`),
                    `status` = VALUES(`status`),
                    `position` = VALUES(`position`),
                    `theme` = VALUES(`theme`),
                    `updated_at` = CURRENT_TIMESTAMP
                SQL,
            [
                'asset_key' => $normalKey,
                'local_path' => $normalPath,
                'status' => LabelStatus::fromInput($status)->value,
                'position' => LabelPosition::fromInput($position)->value,
                'theme' => LabelTheme::fromInput($theme)->value,
            ],
        );
    }

    /**
     * Legt ein beim Scan neu gefundenes Asset ausschließlich mit dem sicheren
     * Startstatus „unreviewed“ an. Bei einem vorhandenen Schlüssel werden nur
     * technische Pfaddaten aktualisiert; die menschliche Kennzeichnung bleibt
     * unter allen Umständen unverändert.
     *
     * @return array{id: int, created: bool}
     */
    public function ensureUnreviewed(string $assetKey, string $localPath): array
    {
        if (!$this->scanSessionActive) {
            $this->ownership->assertOwned(self::TABLE);
        }
        $normalKey = $this->canonicalAssetKey($assetKey);
        $normalPath = ltrim($this->normalPath($localPath), '/');
        if ($normalKey === '' || $normalPath === '') {
            throw new RuntimeException('Asset-Schlüssel und lokaler Pfad dürfen nicht leer sein.');
        }

        $affected = $this->db->getAffectedRows(
            <<<'SQL'
                INSERT INTO `xplugin_mgd_ai_asset`
                    (`asset_key`, `local_path`, `status`, `position`, `theme`, `created_at`, `updated_at`)
                VALUES
                    (:asset_key, :local_path, 'unreviewed', 'bottom-right', 'auto', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE
                    `local_path` = VALUES(`local_path`),
                    `updated_at` = CURRENT_TIMESTAMP
                SQL,
            ['asset_key' => $normalKey, 'local_path' => $normalPath],
        );
        $row = $this->db->getSingleObject(
            <<<'SQL'
                SELECT `id`
                  FROM `xplugin_mgd_ai_asset`
                 WHERE `asset_key` = :asset_key
                SQL,
            ['asset_key' => $normalKey],
        );
        $id = $row->id ?? null;
        if (is_string($id) && preg_match('/^[1-9][0-9]*$/D', $id) === 1) {
            $id = (int) $id;
        }
        if (!is_int($id) || $id < 1) {
            throw new RuntimeException('Das gespeicherte Scan-Asset besitzt keine gültige technische ID.');
        }

        return ['id' => $id, 'created' => $affected === 1];
    }

    /** Öffnet nach einer frischen Ownership-Prüfung genau eine Scan-Session. */
    public function beginScanSession(): void
    {
        if ($this->scanSessionActive) {
            throw new RuntimeException('Eine Asset-Scan-Session ist bereits aktiv.');
        }
        $this->ownership->assertOwned(self::TABLE);
        $this->scanSessionActive = true;
    }

    /** Beendet die kurzlebige Ownership-Freigabe auch auf Fehlerpfaden. */
    public function endScanSession(): void
    {
        $this->scanSessionActive = false;
    }

    /**
     * Ändert die vollständige Benutzerauswahl atomar. Schlägt nur ein Element
     * fehl, stellt Rollback sämtliche davor geschriebenen Zustände wieder her.
     *
     * @param list<array{asset_key: mixed, status: mixed, position?: mixed, theme?: mixed}> $assets
     */
    public function bulkUpdate(array $assets): void
    {
        $this->ownership->assertOwned(self::TABLE);
        if (!$this->db->beginTransaction()) {
            throw new RuntimeException('Datenbanktransaktion konnte nicht gestartet werden.');
        }

        try {
            /** @var list<array{asset_key: string, status: mixed, position?: mixed, theme?: mixed}> $normalizedAssets */
            $normalizedAssets = [];
            $uniqueKeys = [];
            foreach ($assets as $asset) {
                $assetKey = $this->canonicalAssetKey($asset['asset_key']);
                if ($assetKey === '') {
                    throw new RuntimeException('Ein Asset-Schlüssel darf nicht leer sein.');
                }
                $normalizedAssets[] = [...$asset, 'asset_key' => $assetKey];
                $uniqueKeys[$assetKey] = true;
            }

            /*
             * Alle angeforderten Datensätze werden vor der ersten Änderung
             * gesperrt und validiert. Einzelabfragen halten Identifier fest und
             * binden jeden Hash; dynamisch interpolierte IN-Listen entfallen.
             */
            foreach (array_keys($uniqueKeys) as $assetKey) {
                $vorhanden = $this->db->getSingleObject(
                    <<<'SQL'
                        SELECT `id`
                          FROM `xplugin_mgd_ai_asset`
                         WHERE `asset_key` = :asset_key
                         FOR UPDATE
                        SQL,
                    ['asset_key' => $assetKey],
                );
                if ($vorhanden === null) {
                    throw new RuntimeException(sprintf('Asset %s wurde nicht gefunden.', $assetKey));
                }
            }

            foreach ($normalizedAssets as $asset) {
                $this->db->getAffectedRows(
                    <<<'SQL'
                        UPDATE `xplugin_mgd_ai_asset`
                           SET `status` = :status,
                               `position` = :position,
                               `theme` = :theme,
                               `updated_at` = CURRENT_TIMESTAMP
                         WHERE `asset_key` = :asset_key
                        SQL,
                    [
                        'status' => LabelStatus::fromInput($asset['status'])->value,
                        'position' => LabelPosition::fromInput($asset['position'] ?? null)->value,
                        'theme' => LabelTheme::fromInput($asset['theme'] ?? null)->value,
                        'asset_key' => $asset['asset_key'],
                    ],
                );
            }

            if (!$this->db->commit()) {
                throw new RuntimeException('Datenbanktransaktion konnte nicht bestätigt werden.');
            }
        } catch (Throwable $fehler) {
            try {
                if (!$this->db->rollback()) {
                    throw new RuntimeException('Datenbank-Rollback meldete false.');
                }
            } catch (Throwable $rollbackFehler) {
                throw new RuntimeException(
                    'Rollback nach fehlgeschlagener Asset-Aktualisierung ist ebenfalls fehlgeschlagen: '
                    . $rollbackFehler->getMessage(),
                    0,
                    $fehler,
                );
            }
            throw $fehler;
        }
    }

    /** @param list<int> $ids */
    public function countExistingIds(array $ids): int
    {
        $this->ownership->assertOwned(self::TABLE);
        $count = 0;
        foreach ($ids as $id) {
            $row = $this->db->getSingleObject(
                'SELECT `id` FROM `xplugin_mgd_ai_asset` WHERE `id` = :id',
                ['id' => $id],
            );
            if ($row !== null) {
                ++$count;
            }
        }

        return $count;
    }

    /** @param array<string, string> $changes */
    public function updateOneById(int $id, array $changes): void
    {
        $this->updateManyByIds([$id], $changes);
    }

    /**
     * Ändert eine vollständige ID-Auswahl atomar. Feldnamen gelangen niemals
     * aus Eingaben ins SQL, sondern wählen eine von sieben festen Anweisungen.
     *
     * @param list<int> $ids
     * @param array<string, string> $changes
     */
    public function updateManyByIds(array $ids, array $changes): void
    {
        $this->ownership->assertOwned(self::TABLE);
        if ($this->db->getPDO()->inTransaction()) {
            throw new RuntimeException('Die Bildänderung darf nicht in einer bereits aktiven Transaktion starten.');
        }
        $statement = $this->maskedUpdateStatement(array_keys($changes));
        if (!$this->db->beginTransaction()) {
            throw new RuntimeException('Die sichere Bildänderung konnte nicht gestartet werden.');
        }
        try {
            foreach ($ids as $id) {
                $existing = $this->db->getSingleObject(
                    'SELECT `id` FROM `xplugin_mgd_ai_asset` WHERE `id` = :id FOR UPDATE',
                    ['id' => $id],
                );
                if ($existing === null) {
                    throw new AssetNotFoundException('Mindestens ein ausgewähltes Asset ist nicht mehr vorhanden.');
                }
            }
            foreach ($ids as $id) {
                $this->db->getAffectedRows($statement, [...$changes, 'id' => $id]);
            }
            if (!$this->db->commit()) {
                throw new RuntimeException('Die sichere Bildänderung konnte nicht bestätigt werden.');
            }
        } catch (Throwable $error) {
            try {
                if (!$this->db->rollback()) {
                    throw new RuntimeException('Datenbank-Rollback meldete false.');
                }
            } catch (Throwable) {
                throw new RuntimeException('Die Bildänderung und ihre Rücknahme sind fehlgeschlagen.', 0, $error);
            }
            throw $error;
        }
    }

    /**
     * @param array<string, string|bool> $filters
     * @return list<array<string, scalar|null>>
     */
    public function listPage(int $offset, int $limit, array $filters, string $sort, string $direction): array
    {
        $this->ownership->assertOwned(self::TABLE);
        [$where, $params] = $this->listWhere($filters);
        $sortSql = match ($sort) {
            'id' => '`asset`.`id`',
            'status' => '`asset`.`status`',
            'updated_at' => '`asset`.`updated_at`',
            default => throw new RuntimeException('Die Sortierung ist nicht freigegeben.'),
        };
        $directionSql = match ($direction) {
            'asc' => 'ASC',
            'desc' => 'DESC',
            default => throw new RuntimeException('Die Sortierrichtung ist nicht freigegeben.'),
        };
        $rows = $this->db->getObjects(
            'SELECT `asset`.`id`, `asset`.`local_path`, `asset`.`status`, `asset`.`position`, `asset`.`theme`, '
            . 'COUNT(`usage`.`id`) AS `usage_count` '
            . 'FROM `xplugin_mgd_ai_asset` AS `asset` '
            . 'LEFT JOIN `xplugin_mgd_ai_usage` AS `usage` ON `usage`.`asset_id` = `asset`.`id` '
            . $where . ' GROUP BY `asset`.`id`, `asset`.`local_path`, `asset`.`status`, `asset`.`position`, `asset`.`theme`, `asset`.`updated_at` '
            . 'ORDER BY ' . $sortSql . ' ' . $directionSql . ' LIMIT :limit OFFSET :offset',
            [...$params, 'limit' => $limit, 'offset' => $offset],
        );

        return array_values(array_map(static fn(object $row): array => [
            'id' => is_numeric($row->id ?? null) ? (int) $row->id : 0,
            'local_path' => is_string($row->local_path ?? null) ? $row->local_path : '',
            'status' => is_string($row->status ?? null) ? $row->status : '',
            'position' => is_string($row->position ?? null) ? $row->position : '',
            'theme' => is_string($row->theme ?? null) ? $row->theme : '',
            'usage_count' => is_numeric($row->usage_count ?? null) ? (int) $row->usage_count : 0,
        ], $rows));
    }

    /** @param array<string, string|bool> $filters */
    public function countForList(array $filters): int
    {
        $this->ownership->assertOwned(self::TABLE);
        [$where, $params] = $this->listWhere($filters);
        $row = $this->db->getSingleObject(
            'SELECT COUNT(DISTINCT `asset`.`id`) AS `total` FROM `xplugin_mgd_ai_asset` AS `asset` '
            . 'LEFT JOIN `xplugin_mgd_ai_usage` AS `usage` ON `usage`.`asset_id` = `asset`.`id` ' . $where,
            $params,
        );

        return $row !== null && is_numeric($row->total ?? null) ? max(0, (int) $row->total) : 0;
    }

    /** @return array<string, scalar|null>|null */
    public function detailById(int $id): ?array
    {
        $this->ownership->assertOwned(self::TABLE);
        $row = $this->db->getSingleObject(
            <<<'SQL'
                SELECT `asset`.`id`, `asset`.`local_path`, `asset`.`status`, `asset`.`position`, `asset`.`theme`,
                       COUNT(`usage`.`id`) AS `usage_count`,
                       SUM(CASE WHEN `usage`.`is_present` = 1 THEN 1 ELSE 0 END) AS `present_usage_count`
                  FROM `xplugin_mgd_ai_asset` AS `asset`
                  LEFT JOIN `xplugin_mgd_ai_usage` AS `usage` ON `usage`.`asset_id` = `asset`.`id`
                  WHERE `asset`.`id` = :id
                  GROUP BY `asset`.`id`, `asset`.`local_path`, `asset`.`status`, `asset`.`position`, `asset`.`theme`
                SQL,
            ['id' => $id],
        );
        if ($row === null) {
            return null;
        }

        return [
            'id' => is_numeric($row->id ?? null) ? (int) $row->id : 0,
            'local_path' => is_string($row->local_path ?? null) ? $row->local_path : '',
            'status' => is_string($row->status ?? null) ? $row->status : '',
            'position' => is_string($row->position ?? null) ? $row->position : '',
            'theme' => is_string($row->theme ?? null) ? $row->theme : '',
            'usage_count' => is_numeric($row->usage_count ?? null) ? (int) $row->usage_count : 0,
            'present_usage_count' => is_numeric($row->present_usage_count ?? null) ? (int) $row->present_usage_count : 0,
        ];
    }

    /** @param list<string> $fields */
    private function maskedUpdateStatement(array $fields): string
    {
        sort($fields, SORT_STRING);
        return match ($fields) {
            ['status'] => 'UPDATE `xplugin_mgd_ai_asset` SET `status` = :status, `updated_at` = CURRENT_TIMESTAMP WHERE `id` = :id',
            ['position'] => 'UPDATE `xplugin_mgd_ai_asset` SET `position` = :position, `updated_at` = CURRENT_TIMESTAMP WHERE `id` = :id',
            ['theme'] => 'UPDATE `xplugin_mgd_ai_asset` SET `theme` = :theme, `updated_at` = CURRENT_TIMESTAMP WHERE `id` = :id',
            ['position', 'status'] => 'UPDATE `xplugin_mgd_ai_asset` SET `position` = :position, `status` = :status, `updated_at` = CURRENT_TIMESTAMP WHERE `id` = :id',
            ['status', 'theme'] => 'UPDATE `xplugin_mgd_ai_asset` SET `status` = :status, `theme` = :theme, `updated_at` = CURRENT_TIMESTAMP WHERE `id` = :id',
            ['position', 'theme'] => 'UPDATE `xplugin_mgd_ai_asset` SET `position` = :position, `theme` = :theme, `updated_at` = CURRENT_TIMESTAMP WHERE `id` = :id',
            ['position', 'status', 'theme'] => 'UPDATE `xplugin_mgd_ai_asset` SET `position` = :position, `status` = :status, `theme` = :theme, `updated_at` = CURRENT_TIMESTAMP WHERE `id` = :id',
            default => throw new RuntimeException('Die Änderungsfelder sind nicht freigegeben.'),
        };
    }

    /**
     * @param array<string, string|bool> $filters
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function listWhere(array $filters): array
    {
        $clauses = [];
        $params = [];
        if (isset($filters['status'])) {
            $clauses[] = '`asset`.`status` = :filter_status';
            $params['filter_status'] = $filters['status'];
        }
        if (isset($filters['source'])) {
            $clauses[] = '`usage`.`source_type` = :filter_source';
            $params['filter_source'] = $filters['source'];
        }
        if (isset($filters['present'])) {
            $clauses[] = '`usage`.`is_present` = :filter_present';
            $params['filter_present'] = $filters['present'] ? 1 : 0;
        }

        return [$clauses === [] ? '' : 'WHERE ' . implode(' AND ', $clauses), $params];
    }

    private function canonicalAssetKey(mixed $input): string
    {
        if (!is_string($input) || $input === '') {
            return '';
        }

        return preg_match('/^[a-f0-9]{64}$/i', $input) === 1
            ? strtolower($input)
            : hash('sha256', $input);
    }

    private function normalPath(mixed $input): string
    {
        if (!is_string($input)) {
            return '';
        }

        $path = str_replace(['\\', "\0"], ['/', ''], trim($input));
        if (mb_strlen($path) > 1024) {
            throw new RuntimeException('Der lokale Rohpfad ist zu lang.');
        }
        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $path) === 1) {
            return '';
        }
        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($segments);
                continue;
            }
            $segments[] = $segment;
        }

        if ($segments === []) {
            return '';
        }

        $normalized = '/' . implode('/', $segments);
        return $normalized;
    }
}
