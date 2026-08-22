<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database;

use JTL\DB\DbInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\LocalAssetLabelRepositoryInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Value\LocalAssetLabel;
use Plugin\MGD_AI_Kennzeichnung\Domain\AssetSource;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelPosition;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelStatus;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelTheme;
use RuntimeException;
use Throwable;

/**
 * Speichert eine lokale Kennzeichnung und ihre minimierte Fundstelle atomar.
 *
 * Die Fundstellenreferenz enthält nicht den lesbaren Dateipfad, sondern nur
 * dessen Hash. Der eigentliche lokale Pfad liegt genau einmal in der dafür
 * vorgesehenen Asset-Tabelle.
 */
final class LocalAssetLabelRepository implements LocalAssetLabelRepositoryInterface
{
    private readonly SchemaOwnershipGuard $ownership;

    public function __construct(private readonly DbInterface $db)
    {
        $this->ownership = new SchemaOwnershipGuard($db);
    }

    public function findByLocalPath(string $localPath): ?LocalAssetLabel
    {
        $this->assertOwnedTables();
        $row = $this->db->getSingleObject(
            <<<'SQL'
                SELECT `asset`.`id`, `asset`.`local_path`, `asset`.`status`, `asset`.`position`, `asset`.`theme`,
                       COALESCE(MIN(`usage`.`source_type`), 'unknown') AS `source`
                  FROM `xplugin_mgd_ai_asset` AS `asset`
                  LEFT JOIN `xplugin_mgd_ai_usage` AS `usage` ON `usage`.`asset_id` = `asset`.`id`
                 WHERE `asset`.`local_path` = :local_path
                 GROUP BY `asset`.`id`, `asset`.`local_path`, `asset`.`status`, `asset`.`position`, `asset`.`theme`
                SQL,
            ['local_path' => $localPath],
        );

        return $row === null ? null : $this->labelFromRow($row);
    }

    public function save(
        string $localPath,
        AssetSource $source,
        LabelStatus $status,
        LabelPosition $position,
        LabelTheme $theme,
    ): LocalAssetLabel {
        $this->assertOwnedTables();
        if ($this->db->getPDO()->inTransaction()) {
            throw new RuntimeException('Die lokale Bildkennzeichnung darf nicht in einer fremden Transaktion starten.');
        }
        if (!$this->db->beginTransaction()) {
            throw new RuntimeException('Die lokale Bildkennzeichnung konnte nicht gestartet werden.');
        }

        try {
            $id = $this->lockedIdByLocalPath($localPath);
            if ($id === null) {
                $assetKey = hash('sha256', $localPath);
                $this->db->getAffectedRows(
                    <<<'SQL'
                        INSERT INTO `xplugin_mgd_ai_asset`
                            (`asset_key`, `local_path`, `status`, `position`, `theme`, `created_at`, `updated_at`)
                        VALUES
                            (:asset_key, :local_path, :status, :position, :theme, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                        ON DUPLICATE KEY UPDATE `local_path` = VALUES(`local_path`)
                        SQL,
                    [
                        'asset_key' => $assetKey,
                        'local_path' => $localPath,
                        'status' => $status->value,
                        'position' => $position->value,
                        'theme' => $theme->value,
                    ],
                );
                $id = $this->lockedIdByAssetKey($assetKey);
            }

            $this->db->getAffectedRows(
                <<<'SQL'
                    UPDATE `xplugin_mgd_ai_asset`
                       SET `status` = :status,
                           `position` = :position,
                           `theme` = :theme,
                           `updated_at` = CURRENT_TIMESTAMP
                     WHERE `id` = :id
                    SQL,
                [
                    'status' => $status->value,
                    'position' => $position->value,
                    'theme' => $theme->value,
                    'id' => $id,
                ],
            );
            $sourceReference = 'local-path-sha256:' . hash('sha256', $localPath);
            $this->db->getAffectedRows(
                <<<'SQL'
                    INSERT INTO `xplugin_mgd_ai_usage`
                        (`asset_id`, `source_type`, `source_reference`, `source_reference_hash`, `context`, `last_seen_at`, `is_present`)
                    VALUES
                        (:asset_id, :source_type, :source_reference, :source_reference_hash, NULL, CURRENT_TIMESTAMP, 1)
                    ON DUPLICATE KEY UPDATE `last_seen_at` = CURRENT_TIMESTAMP, `is_present` = 1
                    SQL,
                [
                    'asset_id' => $id,
                    'source_type' => $source->value,
                    'source_reference' => $sourceReference,
                    'source_reference_hash' => hash('sha256', $sourceReference),
                ],
            );

            if (!$this->db->commit()) {
                throw new RuntimeException('Die lokale Bildkennzeichnung konnte nicht bestätigt werden.');
            }

            return new LocalAssetLabel($id, $localPath, $status, $position, $theme, $source, true);
        } catch (Throwable $error) {
            try {
                if (!$this->db->rollback()) {
                    throw new RuntimeException('Datenbank-Rollback meldete false.');
                }
            } catch (Throwable) {
                throw new RuntimeException(
                    'Die lokale Bildkennzeichnung und ihre Rücknahme sind fehlgeschlagen.',
                    0,
                    $error,
                );
            }
            throw $error;
        }
    }

    private function lockedIdByLocalPath(string $localPath): ?int
    {
        $row = $this->db->getSingleObject(
            'SELECT `id` FROM `xplugin_mgd_ai_asset` WHERE `local_path` = :local_path FOR UPDATE',
            ['local_path' => $localPath],
        );

        return $row === null ? null : $this->positiveId($row->id ?? null);
    }

    private function lockedIdByAssetKey(string $assetKey): int
    {
        $row = $this->db->getSingleObject(
            'SELECT `id` FROM `xplugin_mgd_ai_asset` WHERE `asset_key` = :asset_key FOR UPDATE',
            ['asset_key' => $assetKey],
        );
        if ($row === null) {
            throw new RuntimeException('Das lokale Bild konnte nach dem Speichern nicht gesperrt werden.');
        }

        return $this->positiveId($row->id ?? null);
    }

    private function positiveId(mixed $id): int
    {
        if (is_string($id) && preg_match('/^[1-9][0-9]*$/D', $id) === 1) {
            $id = (int) $id;
        }
        if (!is_int($id) || $id < 1) {
            throw new RuntimeException('Das lokale Bild besitzt keine gültige technische ID.');
        }

        return $id;
    }

    private function labelFromRow(object $row): LocalAssetLabel
    {
        return new LocalAssetLabel(
            id: $this->positiveId($row->id ?? null),
            localPath: is_string($row->local_path ?? null) ? $row->local_path : '',
            status: LabelStatus::fromInput($row->status ?? null),
            position: LabelPosition::fromInput($row->position ?? null),
            theme: LabelTheme::fromInput($row->theme ?? null),
            source: AssetSource::fromInput($row->source ?? null),
            persisted: true,
        );
    }

    private function assertOwnedTables(): void
    {
        $this->ownership->assertOwned('xplugin_mgd_ai_asset');
        $this->ownership->assertOwned('xplugin_mgd_ai_usage');
    }
}
