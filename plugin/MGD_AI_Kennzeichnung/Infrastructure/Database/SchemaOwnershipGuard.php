<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database;

use JTL\DB\DbInterface;
use RuntimeException;

/**
 * Verhindert, dass das Plugin gleichnamige Tabellen eines fremden Eigentümers
 * verändert. Eigentum wird nicht aus einer PHP-Annahme abgeleitet, sondern aus
 * dem dauerhaft in MySQL gespeicherten Tabellenkommentar gelesen.
 */
final class SchemaOwnershipGuard
{
    public const OWNERSHIP_MARKER = 'mgd-ai-kennzeichnung-jtl-v1';

    /** @var list<string> Ausschließlich diese fest programmierten Tabellen sind zulässig. */
    private const ALLOWED_TABLES = [
        'xplugin_mgd_ai_asset',
        'xplugin_mgd_ai_usage',
        'xplugin_mgd_ai_philosophy',
    ];

    /**
     * Feste Fingerprints der drei erwarteten Schemata. Die Werte ändern sich
     * nur gemeinsam mit einer bewusst versionierten Migration. Der SQL-Abruf
     * bezieht Spalten, Collations, Indizes und Fremdschlüssel ein.
     *
     * @var array<string, string>
     */
    private const EXPECTED_FINGERPRINTS = [
        'xplugin_mgd_ai_asset' => '2041b2e6ae498861d47ff643532a322a0dca84d555432a550025ade680000127',
        'xplugin_mgd_ai_usage' => '052fd8eb2dabf6ad28d0e1553e920c8f8398733011352796c0dc80260acecf36',
        'xplugin_mgd_ai_philosophy' => 'dc9a3bee11871aff7feabfec7a004cdc580b46c0b7155a8bfd27f36dcd1c0cea',
    ];

    public function __construct(private readonly DbInterface $db) {}

    /**
     * Erlaubt das Erstellen einer fehlenden Tabelle oder das Ändern einer exakt
     * markierten eigenen Tabelle. Fremde und nicht markierte Tabellen bleiben
     * unverändert. Der Tabellenname kann nicht als freie SQL-Eingabe dienen.
     */
    public function mayMutate(string $table): bool
    {
        $this->assertAllowedTable($table);
        $metadata = $this->metadata($table);

        return $metadata === null || $this->isExpectedSchema($table, $metadata);
    }

    /**
     * Verlangt eine bereits vorhandene, eindeutig diesem Plugin gehörende
     * Tabelle. Diese strengere Methode ist insbesondere für spätere Updates
     * und eine sichere Deinstallation verwendbar.
     */
    public function assertOwned(string $table): void
    {
        $this->assertAllowedTable($table);
        $metadata = $this->metadata($table);
        if ($metadata === null || !$this->isExpectedSchema($table, $metadata)) {
            throw new RuntimeException(sprintf(
                'Tabelle %s besitzt nicht Marker und Schema-Fingerprint dieses Plugins.',
                $table,
            ));
        }
    }

    /** Gibt ausschließlich für die Migration an, ob der feste Tabellenname existiert. */
    public function exists(string $table): bool
    {
        $this->assertAllowedTable($table);

        return $this->metadata($table) !== null;
    }

    /** Liefert dem Migrationstest-Fake denselben unveränderlichen Vertrag. */
    public static function expectedFingerprint(string $table): string
    {
        if (!isset(self::EXPECTED_FINGERPRINTS[$table])) {
            throw new RuntimeException('Unbekannter Tabellenname wurde abgewiesen.');
        }

        return self::EXPECTED_FINGERPRINTS[$table];
    }

    private function assertAllowedTable(string $table): void
    {
        if (!in_array($table, self::ALLOWED_TABLES, true)) {
            throw new RuntimeException('Unbekannter Tabellenname wurde abgewiesen.');
        }
    }

    private function metadata(string $table): ?object
    {
        return $this->db->getSingleObject(
            <<<'SQL'
                SELECT `t`.`TABLE_COMMENT` AS `ownership_marker`,
                       SHA2(CONCAT_WS('|',
                           COALESCE((
                               SELECT GROUP_CONCAT(CONCAT_WS(':',
                                   `c`.`COLUMN_NAME`, `c`.`COLUMN_TYPE`, `c`.`IS_NULLABLE`,
                                   COALESCE(`c`.`COLUMN_DEFAULT`, '<NULL>'),
                                   COALESCE(`c`.`COLLATION_NAME`, '<NONE>'), `c`.`EXTRA`
                               ) ORDER BY `c`.`ORDINAL_POSITION` SEPARATOR ',' )
                                 FROM `INFORMATION_SCHEMA`.`COLUMNS` AS `c`
                                WHERE `c`.`TABLE_SCHEMA` = DATABASE()
                                  AND `c`.`TABLE_NAME` = `input`.`target_table`
                           ), ''),
                           COALESCE((
                               SELECT GROUP_CONCAT(CONCAT_WS(':',
                                   `s`.`INDEX_NAME`, `s`.`NON_UNIQUE`, `s`.`SEQ_IN_INDEX`, `s`.`COLUMN_NAME`
                               ) ORDER BY `s`.`INDEX_NAME`, `s`.`SEQ_IN_INDEX` SEPARATOR ',')
                                 FROM `INFORMATION_SCHEMA`.`STATISTICS` AS `s`
                                WHERE `s`.`TABLE_SCHEMA` = DATABASE()
                                  AND `s`.`TABLE_NAME` = `input`.`target_table`
                           ), ''),
                           COALESCE((
                               SELECT GROUP_CONCAT(CONCAT_WS(':',
                                   `k`.`CONSTRAINT_NAME`, `k`.`COLUMN_NAME`, `k`.`REFERENCED_TABLE_NAME`,
                                   `k`.`REFERENCED_COLUMN_NAME`, `r`.`UPDATE_RULE`, `r`.`DELETE_RULE`
                               ) ORDER BY `k`.`CONSTRAINT_NAME`, `k`.`ORDINAL_POSITION` SEPARATOR ',')
                                 FROM `INFORMATION_SCHEMA`.`KEY_COLUMN_USAGE` AS `k`
                                 JOIN `INFORMATION_SCHEMA`.`REFERENTIAL_CONSTRAINTS` AS `r`
                                   ON `r`.`CONSTRAINT_SCHEMA` = `k`.`CONSTRAINT_SCHEMA`
                                  AND `r`.`CONSTRAINT_NAME` = `k`.`CONSTRAINT_NAME`
                                WHERE `k`.`TABLE_SCHEMA` = DATABASE()
                                  AND `k`.`TABLE_NAME` = `input`.`target_table`
                                  AND `k`.`REFERENCED_TABLE_NAME` IS NOT NULL
                           ), ''),
                           `t`.`TABLE_COLLATION`
                       ), 256) AS `calculated_fingerprint`
                  FROM `INFORMATION_SCHEMA`.`TABLES`
                    AS `t`
                 CROSS JOIN (SELECT :table_name AS `target_table`) AS `input`
                 WHERE `TABLE_SCHEMA` = DATABASE()
                   AND `TABLE_NAME` = `input`.`target_table`
                SQL,
            ['table_name' => $table],
        );
    }

    private function isExpectedSchema(string $table, object $metadata): bool
    {
        return ($metadata->ownership_marker ?? null) === self::OWNERSHIP_MARKER
            && ($metadata->calculated_fingerprint ?? null) === self::expectedFingerprint($table);
    }
}
