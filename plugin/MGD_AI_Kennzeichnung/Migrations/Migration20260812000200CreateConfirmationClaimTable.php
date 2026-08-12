<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\ConfirmationClaimRepository;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\ConfirmationClaimSchemaGuard;
use RuntimeException;
use Throwable;

/**
 * Ergänzt das fachliche Dreitabellen-Grundmodell um flüchtige
 * Sicherheitsinfrastruktur für atomare Admin-Einmalbestätigungen.
 */
class Migration20260812000200CreateConfirmationClaimTable extends Migration implements IMigration
{
    private const LOCK_NAME = 'mgd-ai-confirmation-claim-schema-v1';

    public function up(): void
    {
        $db = $this->getDB();
        $lock = $db->getSingleObject(
            'SELECT GET_LOCK(:lock_name, :timeout_seconds) AS `acquired`',
            ['lock_name' => self::LOCK_NAME, 'timeout_seconds' => 10],
        );
        if ($this->integerMetadata($lock, 'acquired') !== 1) {
            throw new RuntimeException('Exklusive Schema-Sperre für Bestätigungs-Claims konnte nicht erlangt werden.');
        }

        $guard = new ConfirmationClaimSchemaGuard($db);
        $created = false;
        $pendingFailure = null;
        try {
            if ($guard->exists()) {
                $guard->assertOwned();
            } else {
                $db->getAffectedRows($this->createStatement());
                $created = true;
                /* Postcondition schließt eine TOCTOU-Kollision nach dem Preflight. */
                $guard->assertOwned();
            }
        } catch (Throwable $fehler) {
            $pendingFailure = $fehler;
            if ($created) {
                try {
                    $guard->assertOwned();
                    $db->getAffectedRows('DROP TABLE `xplugin_mgd_ai_confirmation_claim`');
                } catch (Throwable $cleanupFehler) {
                    $pendingFailure = new RuntimeException(
                        'Claim-Migration und sichere kompensierende Bereinigung sind fehlgeschlagen.',
                        0,
                        $cleanupFehler,
                    );
                }
            }
        } finally {
            try {
                $released = $db->getSingleObject(
                    'SELECT RELEASE_LOCK(:lock_name) AS `released`',
                    ['lock_name' => self::LOCK_NAME],
                );
                if ($this->integerMetadata($released, 'released') !== 1) {
                    throw new RuntimeException('Schema-Sperre für Bestätigungs-Claims konnte nicht freigegeben werden.');
                }
            } catch (Throwable $lockFehler) {
                $pendingFailure = new RuntimeException(
                    'Fehler beim Freigeben der Claim-Schema-Sperre.',
                    0,
                    $pendingFailure ?? $lockFehler,
                );
            }
        }

        if ($pendingFailure instanceof Throwable) {
            throw $pendingFailure;
        }
    }

    /** Sicherheits-Claims werden nicht automatisch destruktiv entfernt. */
    public function down(): void {}

    private function createStatement(): string
    {
        $marker = ConfirmationClaimSchemaGuard::OWNERSHIP_MARKER;

        return <<<SQL
            CREATE TABLE `xplugin_mgd_ai_confirmation_claim` (
                `token_hash` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
                `subject_hash` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
                `expires_at` DATETIME(6) NOT NULL,
                `claimed_at` TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                PRIMARY KEY (`token_hash`),
                KEY `idx_mgd_ai_confirmation_expires` (`expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='{$marker}'
            SQL;
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
        $integer = (int) $value;

        return (string) $integer === $value ? $integer : 0;
    }
}
