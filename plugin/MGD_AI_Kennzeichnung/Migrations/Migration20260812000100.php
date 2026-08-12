<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\SchemaOwnershipGuard;
use RuntimeException;

/** Erstellt ausschließlich die drei eigentumsmarkierten Plugin-Tabellen. */
final class Migration20260812000100 extends Migration implements IMigration
{
    /** @var list<string> */
    private const TABLES = [
        'xplugin_mgd_ai_asset',
        'xplugin_mgd_ai_usage',
        'xplugin_mgd_ai_philosophy',
    ];

    /**
     * Prüft zuerst sämtliche Namen auf Kollisionen. Dadurch kann eine fremde
     * dritte Tabelle nicht erst erkannt werden, nachdem zwei Tabellen bereits
     * erstellt oder verändert wurden.
     */
    public function up(): void
    {
        $guard = new SchemaOwnershipGuard($this->getDB());
        foreach (self::TABLES as $table) {
            if (!$guard->mayMutate($table)) {
                throw new RuntimeException(sprintf(
                    'Migration abgebrochen: Tabelle %s gehört nicht diesem Plugin.',
                    $table,
                ));
            }
        }

        foreach ($this->schemaStatements() as $statement) {
            $this->getDB()->getAffectedRows($statement);
        }
    }

    /**
     * Das Löschen von Daten gehört noch nicht zu diesem Entwicklungsschritt.
     * Eine spätere Deinstallation muss vor jedem DROP assertOwned() verwenden.
     */
    public function down(): void {}

    /** @return list<string> */
    private function schemaStatements(): array
    {
        $marker = SchemaOwnershipGuard::OWNERSHIP_MARKER;

        return [
            <<<SQL
                CREATE TABLE IF NOT EXISTS `xplugin_mgd_ai_asset` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `asset_key` VARCHAR(191) NOT NULL,
                    `local_path` VARCHAR(1024) NOT NULL,
                    `status` ENUM('unreviewed','none','generated','partially-generated','modified','deepfake') NOT NULL DEFAULT 'unreviewed',
                    `position` ENUM('top-left','top-right','bottom-left','bottom-right') NOT NULL DEFAULT 'bottom-right',
                    `theme` ENUM('auto','light','dark') NOT NULL DEFAULT 'auto',
                    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uq_mgd_ai_asset_key` (`asset_key`),
                    KEY `idx_mgd_ai_asset_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='{$marker}'
                SQL,
            <<<SQL
                CREATE TABLE IF NOT EXISTS `xplugin_mgd_ai_usage` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `asset_id` BIGINT UNSIGNED NOT NULL,
                    `source_type` ENUM('product','category','manufacturer','banner','opc','custom-local-manual','unknown') NOT NULL DEFAULT 'unknown',
                    `source_reference` VARCHAR(255) NOT NULL,
                    `context` VARCHAR(500) NULL,
                    `last_seen_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `is_present` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uq_mgd_ai_usage_source` (`asset_id`, `source_type`, `source_reference`),
                    KEY `idx_mgd_ai_usage_present_seen` (`is_present`, `last_seen_at`),
                    CONSTRAINT `fk_mgd_ai_usage_asset` FOREIGN KEY (`asset_id`)
                        REFERENCES `xplugin_mgd_ai_asset` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='{$marker}'
                SQL,
            <<<SQL
                CREATE TABLE IF NOT EXISTS `xplugin_mgd_ai_philosophy` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `language` VARCHAR(12) NOT NULL,
                    `content` TEXT NOT NULL,
                    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uq_mgd_ai_philosophy_language` (`language`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='{$marker}'
                SQL,
        ];
    }
}
