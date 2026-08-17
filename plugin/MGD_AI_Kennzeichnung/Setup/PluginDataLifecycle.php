<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Setup;

use JTL\DB\DbInterface;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\ConfirmationClaimRepository;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\ConfirmationClaimSchemaGuard;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\SchemaOwnershipGuard;
use RuntimeException;
use Throwable;

/**
 * Entfernt auf ausdrücklichen Wunsch ausschließlich nachweislich eigene Daten.
 *
 * Vor dem ersten DROP werden alle vorhandenen Tabellen vollständig geprüft.
 * Eine globale Datenbanksperre schützt den kurzen Vorgang vor konkurrierenden
 * Plugin-Prozessen. Fremde oder veränderte Tabellen bleiben unangetastet.
 */
final class PluginDataLifecycle
{
    private const LOCK_NAME = 'mgd-ai-kennzeichnung-jtl-uninstall-v1';

    /** @var list<string> Löschreihenfolge berücksichtigt den Fremdschlüssel auf die Asset-Tabelle. */
    private const CORE_TABLES = [
        'xplugin_mgd_ai_usage',
        'xplugin_mgd_ai_philosophy',
        'xplugin_mgd_ai_asset',
    ];

    public function __construct(private readonly DbInterface $db) {}

    public function uninstalled(bool $deleteData): void
    {
        if (!$deleteData) {
            return;
        }

        $sperre = $this->db->getSingleObject(
            'SELECT GET_LOCK(:lock_name, :timeout_seconds) AS `acquired`',
            ['lock_name' => self::LOCK_NAME, 'timeout_seconds' => 10],
        );
        if ($this->integerMetadata($sperre, 'acquired') !== 1) {
            throw new RuntimeException('Die sichere Deinstallationssperre konnte nicht erlangt werden.');
        }

        $fehler = null;
        try {
            $this->removeOwnedTables();
        } catch (Throwable $exception) {
            $fehler = $exception;
        } finally {
            try {
                $freigabe = $this->db->getSingleObject(
                    'SELECT RELEASE_LOCK(:lock_name) AS `released`',
                    ['lock_name' => self::LOCK_NAME],
                );
                if ($this->integerMetadata($freigabe, 'released') !== 1) {
                    throw new RuntimeException('Die sichere Deinstallationssperre konnte nicht freigegeben werden.');
                }
            } catch (Throwable $lockFehler) {
                $fehler = new RuntimeException(
                    'Die Deinstallation konnte ihre Datenbanksperre nicht sicher abschließen.',
                    0,
                    $fehler ?? $lockFehler,
                );
            }
        }

        if ($fehler instanceof Throwable) {
            throw $fehler;
        }
    }

    private function removeOwnedTables(): void
    {
        $schema = new SchemaOwnershipGuard($this->db);
        $claim = new ConfirmationClaimSchemaGuard($this->db);
        $vorhanden = [];

        /* Vollständiger Preflight: Vor seinem Abschluss wird keine Tabelle verändert. */
        foreach (self::CORE_TABLES as $tabelle) {
            $vorhanden[$tabelle] = $schema->exists($tabelle);
            if ($vorhanden[$tabelle]) {
                $schema->assertOwned($tabelle);
            }
        }
        $claimVorhanden = $claim->exists();
        if ($claimVorhanden) {
            $claim->assertOwned();
        }

        if ($claimVorhanden) {
            $claim->assertOwned();
            $this->db->getAffectedRows('DROP TABLE `' . ConfirmationClaimRepository::TABLE . '`');
        }
        foreach (self::CORE_TABLES as $tabelle) {
            if (!$vorhanden[$tabelle]) {
                continue;
            }
            /* Erneute Prüfung direkt vor jedem irreversiblen Schritt. */
            $schema->assertOwned($tabelle);
            $this->db->getAffectedRows('DROP TABLE `' . $tabelle . '`');
        }
    }

    private function integerMetadata(?object $metadata, string $field): int
    {
        $wert = $metadata === null ? null : ($metadata->{$field} ?? null);
        if (is_int($wert)) {
            return $wert;
        }
        if (!is_string($wert) || preg_match('/^(?:0|-?[1-9][0-9]*)$/D', $wert) !== 1) {
            return 0;
        }
        $ganzzahl = (int) $wert;

        return (string) $ganzzahl === $wert ? $ganzzahl : 0;
    }
}
