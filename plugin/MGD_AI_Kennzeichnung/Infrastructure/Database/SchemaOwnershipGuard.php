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
                       `t`.`ENGINE` AS `table_engine`,
                       `t`.`TABLE_COLLATION` AS `table_collation`,
                       COALESCE((
                           SELECT JSON_ARRAYAGG(JSON_OBJECT(
                               'name', `c`.`COLUMN_NAME`, 'type', `c`.`COLUMN_TYPE`,
                               'nullable', `c`.`IS_NULLABLE`, 'default', `c`.`COLUMN_DEFAULT`,
                               'collation', `c`.`COLLATION_NAME`, 'extra', `c`.`EXTRA`,
                               'ordinal', `c`.`ORDINAL_POSITION`
                           ))
                             FROM `INFORMATION_SCHEMA`.`COLUMNS` AS `c`
                            WHERE `c`.`TABLE_SCHEMA` = DATABASE()
                              AND `c`.`TABLE_NAME` = `input`.`target_table`
                       ), JSON_ARRAY()) AS `columns_json`,
                       COALESCE((
                           SELECT JSON_ARRAYAGG(JSON_OBJECT(
                               'name', `s`.`INDEX_NAME`, 'non_unique', `s`.`NON_UNIQUE`,
                               'sequence', `s`.`SEQ_IN_INDEX`, 'column', `s`.`COLUMN_NAME`,
                               'sub_part', `s`.`SUB_PART`, 'collation', `s`.`COLLATION`,
                               'type', `s`.`INDEX_TYPE`
                           ))
                             FROM `INFORMATION_SCHEMA`.`STATISTICS` AS `s`
                            WHERE `s`.`TABLE_SCHEMA` = DATABASE()
                              AND `s`.`TABLE_NAME` = `input`.`target_table`
                       ), JSON_ARRAY()) AS `indexes_json`,
                       COALESCE((
                           SELECT JSON_ARRAYAGG(JSON_OBJECT(
                               'name', `k`.`CONSTRAINT_NAME`, 'column', `k`.`COLUMN_NAME`,
                               'referenced_table', `k`.`REFERENCED_TABLE_NAME`,
                               'referenced_column', `k`.`REFERENCED_COLUMN_NAME`,
                               'sequence', `k`.`ORDINAL_POSITION`,
                               'update_rule', `r`.`UPDATE_RULE`, 'delete_rule', `r`.`DELETE_RULE`
                           ))
                             FROM `INFORMATION_SCHEMA`.`KEY_COLUMN_USAGE` AS `k`
                             JOIN `INFORMATION_SCHEMA`.`REFERENTIAL_CONSTRAINTS` AS `r`
                               ON `r`.`CONSTRAINT_SCHEMA` = `k`.`CONSTRAINT_SCHEMA`
                              AND `r`.`CONSTRAINT_NAME` = `k`.`CONSTRAINT_NAME`
                            WHERE `k`.`TABLE_SCHEMA` = DATABASE()
                              AND `k`.`TABLE_NAME` = `input`.`target_table`
                              AND `k`.`REFERENCED_TABLE_NAME` IS NOT NULL
                       ), JSON_ARRAY()) AS `foreign_keys_json`
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
        $engine = $metadata->table_engine ?? null;
        $collation = $metadata->table_collation ?? null;
        if (($metadata->ownership_marker ?? null) !== self::OWNERSHIP_MARKER
            || !is_string($engine) || strtolower($engine) !== 'innodb'
            || !is_string($collation) || strtolower($collation) !== 'utf8mb4_unicode_ci') {
            return false;
        }

        return $this->normalizedColumns($metadata->columns_json ?? null) === $this->expectedColumns($table)
            && $this->normalizedIndexes($metadata->indexes_json ?? null) === $this->expectedIndexes($table)
            && $this->normalizedForeignKeys($metadata->foreign_keys_json ?? null) === $this->expectedForeignKeys($table);
    }

    /** @return list<string> */
    private function normalizedColumns(mixed $json): array
    {
        $rows = $this->jsonRows($json);
        $normalized = [];
        foreach ($rows as $row) {
            $ordinal = $this->canonicalInteger($row['ordinal'] ?? null);
            if ($ordinal === null) {
                return [];
            }
            $normalized[] = implode('|', [
                $ordinal,
                strtolower($this->stringValue($row, 'name')),
                $this->normalizeColumnType($this->stringValue($row, 'type')),
                strtoupper($this->stringValue($row, 'nullable')),
                $this->normalizeDefault($row['default'] ?? null),
                strtolower($this->nullableString($row['collation'] ?? null)),
                $this->normalizeExtra($this->stringValue($row, 'extra')),
            ]);
        }
        sort($normalized, SORT_STRING);

        return $normalized;
    }

    /** @return list<string> */
    private function normalizedIndexes(mixed $json): array
    {
        $rows = $this->jsonRows($json);
        $normalized = [];
        foreach ($rows as $row) {
            $nonUnique = $this->canonicalInteger($row['non_unique'] ?? null);
            $sequence = $this->canonicalInteger($row['sequence'] ?? null);
            if ($nonUnique === null || $sequence === null) {
                return [];
            }
            $subPart = $row['sub_part'] ?? null;
            $normalized[] = implode('|', [
                strtolower($this->stringValue($row, 'name')),
                $nonUnique,
                $sequence,
                strtolower($this->stringValue($row, 'column')),
                $subPart === null ? '<null>' : ($this->canonicalInteger($subPart) ?? '<invalid>'),
                strtoupper($this->stringValue($row, 'collation')),
                strtoupper($this->stringValue($row, 'type')),
            ]);
        }
        sort($normalized, SORT_STRING);

        return $normalized;
    }

    /** @return list<string> */
    private function normalizedForeignKeys(mixed $json): array
    {
        $rows = $this->jsonRows($json);
        $normalized = [];
        foreach ($rows as $row) {
            $sequence = $this->canonicalInteger($row['sequence'] ?? null);
            if ($sequence === null) {
                return [];
            }
            $normalized[] = implode('|', [
                strtolower($this->stringValue($row, 'name')),
                strtolower($this->stringValue($row, 'column')),
                strtolower($this->stringValue($row, 'referenced_table')),
                strtolower($this->stringValue($row, 'referenced_column')),
                $sequence,
                strtoupper($this->stringValue($row, 'update_rule')),
                strtoupper($this->stringValue($row, 'delete_rule')),
            ]);
        }
        sort($normalized, SORT_STRING);

        return $normalized;
    }

    /** @return list<array<string, mixed>> */
    private function jsonRows(mixed $json): array
    {
        if (!is_string($json)) {
            return [];
        }
        try {
            $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }
        if (!is_array($decoded) || !array_is_list($decoded)) {
            return [];
        }
        $rows = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                return [];
            }
            $normalizedRow = [];
            foreach ($row as $key => $value) {
                if (!is_string($key)) {
                    return [];
                }
                $normalizedRow[$key] = $value;
            }
            $rows[] = $normalizedRow;
        }

        return $rows;
    }

    /** @param array<string, mixed> $row */
    private function stringValue(array $row, string $key): string
    {
        $value = $row[$key] ?? null;

        return is_string($value) ? $value : '';
    }

    private function nullableString(mixed $value): string
    {
        return is_string($value) ? $value : '<null>';
    }

    private function canonicalInteger(mixed $value): ?string
    {
        if (is_int($value)) {
            return (string) $value;
        }
        if (!is_string($value) || preg_match('/^(?:0|-?[1-9][0-9]*)$/D', $value) !== 1) {
            return null;
        }
        $integer = (int) $value;

        return (string) $integer === $value ? $value : null;
    }

    private function normalizeColumnType(string $type): string
    {
        $type = strtolower(preg_replace('/\s+/u', ' ', trim($type)) ?? '');

        return preg_replace('/\b(bigint|int|tinyint)\(\d+\)/', '$1', $type) ?? '';
    }

    private function normalizeDefault(mixed $default): string
    {
        if ($default === null) {
            return '<null>';
        }
        if (!is_string($default) && !is_int($default)) {
            return '<invalid>';
        }
        $value = trim((string) $default);
        if (strtolower($value) === 'null') {
            return '<null>';
        }
        if (strlen($value) >= 2 && $value[0] === "'" && $value[strlen($value) - 1] === "'") {
            $value = substr($value, 1, -1);
        }
        $value = strtolower($value);

        return preg_replace('/^current_timestamp(?:\(\))?$/', 'current_timestamp', $value) ?? '';
    }

    private function normalizeExtra(string $extra): string
    {
        $extra = strtolower($extra);
        $extra = str_replace(['default_generated', 'current_timestamp()'], ['', 'current_timestamp'], $extra);

        return trim(preg_replace('/\s+/u', ' ', $extra) ?? '');
    }

    /** @return list<string> */
    private function expectedColumns(string $table): array
    {
        $commonId = '1|id|bigint unsigned|NO|<null>|<null>|auto_increment';

        return match ($table) {
            'xplugin_mgd_ai_asset' => [
                $commonId,
                '2|asset_key|char(64)|NO|<null>|ascii_bin|',
                '3|local_path|varchar(1024)|NO|<null>|utf8mb4_unicode_ci|',
                "4|status|enum('unreviewed','none','generated','partially-generated','modified','deepfake')|NO|unreviewed|utf8mb4_unicode_ci|",
                "5|position|enum('top-left','top-right','bottom-left','bottom-right')|NO|bottom-right|utf8mb4_unicode_ci|",
                "6|theme|enum('auto','light','dark')|NO|auto|utf8mb4_unicode_ci|",
                '7|created_at|timestamp|NO|current_timestamp|<null>|',
                '8|updated_at|timestamp|NO|current_timestamp|<null>|on update current_timestamp',
            ],
            'xplugin_mgd_ai_usage' => [
                $commonId,
                '2|asset_id|bigint unsigned|NO|<null>|<null>|',
                "3|source_type|enum('product','category','manufacturer','banner','opc','custom-local-manual','unknown')|NO|unknown|utf8mb4_unicode_ci|",
                '4|source_reference|varchar(255)|NO|<null>|utf8mb4_bin|',
                '5|source_reference_hash|char(64)|NO|<null>|ascii_bin|',
                '6|context|varchar(500)|YES|<null>|utf8mb4_unicode_ci|',
                '7|last_seen_at|timestamp|NO|current_timestamp|<null>|',
                '8|is_present|tinyint unsigned|NO|1|<null>|',
            ],
            'xplugin_mgd_ai_philosophy' => [
                $commonId,
                '2|language|varchar(12)|NO|<null>|ascii_bin|',
                '3|content|text|NO|<null>|utf8mb4_unicode_ci|',
                '4|created_at|timestamp|NO|current_timestamp|<null>|',
                '5|updated_at|timestamp|NO|current_timestamp|<null>|on update current_timestamp',
            ],
            default => [],
        };
    }

    /** @return list<string> */
    private function expectedIndexes(string $table): array
    {
        $rows = match ($table) {
            'xplugin_mgd_ai_asset' => [
                'idx_mgd_ai_asset_status|1|1|status|<null>|A|BTREE',
                'primary|0|1|id|<null>|A|BTREE',
                'uq_mgd_ai_asset_key|0|1|asset_key|<null>|A|BTREE',
            ],
            'xplugin_mgd_ai_usage' => [
                'idx_mgd_ai_usage_present_seen|1|1|is_present|<null>|A|BTREE',
                'idx_mgd_ai_usage_present_seen|1|2|last_seen_at|<null>|A|BTREE',
                'primary|0|1|id|<null>|A|BTREE',
                'uq_mgd_ai_usage_source|0|1|asset_id|<null>|A|BTREE',
                'uq_mgd_ai_usage_source|0|2|source_type|<null>|A|BTREE',
                'uq_mgd_ai_usage_source|0|3|source_reference_hash|<null>|A|BTREE',
            ],
            'xplugin_mgd_ai_philosophy' => [
                'primary|0|1|id|<null>|A|BTREE',
                'uq_mgd_ai_philosophy_language|0|1|language|<null>|A|BTREE',
            ],
            default => [],
        };
        sort($rows, SORT_STRING);

        return $rows;
    }

    /** @return list<string> */
    private function expectedForeignKeys(string $table): array
    {
        return $table === 'xplugin_mgd_ai_usage'
            ? ['fk_mgd_ai_usage_asset|asset_id|xplugin_mgd_ai_asset|id|1|RESTRICT|CASCADE']
            : [];
    }
}
