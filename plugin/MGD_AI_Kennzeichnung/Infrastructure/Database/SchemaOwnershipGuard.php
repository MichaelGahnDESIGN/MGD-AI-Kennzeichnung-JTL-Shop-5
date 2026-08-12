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

        return $metadata === null || ($metadata->ownership_marker ?? null) === self::OWNERSHIP_MARKER;
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
        if ($metadata === null || ($metadata->ownership_marker ?? null) !== self::OWNERSHIP_MARKER) {
            throw new RuntimeException(sprintf(
                'Tabelle %s ist nicht eindeutig Eigentum des Plugins und darf nicht verändert werden.',
                $table,
            ));
        }
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
                SELECT `TABLE_COMMENT` AS `ownership_marker`
                  FROM `INFORMATION_SCHEMA`.`TABLES`
                 WHERE `TABLE_SCHEMA` = DATABASE()
                   AND `TABLE_NAME` = :table_name
                SQL,
            ['table_name' => $table],
        );
    }
}
