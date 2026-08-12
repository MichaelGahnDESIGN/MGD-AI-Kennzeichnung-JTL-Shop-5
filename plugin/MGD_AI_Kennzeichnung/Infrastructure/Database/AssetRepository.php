<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database;

use JTL\DB\DbInterface;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelPosition;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelStatus;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelTheme;
use RuntimeException;
use Throwable;

/** Speichert technische Bildkennzeichnungen ohne frei zusammengesetzte SQL-Werte. */
final class AssetRepository
{
    private const TABLE = 'xplugin_mgd_ai_asset';

    private readonly SchemaOwnershipGuard $ownership;

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
        $normalKey = $this->boundedText($assetKey, 191);
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
            foreach ($assets as $asset) {
                $assetKey = $this->boundedText($asset['asset_key'], 191);
                if ($assetKey === '') {
                    throw new RuntimeException('Ein Asset-Schlüssel darf nicht leer sein.');
                }

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
                        'asset_key' => $assetKey,
                    ],
                );
            }

            if (!$this->db->commit()) {
                throw new RuntimeException('Datenbanktransaktion konnte nicht bestätigt werden.');
            }
        } catch (Throwable $fehler) {
            try {
                $this->db->rollback();
            } catch (Throwable) {
                /*
                 * Der ursprüngliche Schreibfehler bleibt für Diagnose und
                 * Sicherheitslogik maßgeblich. Ein zusätzlicher Rollbackfehler
                 * darf ihn nicht verdecken; der Shop kann ihn zentral melden.
                 */
            }
            throw $fehler;
        }
    }

    private function boundedText(mixed $input, int $maximum): string
    {
        if (!is_string($input)) {
            return '';
        }

        return mb_substr(trim(str_replace("\0", '', $input)), 0, $maximum);
    }

    private function normalPath(mixed $input): string
    {
        if (!is_string($input)) {
            return '';
        }

        $path = str_replace(['\\', "\0"], ['/', ''], trim($input));
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

        return mb_substr('/' . implode('/', $segments), 0, 1024);
    }
}
