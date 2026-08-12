<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database;

use JTL\DB\DbInterface;
use RuntimeException;

/**
 * Prüft Eigentumsmarker und vollständige Semantik der flüchtigen Claim-Tabelle.
 * Ein bloß gleicher Tabellenname genügt ausdrücklich nicht als Eigentumsbeweis.
 */
final class ConfirmationClaimSchemaGuard
{
    public const OWNERSHIP_MARKER = 'mgd-ai-kennzeichnung-jtl-confirmation-v1';

    public function __construct(private readonly DbInterface $db) {}

    public function exists(): bool
    {
        return $this->tableMetadata() !== null;
    }

    public function assertOwned(): void
    {
        $table = $this->tableMetadata();
        if ($table === null
            || ($table->ownership_marker ?? null) !== self::OWNERSHIP_MARKER
            || strtolower($this->string($table->table_engine ?? null)) !== 'innodb'
            || strtolower($this->string($table->table_collation ?? null)) !== 'utf8mb4_unicode_ci'
            || $this->columns() !== $this->expectedColumns()
            || $this->indexes() !== $this->expectedIndexes()
            || $this->foreignKeys() !== []) {
            throw new RuntimeException('Die Bestätigungs-Claim-Tabelle ist fremd oder besitzt ein unerwartetes Schema.');
        }
    }

    private function tableMetadata(): ?object
    {
        return $this->db->getSingleObject(
            <<<'SQL'
                SELECT `TABLE_COMMENT` AS `ownership_marker`, `ENGINE` AS `table_engine`,
                       `TABLE_COLLATION` AS `table_collation`
                  FROM `INFORMATION_SCHEMA`.`TABLES`
                 WHERE `TABLE_SCHEMA` = DATABASE()
                   AND `TABLE_NAME` = :table_name
                SQL,
            ['table_name' => ConfirmationClaimRepository::TABLE],
        );
    }

    /** @return list<string> */
    private function columns(): array
    {
        $rows = $this->db->getObjects(
            <<<'SQL'
                SELECT `COLUMN_NAME` AS `name`, `COLUMN_TYPE` AS `type`, `IS_NULLABLE` AS `nullable`,
                       `COLUMN_DEFAULT` AS `default`, `COLLATION_NAME` AS `collation`,
                       `EXTRA` AS `extra`, `ORDINAL_POSITION` AS `ordinal`
                  FROM `INFORMATION_SCHEMA`.`COLUMNS`
                 WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = :table_name
                 ORDER BY `ORDINAL_POSITION`
                SQL,
            ['table_name' => ConfirmationClaimRepository::TABLE],
        );
        $result = [];
        foreach ($rows as $row) {
            $extra = strtolower(preg_replace('/\s+/', ' ', trim($this->string($row->extra ?? null))) ?? '');
            /* MySQL 8 meldet DEFAULT_GENERATED, MariaDB lässt dieses reine Metadatenflag leer. */
            if (($row->name ?? null) === 'claimed_at' && $extra === 'default_generated') {
                $extra = '';
            }
            $result[] = implode('|', [
                $this->integer($row->ordinal ?? null), strtolower($this->string($row->name ?? null)),
                strtolower($this->string($row->type ?? null)), strtoupper($this->string($row->nullable ?? null)),
                strtolower($this->string($row->default ?? null)), strtolower($this->string($row->collation ?? null)),
                $extra,
            ]);
        }

        return $result;
    }

    /** @return list<string> */
    private function indexes(): array
    {
        $rows = $this->db->getObjects(
            <<<'SQL'
                SELECT `INDEX_NAME` AS `name`, `NON_UNIQUE` AS `non_unique`, `SEQ_IN_INDEX` AS `sequence`,
                       `COLUMN_NAME` AS `column`, `SUB_PART` AS `sub_part`, `COLLATION` AS `collation`,
                       `INDEX_TYPE` AS `type`
                  FROM `INFORMATION_SCHEMA`.`STATISTICS`
                 WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = :table_name
                 ORDER BY `INDEX_NAME`, `SEQ_IN_INDEX`
                SQL,
            ['table_name' => ConfirmationClaimRepository::TABLE],
        );
        $result = [];
        foreach ($rows as $row) {
            $result[] = implode('|', [
                strtolower($this->string($row->name ?? null)), $this->integer($row->non_unique ?? null),
                $this->integer($row->sequence ?? null), strtolower($this->string($row->column ?? null)),
                ($row->sub_part ?? null) === null ? '<null>' : $this->integer($row->sub_part),
                strtoupper($this->string($row->collation ?? null)), strtoupper($this->string($row->type ?? null)),
            ]);
        }
        sort($result, SORT_STRING);

        return $result;
    }

    /** @return list<object> */
    private function foreignKeys(): array
    {
        return array_values($this->db->getObjects(
            <<<'SQL'
                SELECT `REFERENCED_TABLE_NAME`
                  FROM `INFORMATION_SCHEMA`.`KEY_COLUMN_USAGE`
                 WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = :table_name
                   AND `REFERENCED_TABLE_NAME` IS NOT NULL
                SQL,
            ['table_name' => ConfirmationClaimRepository::TABLE],
        ));
    }

    /** @return list<string> */
    private function expectedColumns(): array
    {
        return [
            '1|token_hash|char(64)|NO||ascii_bin|',
            '2|subject_hash|char(64)|NO||ascii_bin|',
            '3|expires_at|datetime(6)|NO|||',
            '4|claimed_at|timestamp(6)|NO|current_timestamp(6)||',
        ];
    }

    /** @return list<string> */
    private function expectedIndexes(): array
    {
        return [
            'idx_mgd_ai_confirmation_expires|1|1|expires_at|<null>|A|BTREE',
            'primary|0|1|token_hash|<null>|A|BTREE',
        ];
    }

    private function string(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }

    private function integer(mixed $value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }

        return is_string($value) && preg_match('/^(?:0|[1-9][0-9]*)$/D', $value) === 1 ? $value : '<invalid>';
    }
}
