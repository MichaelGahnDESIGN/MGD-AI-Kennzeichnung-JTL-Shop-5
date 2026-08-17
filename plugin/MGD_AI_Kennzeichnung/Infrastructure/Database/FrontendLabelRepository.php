<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database;

use JTL\DB\DbInterface;

/**
 * Liest die im Frontend sichtbaren Kennzeichnungen in einer festen Obergrenze.
 *
 * Die Abfrage ist ausschließlich lesend, enthält keine dynamischen SQL-Teile
 * und liefert nur lokale technische Metadaten. Personenbezogene Daten oder
 * Inhalte fremder Dienste werden weder gelesen noch übertragen.
 */
final class FrontendLabelRepository
{
    private const MAX_LABELS = 500;

    public function __construct(private readonly DbInterface $db) {}

    /**
     * @return list<array{local_path: string, status: string, position: string, theme: string, source_type: string}>
     */
    public function visibleLabels(): array
    {
        $zeilen = $this->db->getObjects(
            <<<'SQL'
                SELECT `asset`.`local_path`,
                       `asset`.`status`,
                       `asset`.`position`,
                       `asset`.`theme`,
                       MIN(`usage`.`source_type`) AS `source_type`
                  FROM `xplugin_mgd_ai_asset` AS `asset`
                  INNER JOIN `xplugin_mgd_ai_usage` AS `usage`
                          ON `usage`.`asset_id` = `asset`.`id`
                         AND `usage`.`is_present` = 1
                 WHERE `asset`.`status` IN ('generated', 'partially-generated', 'modified', 'deepfake')
                 GROUP BY `asset`.`id`, `asset`.`local_path`, `asset`.`status`, `asset`.`position`, `asset`.`theme`
                 ORDER BY `asset`.`id` ASC
                 LIMIT 500
                SQL,
        );

        $ergebnis = [];
        foreach (array_slice($zeilen, 0, self::MAX_LABELS) as $zeile) {
            $werte = [
                'local_path' => $zeile->local_path ?? null,
                'status' => $zeile->status ?? null,
                'position' => $zeile->position ?? null,
                'theme' => $zeile->theme ?? null,
                'source_type' => $zeile->source_type ?? null,
            ];
            if (array_filter($werte, static fn(mixed $wert): bool => !is_string($wert)) !== []) {
                continue;
            }
            /** @var array{local_path: string, status: string, position: string, theme: string, source_type: string} $werte */
            $ergebnis[] = $werte;
        }

        return $ergebnis;
    }
}
