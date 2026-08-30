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
    public function ipv6_links_bleiben_browsergueltig_und_sicher_serialisiert(): void
    {
        $clean = (new PhilosophySanitizer())->sanitize(
            '<a href="https://[2001:db8::1]:443/path">IPv6</a>',
        );

        self::assertSame(
            '<a href="https://[2001:db8::1]:443/path" rel="noopener noreferrer">IPv6</a>',
            $clean,
        );
    }

    #[Test]
    public function nur_exakt_roher_https_port_443_wird_akzeptiert(): void
    {
        $sanitizer = new PhilosophySanitizer();

        foreach ([
            'https://example.org:0443/path',
            'https://example.org:00443/path',
            'https://[2001:db8::1]:0443/path',
            'https://[2001:db8::1]:00443/path',
        ] as $url) {
            self::assertSame('Text', $sanitizer->sanitize('<a href="' . $url . '">Text</a>'), $url);
        }

        self::assertSame(
            '<a href="https://example.org:443/path" rel="noopener noreferrer">Text</a>',
            $sanitizer->sanitize('<a href="https://example.org:443/path">Text</a>'),
        );
        self::assertSame(
            '<a href="https://[2001:db8::1]:443/path" rel="noopener noreferrer">Text</a>',
            $sanitizer->sanitize('<a href="https://[2001:db8::1]:443/path">Text</a>'),
        );
    }

    #[Test]
    public function geklammerte_hosts_muessen_gueltige_ipv6_adressen_sein(): void
    {
        $sanitizer = new PhilosophySanitizer();

        foreach ([
            'https://[]/path',
            'https://[not-ipv6]/path',
            'https://[2001:db8::zz]:443/path',
        ] as $url) {
            self::assertSame('Text', $sanitizer->sanitize('<a href="' . $url . '">Text</a>'), $url);
        }
    }

    #[Test]
    public function prozentkodierte_verbotene_authorityzeichen_werden_abgelehnt(): void
    {
        $sanitizer = new PhilosophySanitizer();

        foreach (['00', '2f', '5c', '40', '3a'] as $code) {
            $url = 'https://exa%' . $code . 'mple.org/path';
            self::assertSame('Text', $sanitizer->sanitize('<a href="' . $url . '">Text</a>'), $url);
        }

        self::assertSame(
            '<a href="https://example.org/path" rel="noopener noreferrer">DNS</a>',
            $sanitizer->sanitize('<a href="https://example.org/path">DNS</a>'),
        );
        self::assertSame(
            '<a href="https://[2001:db8::1]/path" rel="noopener noreferrer">IPv6</a>',
            $sanitizer->sanitize('<a href="https://[2001:db8::1]/path">IPv6</a>'),
        );
    }

    #[Test]
    public function kombinierte_entitaeten_bleiben_sichtbar_eindeutig_serialisiert(): void
    {
        self::assertSame(
            '<p>&amp;…</p>',
            (new PhilosophySanitizer())->sanitize('<p>&amp;&lt;&gt;…</p>'),
        );
    }

    #[Test]
    public function gleichnamig_verschachtelte_aktive_container_verlieren_auch_tail_inhalt(): void
    {
        $sanitizer = new PhilosophySanitizer();

        foreach (['iframe', 'embed', 'noscript'] as $name) {
            $html = '<' . $name . '>outer<' . $name . '>inner</' . $name . '>tail</' . $name . '><p>end</p>';
            self::assertSame('<p>end</p>', $sanitizer->sanitize($html), $name);
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
