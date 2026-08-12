<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database;

use JTL\DB\DbInterface;
use Plugin\MGD_AI_Kennzeichnung\Domain\AssetSource;
use RuntimeException;
use Throwable;

/** Speichert minimierte technische Fundstellen eines Assets idempotent. */
final class UsageRepository
{
    private const TABLE = 'xplugin_mgd_ai_usage';
    private readonly SchemaOwnershipGuard $ownership;
    private bool $reconciling = false;
    private bool $scanOwnershipConfirmed = false;

    public function __construct(private readonly DbInterface $db)
    {
        $this->ownership = new SchemaOwnershipGuard($db);
    }

    public function upsert(
        int $assetId,
        mixed $sourceType,
        mixed $sourceReference,
        mixed $context = null,
        bool $present = true,
    ): bool {
        if (!$this->reconciling) {
            $this->ownership->assertOwned(self::TABLE);
        }
        if ($assetId < 1) {
            throw new RuntimeException('Die technische Asset-ID muss positiv sein.');
        }

        if (!is_string($sourceReference)) {
            throw new RuntimeException('Die technische Quellenreferenz darf nicht leer sein.');
        }
        $reference = str_replace("\0", '', $sourceReference);
        if (trim($reference) === '') {
            throw new RuntimeException('Die technische Quellenreferenz darf nicht leer sein.');
        }
        if (mb_strlen($reference) > 255) {
            throw new RuntimeException('Die technische Quellenreferenz ist zu lang.');
        }
        $normalSource = AssetSource::fromInput($sourceType)->value;
        $normalContext = $this->safePlainText($context, 500);

        $this->db->getAffectedRows(
            <<<'SQL'
                INSERT INTO `xplugin_mgd_ai_usage`
                    (`asset_id`, `source_type`, `source_reference`, `source_reference_hash`, `context`, `last_seen_at`, `is_present`)
                VALUES
                    (:asset_id, :source_type, :source_reference, :source_reference_hash, :context, CURRENT_TIMESTAMP, :is_present)
                ON DUPLICATE KEY UPDATE
                    `context` = VALUES(`context`),
                    `last_seen_at` = CURRENT_TIMESTAMP,
                    `is_present` = VALUES(`is_present`)
                SQL,
            [
                'asset_id' => $assetId,
                'source_type' => $normalSource,
                'source_reference' => $reference,
                'source_reference_hash' => hash('sha256', $reference),
                'context' => $normalContext === '' ? null : $normalContext,
                'is_present' => $present ? 1 : 0,
            ],
        );

        if (!$this->reconciling) {
            return true;
        }

        $inserted = $this->db->getAffectedRows(
            <<<'SQL'
                INSERT IGNORE INTO `tmp_mgd_ai_scan_usage`
                    (`asset_id`, `source_type`, `source_reference_hash`)
                VALUES
                    (:asset_id, :source_type, :source_reference_hash)
                SQL,
            [
                'asset_id' => $assetId,
                'source_type' => $normalSource,
                'source_reference_hash' => hash('sha256', $reference),
            ],
        );

        return $inserted === 1;
    }

    /**
     * Führt einen vollständigen Scan atomar aus. Eine temporäre, indizierte
     * Datenbanktabelle hält nur technische Schlüssel und ersetzt eine potenziell
     * unbegrenzte PHP-Deduplizierung. Erst nachdem der Callback erfolgreich
     * beendet ist, werden nicht mehr gesehene Nutzungen als fehlend markiert.
     *
     * @template T
     * @param list<AssetSource> $scannedSources
     * @param callable(): T $scan
     * @return T
     */
    public function reconcile(array $scannedSources, callable $scan): mixed
    {
        $this->assertAutomaticSourceScope($scannedSources);
        $this->assertReadyForScan();
        if ($this->reconciling) {
            throw new RuntimeException('Ein Bildabgleich darf nicht verschachtelt gestartet werden.');
        }
        /*
         * NiceDB 5.7.2 zählt verschachtelte beginTransaction()-Aufrufe intern,
         * rollback() setzt jedoch den Zähler auf null und rollt die physische
         * PDO-Transaktion vollständig zurück. Deshalb darf der Scanner niemals
         * innerhalb einer fremden äußeren Transaktion beginnen.
         */
        if ($this->db->getPDO()->inTransaction()) {
            throw new RuntimeException('Der Bildabgleich darf nicht innerhalb einer bereits aktiven Transaktion starten.');
        }
        if (!$this->db->beginTransaction()) {
            throw new RuntimeException('Datenbanktransaktion für den Bildabgleich konnte nicht gestartet werden.');
        }

        $cleanupRequired = false;
        $error = null;
        /** @var array{value: T}|null $outcome */
        $outcome = null;
        try {
            /* Auch ein während CREATE geworfener Treiberfehler kann die Tabelle bereits angelegt haben. */
            $cleanupRequired = true;
            $this->db->getAffectedRows(
                <<<'SQL'
                    CREATE TEMPORARY TABLE `tmp_mgd_ai_scan_usage` (
                        `asset_id` BIGINT UNSIGNED NOT NULL,
                        `source_type` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
                        `source_reference_hash` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
                        PRIMARY KEY (`asset_id`, `source_type`, `source_reference_hash`)
                    ) ENGINE=InnoDB
                    SQL,
            );
            $this->reconciling = true;
            $outcome = ['value' => $scan()];
            $this->reconciling = false;

            foreach ($scannedSources as $source) {
                $this->db->getAffectedRows(
                    <<<'SQL'
                    UPDATE `xplugin_mgd_ai_usage` AS `usage`
                    LEFT JOIN `tmp_mgd_ai_scan_usage` AS `seen`
                      ON `seen`.`asset_id` = `usage`.`asset_id`
                     AND `seen`.`source_type` = `usage`.`source_type`
                     AND `seen`.`source_reference_hash` = `usage`.`source_reference_hash`
                       SET `usage`.`is_present` = 0
                     WHERE `seen`.`asset_id` IS NULL
                       AND `usage`.`is_present` = 1
                       AND `usage`.`source_type` = :source_type
                    SQL,
                    ['source_type' => $source->value],
                );
            }
            $this->db->getAffectedRows('DROP TEMPORARY TABLE `tmp_mgd_ai_scan_usage`');
            $cleanupRequired = false;
            if (!$this->db->commit()) {
                throw new RuntimeException('Bildabgleich konnte nicht bestätigt werden.');
            }
        } catch (Throwable $caught) {
            $this->reconciling = false;
            $error = $caught;
            try {
                if (!$this->db->rollback()) {
                    throw new RuntimeException('Datenbank-Rollback meldete false.');
                }
            } catch (Throwable $rollbackError) {
                $error = new RuntimeException(
                    'Rollback nach fehlgeschlagenem Bildabgleich ist ebenfalls fehlgeschlagen: '
                    . $rollbackError->getMessage(),
                    0,
                    $caught,
                );
            }
        } finally {
            $this->reconciling = false;
            if ($cleanupRequired) {
                try {
                    $this->db->getAffectedRows('DROP TEMPORARY TABLE IF EXISTS `tmp_mgd_ai_scan_usage`');
                } catch (Throwable $cleanupError) {
                    $error = new RuntimeException(
                        'Bereinigung der temporären Scantabelle ist fehlgeschlagen: ' . $cleanupError->getMessage(),
                        0,
                        $error,
                    );
                }
            }
        }

        if ($error !== null) {
            throw $error;
        }
        if ($outcome === null) {
            throw new RuntimeException('Bildabgleich endete ohne Ergebnis.');
        }

        return $outcome['value'];
    }

    /** @param list<AssetSource> $sources */
    private function assertAutomaticSourceScope(array $sources): void
    {
        $allowed = [
            AssetSource::Product,
            AssetSource::Category,
            AssetSource::Manufacturer,
            AssetSource::Banner,
            AssetSource::Opc,
        ];
        $seen = [];
        foreach ($sources as $source) {
            if (!in_array($source, $allowed, true) || isset($seen[$source->value])) {
                throw new RuntimeException('Die Liste vollständig gescannter Quellen ist ungültig oder doppelt.');
            }
            $seen[$source->value] = true;
        }
        if ($seen === []) {
            throw new RuntimeException('Die Liste vollständig gescannter Quellen darf nicht leer sein.');
        }
    }

    /** Prüft den Usage-Tabellenvertrag einmal vor dem atomaren Scanlauf. */
    public function assertReadyForScan(): void
    {
        if (!$this->scanOwnershipConfirmed) {
            $this->ownership->assertOwned(self::TABLE);
            $this->scanOwnershipConfirmed = true;
        }
    }

    /**
     * Wandelt optionalen Kontext in reinen Text um. Die Reihenfolge entspricht
     * der Philosophie-Härtung: erst kontrolliert dekodieren, dann aktive Blöcke
     * und sämtliche Tags entfernen, danach niemals erneut dekodieren.
     */
    private function safePlainText(mixed $input, int $maximum): string
    {
        if (!is_string($input)) {
            return '';
        }

        $decoded = mb_substr(str_replace("\0", '', $input), 0, 5000);
        for ($durchlauf = 0; $durchlauf < 10; ++$durchlauf) {
            $next = html_entity_decode($this->decodeNumericTagEntities($decoded), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($next === $decoded) {
                break;
            }
            $decoded = $next;
        }
        if ($this->containsMarkupEntity($decoded)) {
            return '';
        }

        $ohneAktiveBloecke = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#isu', '', $decoded) ?? '';
        $text = preg_replace('/\s+/u', ' ', strip_tags($ohneAktiveBloecke)) ?? '';

        return mb_substr(trim($text), 0, $maximum);
    }

    private function decodeNumericTagEntities(string $text): string
    {
        return preg_replace_callback(
            '/&#0*60;?(?![0-9])|&#x0*3c;?|&#0*62;?(?![0-9])|&#x0*3e;?/iu',
            static fn(array $match): string => str_contains(strtolower($match[0]), '3c')
                || preg_match('/60/', $match[0]) === 1 ? '<' : '>',
            $text,
        ) ?? '';
    }

    private function containsMarkupEntity(string $text): bool
    {
        return preg_match(
            '/&(?:(?:amp|#0*38|#x0*26);)*(?:lt|gt|#0*(?:60|62);?|#x0*(?:3c|3e);?)/iu',
            $text,
        ) === 1;
    }
}
