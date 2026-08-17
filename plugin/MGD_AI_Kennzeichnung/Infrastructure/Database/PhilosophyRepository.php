<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database;

use JTL\DB\DbInterface;
use Plugin\MGD_AI_Kennzeichnung\Service\PhilosophySanitizer;
use RuntimeException;

/** Speichert je Sprache genau einen bereinigten Philosophie-Text. */
final class PhilosophyRepository
{
    private const TABLE = 'xplugin_mgd_ai_philosophy';

    private readonly SchemaOwnershipGuard $ownership;
    private readonly PhilosophySanitizer $sanitizer;

    public function __construct(private readonly DbInterface $db, ?PhilosophySanitizer $sanitizer = null)
    {
        $this->ownership = new SchemaOwnershipGuard($db);
        $this->sanitizer = $sanitizer ?? new PhilosophySanitizer();
    }

    public function upsert(mixed $language, mixed $content): void
    {
        $this->ownership->assertOwned(self::TABLE);
        $normalLanguage = $this->normalLanguage($language);

        $safeContent = $this->sanitizer->sanitize($content);
        if ($safeContent === '') {
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
            ['language' => $normalLanguage, 'content' => $safeContent],
        );
    }

    /** Liefert nur den bereits bereinigten Inhalt der passenden Shopsprache. */
    public function findForLocale(mixed $language): string
    {
        $normalLanguage = $this->normalLanguage($language);
        $zeile = $this->db->getSingleObject(
            'SELECT `content` FROM `' . self::TABLE . '` WHERE `language` = :language LIMIT 1',
            ['language' => $normalLanguage],
        );
        $inhalt = $zeile === null ? null : ($zeile->content ?? null);

        return is_string($inhalt) ? $this->sanitizer->sanitize($inhalt) : '';
    }

    private function normalLanguage(mixed $language): string
    {
        $wert = is_string($language) ? strtolower(trim($language)) : '';

        return match ($wert) {
            'de', 'de-de', 'ger', 'deu' => 'de',
            'en', 'en-us', 'en-gb', 'eng' => 'en',
            default => throw new RuntimeException('Unterstützt werden ausschließlich Deutsch und Englisch.'),
        };
    }
}
