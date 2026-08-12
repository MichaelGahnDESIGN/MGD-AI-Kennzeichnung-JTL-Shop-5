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

        /*
         * Entities werden zwingend vor der Markup-Entfernung dekodiert. Sonst
         * könnte etwa „&lt;script&gt;“ strip_tags() passieren und erst danach
         * wieder zu ausführbarem HTML werden. Mehrfach kodierte Eingaben werden
         * kontrolliert bis zu einer stabilen Darstellung aufgelöst.
         */
        $decoded = mb_substr(str_replace("\0", '', $input), 0, 50000);
        for ($durchlauf = 0; $durchlauf < 10; ++$durchlauf) {
            $next = html_entity_decode(
                $this->decodeNumericTagEntities($decoded),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8',
            );
            if ($next === $decoded) {
                break;
            }
            $decoded = $next;
        }

        /*
         * Extrem tief verschachtelte Kodierungen werden nach dem begrenzten
         * Dekodieren neutralisiert. Dadurch kann auch eine spätere Anzeige,
         * die irrtümlich nochmals Entities dekodiert, kein Tag rekonstruieren.
         */
        $markupEntity = '/&(?:(?:amp|#0*38|#x0*26);)*(?:lt|gt|#0*(?:60|62);?|#x0*(?:3c|3e);?)/iu';
        if (preg_match($markupEntity, $decoded) === 1) {
            /*
             * Nach zehn Durchläufen verbliebenes potenzielles Markup ist eine
             * ungewöhnlich tiefe Kodierung. Der gesamte Inhalt wird sicher
             * abgewiesen, statt Teile eines Angriffs als scheinbaren Text zu
             * übernehmen oder eine spätere Rekonstruktion zu riskieren.
             */
            $decoded = '';
        }

        $ohneAktiveBloecke = preg_replace(
            '#<(script|style)\b[^>]*>.*?</\1>#isu',
            '',
            $decoded,
        ) ?? '';
        $text = strip_tags($ohneAktiveBloecke);
        $text = preg_replace('/[\t ]+/u', ' ', $text) ?? '';
        $text = preg_replace('/\R{3,}/u', "\n\n", $text) ?? '';

        /* Nach strip_tags() darf bewusst keine Entity-Dekodierung mehr folgen. */
        return mb_substr(trim($text), 0, 10000);
    }

    /** Dekodiert ausschließlich semikolonlose numerische Winkelklammern. */
    private function decodeNumericTagEntities(string $text): string
    {
        return preg_replace_callback(
            '/&#0*60;?(?![0-9])|&#x0*3c;?|&#0*62;?(?![0-9])|&#x0*3e;?/iu',
            static fn(array $match): string => str_contains(strtolower($match[0]), '3c')
                || preg_match('/60/', $match[0]) === 1 ? '<' : '>',
            $text,
        ) ?? '';
    }
}
