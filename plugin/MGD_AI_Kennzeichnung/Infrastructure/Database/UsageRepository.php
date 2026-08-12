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

        $reference = $this->plainText($sourceReference, 255);
        if ($reference === '') {
            throw new RuntimeException('Die technische Quellenreferenz darf nicht leer sein.');
        }
        $normalSource = AssetSource::fromInput($sourceType)->value;
        $normalContext = $this->plainText($context, 500);

        $this->db->getAffectedRows(
            <<<'SQL'
                INSERT INTO `xplugin_mgd_ai_usage`
                    (`asset_id`, `source_type`, `source_reference`, `context`, `last_seen_at`, `is_present`)
                VALUES
                    (:asset_id, :source_type, :source_reference, :context, CURRENT_TIMESTAMP, :is_present)
                ON DUPLICATE KEY UPDATE
                    `context` = VALUES(`context`),
                    `last_seen_at` = CURRENT_TIMESTAMP,
                    `is_present` = VALUES(`is_present`)
                SQL,
            [
                'asset_id' => $assetId,
                'source_type' => $normalSource,
                'source_reference' => $reference,
                'context' => $normalContext === '' ? null : $normalContext,
                'is_present' => $present ? 1 : 0,
            ],
        );
    }

    private function plainText(mixed $input, int $maximum): string
    {
        if (!is_string($input)) {
            return '';
        }

        $text = html_entity_decode(strip_tags($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', str_replace("\0", '', $text)) ?? '';

        return mb_substr(trim($text), 0, $maximum);
    }
}
