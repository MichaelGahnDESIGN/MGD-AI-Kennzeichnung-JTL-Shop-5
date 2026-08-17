<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Service\PhilosophySanitizer;

final class PhilosophySanitizerTest extends TestCase
{
    #[Test]
    public function erlaubte_semantische_elemente_und_umlaute_bleiben_erhalten(): void
    {
        $clean = (new PhilosophySanitizer())->sanitize(
            '<h2>Unsere Haltung</h2><p>Künstliche Intelligenz &amp; Verantwortung</p>'
            . '<ul><li><strong>Transparent</strong> und <em>fair</em></li></ul>',
        );

        self::assertSame(
            '<h2>Unsere Haltung</h2><p>Künstliche Intelligenz &amp; Verantwortung</p>'
            . '<ul><li><strong>Transparent</strong> und <em>fair</em></li></ul>',
            $clean,
        );
    }

    #[Test]
    public function aktive_und_unbekannte_elemente_werden_sicher_entfernt(): void
    {
        $clean = (new PhilosophySanitizer())->sanitize(
            '<p onclick="alert(1)">Sicher</p><script>alert(1)</script>'
            . '<style>body{display:none}</style><svg onload="alert(2)"><text>Angriff</text></svg>'
            . '<div><b>Lesbarer Text</b></div>',
        );

        self::assertSame('<p>Sicher</p>Lesbarer Text', $clean);
        self::assertStringNotContainsStringIgnoringCase('script', $clean);
        self::assertStringNotContainsStringIgnoringCase('onclick', $clean);
        self::assertStringNotContainsStringIgnoringCase('svg', $clean);
    }

    #[Test]
    public function nur_https_links_ohne_zugangsdaten_werden_ausgegeben(): void
    {
        $sanitizer = new PhilosophySanitizer();

        self::assertSame(
            '<a href="https://example.org/mehr?x=1" rel="noopener noreferrer">Mehr erfahren</a>',
            $sanitizer->sanitize('<a href="https://example.org/mehr?x=1" target="_blank" onclick="x">Mehr erfahren</a>'),
        );
        foreach (['javascript:alert(1)', 'http://example.org', 'https://nutzer:pass@example.org', '//example.org'] as $url) {
            self::assertSame('Unsicher', $sanitizer->sanitize('<a href="' . $url . '">Unsicher</a>'));
        }
    }

    #[Test]
    public function mehrfach_kodiertes_aktives_markup_wird_vor_der_pruefung_aufgeloest(): void
    {
        $clean = (new PhilosophySanitizer())->sanitize(
            '&amp;lt;script&amp;gt;alert(1)&amp;lt;/script&amp;gt;<p>Sichtbar</p>',
        );

        self::assertSame('<p>Sichtbar</p>', $clean);
    }

    #[Test]
    public function nicht_string_und_uebergrosse_eingaben_werden_begrenzt(): void
    {
        $sanitizer = new PhilosophySanitizer();

        self::assertSame('', $sanitizer->sanitize(['kein Text']));
        self::assertLessThanOrEqual(10_000, mb_strlen(strip_tags($sanitizer->sanitize(str_repeat('ä', 20_000)))));
    }
}
