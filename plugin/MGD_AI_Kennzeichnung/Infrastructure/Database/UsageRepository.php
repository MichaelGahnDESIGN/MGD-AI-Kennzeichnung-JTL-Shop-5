<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database;

use JTL\DB\DbInterface;
use Plugin\MGD_AI_Kennzeichnung\Domain\AssetSource;
use RuntimeException;

/** Speichert minimierte technische Fundstellen eines Assets idempotent. */
final class UsageRepository
{
    private const TABLE = 'xplugin_mgd_ai_usage';

    private readonly SchemaOwnershipGuard $ownership;

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
    ): void {
        $this->ownership->assertOwned(self::TABLE);
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
