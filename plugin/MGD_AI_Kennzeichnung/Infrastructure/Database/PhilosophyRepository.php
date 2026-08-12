<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database;

use JTL\DB\DbInterface;
use RuntimeException;

/** Speichert je Sprache genau einen bereinigten Philosophie-Text. */
final class PhilosophyRepository
{
    private const TABLE = 'xplugin_mgd_ai_philosophy';

    private readonly SchemaOwnershipGuard $ownership;

    public function __construct(private readonly DbInterface $db)
    {
        $this->ownership = new SchemaOwnershipGuard($db);
    }

    public function upsert(mixed $language, mixed $content): void
    {
        $this->ownership->assertOwned(self::TABLE);
        $normalLanguage = is_string($language) ? strtolower(trim($language)) : '';
        if (preg_match('/^[a-z]{2}(?:-[a-z]{2})?$/', $normalLanguage) !== 1) {
            throw new RuntimeException('Die Sprache muss ein sicherer Sprachcode sein.');
        }

        $plainContent = $this->plainContent($content);
        if ($plainContent === '') {
            throw new RuntimeException('Der Philosophie-Text darf nicht leer sein.');
        }

        $this->db->getAffectedRows(
            <<<'SQL'
                INSERT INTO `xplugin_mgd_ai_philosophy`
                    (`language`, `content`, `created_at`, `updated_at`)
                VALUES
                    (:language, :content, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE
                    `content` = VALUES(`content`),
                    `updated_at` = CURRENT_TIMESTAMP
                SQL,
            ['language' => $normalLanguage, 'content' => $plainContent],
        );
    }

    private function plainContent(mixed $input): string
    {
        if (!is_string($input)) {
            return '';
        }

        $ohneAktiveBloecke = preg_replace(
            '#<(script|style)\b[^>]*>.*?</\1>#isu',
            '',
            $input,
        ) ?? '';
        $text = html_entity_decode(strip_tags($ohneAktiveBloecke), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[\t ]+/u', ' ', str_replace("\0", '', $text)) ?? '';
        $text = preg_replace('/\R{3,}/u', "\n\n", $text) ?? '';

        return mb_substr(trim($text), 0, 10000);
    }
}
