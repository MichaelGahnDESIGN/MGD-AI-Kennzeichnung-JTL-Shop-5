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
    public function jedes_prozentzeichen_in_der_authority_wird_abgelehnt(): void
    {
        $sanitizer = new PhilosophySanitizer();

        $urls = array_map(
            static fn(string $code): string => 'https://exa%' . $code . 'mple.org/path',
            ['23', '01', '25', '3f', '5b', '7f'],
        );
        array_push(
            $urls,
            'https://exa%mple.org/path',
            'https://exa%ggmple.org/path',
            'https://exa%252fmple.org/path',
        );

        foreach ($urls as $url) {
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
        self::assertSame(
            '<a href="https://example.org/%2f/%25" rel="noopener noreferrer">Pfad</a>',
            $sanitizer->sanitize('<a href="https://example.org/%2f/%25">Pfad</a>'),
        );
    }

    #[Test]
    public function dns_authorities_bestehen_nur_aus_browserkompatiblen_ascii_labels(): void
    {
        $sanitizer = new PhilosophySanitizer();
        foreach ([
            'exa^mple.org',
            'exa<mple.org',
            'exa>mple.org',
            'example].org',
            '[example.org',
            "exa\u{00a0}mple.org",
            'bücher.example',
            'example..org',
            '.example.org',
            'example.org.',
            '-example.org',
            'example-.org',
            'exa_mple.org',
        ] as $host) {
            self::assertSame(
                'Text',
                $sanitizer->sanitize('<a href="https://' . $host . '/path">Text</a>'),
                $host,
            );
        }

        foreach (['EXAMPLE.org', 'xn--bcher-kva.example', 'a-b.example', '127.0.0.1'] as $host) {
            self::assertSame(
                '<a href="https://' . $host . '/path" rel="noopener noreferrer">Text</a>',
                $sanitizer->sanitize('<a href="https://' . $host . '/path">Text</a>'),
                $host,
            );
        }
    }

    #[Test]
    public function kombinierte_entitaeten_bleiben_sichtbar_eindeutig_serialisiert(): void
    {
        $sanitizer = new PhilosophySanitizer();
        foreach ([
            '<p>&amp;&lt;&gt;…</p>' => '<p>&amp;&lt;&gt;…</p>',
            '<p>A&lt; &gt;B</p>' => '<p>A&lt; &gt;B</p>',
            '<p>A&lt;/&gt;B</p>' => '<p>A&lt;/&gt;B</p>',
            '<p>A&#60;&#62;B</p>' => '<p>A&lt;&gt;B</p>',
            '<p>A&#x3c;&#x3e;B</p>' => '<p>A&lt;&gt;B</p>',
            '<p>A&amp;lt;&amp;gt;B</p>' => '<p>A&amp;lt;&amp;gt;B</p>',
        ] as $html => $erwartet) {
            self::assertSame($erwartet, $sanitizer->sanitize($html), $html);
        }
        self::assertSame(
            '<a href="https://example.org/?a=1&amp;b=2" rel="noopener noreferrer">Link</a>',
            $sanitizer->sanitize('<a href="https://example.org/?a=1&amp;b=2">Link</a>'),
        );
    }

    #[Test]
    public function semikolonlose_html5_legacy_entitaeten_folgen_dem_browserkontext(): void
    {
        $sanitizer = new PhilosophySanitizer();
        foreach ([
            '<p>&amp </p>' => '<p>&amp; </p>',
            '<p>&lt Text &gt; &quot </p>' => '<p>&lt; Text &gt; " </p>',
            '<p>&ampx</p>' => '<p>&amp;x</p>',
            '<p>&AMP &Amp</p>' => '<p>&amp; &amp;Amp</p>',
            '<p>&ltimes; &notin;</p>' => '<p>⋉ ∉</p>',
        ] as $html => $erwartet) {
            self::assertSame($erwartet, $sanitizer->sanitize($html), $html);
        }

        self::assertSame(
            '<a href="https://example.org/a&amp;/b" rel="noopener noreferrer">Link</a>',
            $sanitizer->sanitize('<a href="https://example.org/a&amp/b">Link</a>'),
        );
        self::assertSame(
            '<a href="https://example.org/a&amp;amp=b" rel="noopener noreferrer">Link</a>',
            $sanitizer->sanitize('<a href="https://example.org/a&amp=b">Link</a>'),
        );
        self::assertSame(
            '<a href="https://example.org/a&amp;ampb" rel="noopener noreferrer">Link</a>',
            $sanitizer->sanitize('<a href="https://example.org/a&ampb">Link</a>'),
        );
    }

    #[Test]
    public function entities_erzeugen_keine_aktive_tagstruktur(): void
    {
        $sanitizer = new PhilosophySanitizer();
        foreach (['script', 'style', 'iframe', 'object', 'svg', 'math', 'template', 'noscript', 'form'] as $name) {
            $html = '<' . $name . '>BAD</' . $name . '&#32;>'
                . '<a href="https://example.org/leak">LEAK</a>'
                . '</' . $name . '><p>end</p>';
            self::assertSame('<p>end</p>', $sanitizer->sanitize($html), $name);
        }

        foreach ([
            '<script>BAD</script&amp;#32;><a href="https://example.org/leak">LEAK</a></script><p>end</p>',
            '<script &#47;>BAD</script><p>end</p>',
            '<script &amp;#47;>BAD</script><p>end</p>',
            '<script &sol;>BAD</script><p>end</p>',
        ] as $html) {
            self::assertSame('<p>end</p>', $sanitizer->sanitize($html), $html);
        }
    }

    #[Test]
    public function unicode_whitespace_beendet_aktive_container_nicht_vorzeitig(): void
    {
        $sanitizer = new PhilosophySanitizer();
        $aktiveContainer = ['script', 'style', 'iframe', 'object', 'svg', 'math', 'template', 'noscript', 'form'];
        $gegenproben = [
            'VT' => "\u{000b}",
            'NBSP' => "\u{00a0}",
            'OGHAM' => "\u{1680}",
            'LS' => "\u{2028}",
            'BOM' => "\u{feff}",
        ];

        foreach ($aktiveContainer as $name) {
            foreach ($gegenproben as $bezeichnung => $whitespace) {
                $html = '<' . $name . '>BAD</' . $name . $whitespace . '>'
                    . '<a href="https://example.org/leak">LEAK</a>'
                    . '</' . $name . '><p>end</p>';
                self::assertSame('<p>end</p>', $sanitizer->sanitize($html), $name . ' / ' . $bezeichnung);
            }
        }
    }

    #[Test]
    public function self_closing_slash_nach_formfeed_oeffnet_nicht_void_aktive_container(): void
    {
        $sanitizer = new PhilosophySanitizer();
        foreach (['script', 'style', 'iframe', 'object', 'svg', 'math', 'template', 'noscript', 'form'] as $name) {
            self::assertSame(
                '<p>end</p>',
                $sanitizer->sanitize('<' . $name . "\f/>BAD</" . $name . '><p>end</p>'),
                $name,
            );
        }
    }

    #[Test]
    public function roher_vorscanner_macht_kommentare_und_sonstige_knoten_nicht_sichtbar(): void
    {
        $sanitizer = new PhilosophySanitizer();

        foreach ([
            '<!-- geheim --><p>Sicher</p>',
            '<!doctype html><p>Sicher</p>',
            '<?x?><p>Sicher</p>',
        ] as $html) {
            self::assertSame('<p>Sicher</p>', $sanitizer->sanitize($html), $html);
        }
    }

    #[Test]
    public function quotes_in_attributnamen_oder_unquoted_werten_oeffnen_keinen_quotezustand(): void
    {
        $sanitizer = new PhilosophySanitizer();

        foreach (['script', 'iframe', 'object'] as $name) {
            foreach ([" foo' ", " data=x' "] as $attribute) {
                $html = '<' . $name . $attribute . '>BAD</' . $name . "\u{00a0}>"
                    . '<a href="https://example.org/leak">LEAK</a>'
                    . '</' . $name . '><p>end</p>';
                self::assertSame('<p>end</p>', $sanitizer->sanitize($html), $name . $attribute);
            }
        }
    }

    #[Test]
    public function slash_im_unquoted_embed_attributwert_ist_nicht_self_closing(): void
    {
        self::assertSame(
            '<p>Sicher</p>',
            (new PhilosophySanitizer())->sanitize('<embed data=x/>Text</embed><p>Sicher</p>'),
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
    public function kodiertes_aktives_markup_bleibt_text(): void
    {
        $sanitizer = new PhilosophySanitizer();

        self::assertSame(
            '&amp;lt;script&amp;gt;alert(1)&amp;lt;/script&amp;gt;<p>Sichtbar</p>',
            $sanitizer->sanitize('&amp;lt;script&amp;gt;alert(1)&amp;lt;/script&amp;gt;<p>Sichtbar</p>'),
        );
        self::assertSame(
            '&lt;script&gt;alert(1)&lt;/script&gt;<p>Sichtbar</p>',
            $sanitizer->sanitize('&lt;script&gt;alert(1)&lt;/script&gt;<p>Sichtbar</p>'),
        );
    }

    #[Test]
    public function nicht_string_und_uebergrosse_eingaben_werden_begrenzt(): void
    {
        $sanitizer = new PhilosophySanitizer();

        self::assertSame('', $sanitizer->sanitize(['kein Text']));
        self::assertLessThanOrEqual(10_000, mb_strlen(strip_tags($sanitizer->sanitize(str_repeat('ä', 20_000)))));
    }
}
