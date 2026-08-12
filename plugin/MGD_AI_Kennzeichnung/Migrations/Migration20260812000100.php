<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\SchemaOwnershipGuard;
use RuntimeException;
use Throwable;

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
        $lockName = 'mgd-ai-kennzeichnung-jtl-v1-schema';
        $lock = $this->getDB()->getSingleObject(
            'SELECT GET_LOCK(:lock_name, :timeout_seconds) AS `acquired`',
            ['lock_name' => $lockName, 'timeout_seconds' => 10],
        );
        if ($this->integerMetadata($lock, 'acquired') !== 1) {
            throw new RuntimeException('Exklusive Schema-Sperre konnte nicht erlangt werden.');
        }

        /** @var list<string> $created */
        $created = [];
        $pendingFailure = null;
        try {
            /** @var array<string, bool> $missing */
            $missing = [];
            foreach (self::TABLES as $table) {
                $exists = $guard->exists($table);
                if ($exists) {
                    $guard->assertOwned($table);
                }
                $missing[$table] = !$exists;
            }

            foreach ($this->schemaStatements() as $table => $statement) {
                if (!$missing[$table]) {
                    continue;
                }
                $this->getDB()->getAffectedRows($statement);
                $created[] = $table;
                /* Postcondition schützt auch vor einer TOCTOU-Fremdtabelle. */
                $guard->assertOwned($table);
            }
        } catch (Throwable $fehler) {
            $pendingFailure = $fehler;
            try {
                foreach (array_reverse($created) as $table) {
                    $guard->assertOwned($table);
                    $this->getDB()->getAffectedRows($this->dropStatement($table));
                }
            } catch (Throwable $cleanupFehler) {
                $pendingFailure = new RuntimeException(
                    'Migration fehlgeschlagen; sichere kompensierende Bereinigung ebenfalls fehlgeschlagen: '
                    . $cleanupFehler->getMessage(),
                    0,
                    $fehler,
                );
            }
        } finally {
            try {
                $release = $this->getDB()->getSingleObject(
                    'SELECT RELEASE_LOCK(:lock_name) AS `released`',
                    ['lock_name' => $lockName],
                );
                if ($this->integerMetadata($release, 'released') !== 1) {
                    throw new RuntimeException('Exklusive Schema-Sperre konnte nicht freigegeben werden.');
                }
            } catch (Throwable $lockFehler) {
                $pendingFailure = new RuntimeException(
                    'Fehler beim Freigeben der Schema-Sperre: ' . $lockFehler->getMessage(),
                    0,
                    $pendingFailure,
                );
            }
        }

        if ($pendingFailure instanceof Throwable) {
            throw $pendingFailure;
        }
    }

    /**
     * Das Löschen von Daten gehört noch nicht zu diesem Entwicklungsschritt.
     * Eine spätere Deinstallation muss vor jedem DROP assertOwned() verwenden.
     */
    public function down(): void {}

    /** @return array<string, string> */
    private function schemaStatements(): array
    {
        $marker = SchemaOwnershipGuard::OWNERSHIP_MARKER;

        return [
            'xplugin_mgd_ai_asset' => <<<SQL
                CREATE TABLE `xplugin_mgd_ai_asset` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `asset_key` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
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
            'xplugin_mgd_ai_usage' => <<<SQL
                CREATE TABLE `xplugin_mgd_ai_usage` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `asset_id` BIGINT UNSIGNED NOT NULL,
                    `source_type` ENUM('product','category','manufacturer','banner','opc','custom-local-manual','unknown') NOT NULL DEFAULT 'unknown',
                    `source_reference` VARCHAR(255) COLLATE utf8mb4_bin NOT NULL,
                    `source_reference_hash` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
                    `context` VARCHAR(500) NULL,
                    `last_seen_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `is_present` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uq_mgd_ai_usage_source` (`asset_id`, `source_type`, `source_reference_hash`),
                    KEY `idx_mgd_ai_usage_present_seen` (`is_present`, `last_seen_at`),
                    CONSTRAINT `fk_mgd_ai_usage_asset` FOREIGN KEY (`asset_id`)
                        REFERENCES `xplugin_mgd_ai_asset` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='{$marker}'
                SQL,
            'xplugin_mgd_ai_philosophy' => <<<SQL
                CREATE TABLE `xplugin_mgd_ai_philosophy` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `language` VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
                    `content` TEXT NOT NULL,
                    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uq_mgd_ai_philosophy_language` (`language`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='{$marker}'
                SQL,
        ];
    }

    private function dropStatement(string $table): string
    {
        return match ($table) {
            'xplugin_mgd_ai_usage' => 'DROP TABLE `xplugin_mgd_ai_usage`',
            'xplugin_mgd_ai_philosophy' => 'DROP TABLE `xplugin_mgd_ai_philosophy`',
            'xplugin_mgd_ai_asset' => 'DROP TABLE `xplugin_mgd_ai_asset`',
            default => throw new RuntimeException('Unbekannte Tabelle darf nicht entfernt werden.'),
        };
    }

    private function integerMetadata(?object $metadata, string $field): int
    {
        if ($metadata === null) {
            return 0;
        }
        $value = $metadata->{$field} ?? null;
        if (is_int($value)) {
            return $value;
        }
        if (!is_string($value) || preg_match('/^(?:0|-?[1-9][0-9]*)$/D', $value) !== 1) {
            return 0;
        }

        /*
         * JTL-Shop 5.7.2 aktiviert PDO::ATTR_STRINGIFY_FETCHES. Deshalb kommen
         * GET_LOCK/RELEASE_LOCK regulär als "1" zurück. Der Rückvergleich
         * verhindert zugleich Überlauf, führende Nullen, Dezimalformen,
         * Leerzeichen oder andere mehrdeutige numerische Darstellungen.
         */
        $integer = (int) $value;
        if ((string) $integer !== $value) {
            return 0;
        }

        return $integer;
    }
}
