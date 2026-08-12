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
    private bool $scanOwnershipConfirmed = false;

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
        if (!$this->scanOwnershipConfirmed) {
            $this->assertReadyForScan();
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

    /** Prüft den unveränderlichen Tabellenvertrag einmal vor einem Scanlauf. */
    public function assertReadyForScan(): void
    {
        if (!$this->scanOwnershipConfirmed) {
            $this->ownership->assertOwned(self::TABLE);
            $this->scanOwnershipConfirmed = true;
        }
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
