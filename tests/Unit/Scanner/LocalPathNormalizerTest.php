<?php

declare(strict_types=1);

namespace Tests\Unit\Scanner;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Domain\AssetSource;
use Plugin\MGD_AI_Kennzeichnung\Scanner\LocalImageReference;
use Plugin\MGD_AI_Kennzeichnung\Scanner\LocalPathNormalizer;

final class LocalPathNormalizerTest extends TestCase
{
    #[Test]
    public function normalisiert_einen_eindeutigen_lokalen_bildpfad_ohne_dateisystemzugriff(): void
    {
        $normalizer = new LocalPathNormalizer();

        self::assertSame(
            'bilder/produkte/produkt.jpg',
            $normalizer->normalize('/bilder//produkte/./produkt.jpg'),
        );
        self::assertSame('bilder/Größe ä.webp', $normalizer->normalize('bilder/Größe ä.webp'));
    }

    /** @return iterable<string, array{mixed}> */
    public static function unsichereReferenzen(): iterable
    {
        yield 'null' => [null];
        yield 'kein String' => [42];
        yield 'leer' => ['  '];
        yield 'NUL' => ["bilder/a\0.jpg"];
        yield 'externe URL' => ['https://fremd.example/bild.jpg'];
        yield 'protocol-relative URL' => ['//fremd.example/bild.jpg'];
        yield 'data-Schema' => ['data:image/png;base64,AAAA'];
        yield 'JavaScript-Schema' => ['javascript:alert(1).jpg'];
        yield 'file-Schema' => ['file:///etc/passwd.jpg'];
        yield 'php-Schema' => ['php://filter/bild.jpg'];
        yield 'ftp-Schema' => ['ftp://shop.example/bild.jpg'];
        yield 'Credentials' => ['https://nutzer:passwort@shop.example/bild.jpg'];
        yield 'Windows absolut' => ['C:\\bilder\\bild.jpg'];
        yield 'UNC' => ['\\\\server\\freigabe\\bild.jpg'];
        yield 'Query' => ['/bilder/bild.jpg?token=geheim'];
        yield 'Fragment' => ['/bilder/bild.jpg#anker'];
        yield 'Traversal' => ['/bilder/../includes/config.JTL-Shop.ini.php.jpg'];
        yield 'percent-kodiertes Traversal' => ['/bilder/%2e%2e/geheim.jpg'];
        yield 'doppelt percent-kodiertes Traversal' => ['/bilder/%252e%252e/geheim.jpg'];
        yield 'kodierter Backslash' => ['/bilder/%5c..%5cgeheim.jpg'];
        yield 'Unicode-Division-Slash' => ["bilder/..∕geheim.jpg"];
        yield 'Unicode-Vollbreitenpunkte' => ["bilder/．．/geheim.jpg"];
        yield 'sensitive Konfiguration' => ['includes/config.JTL-Shop.ini.php'];
        yield 'Nichtbild' => ['bilder/datei.pdf'];
        yield 'SVG konservativ abgelehnt' => ['bilder/vektor.svg'];
        yield 'ungültiges UTF-8' => ["bilder/\xC3\x28.jpg"];
        yield 'zu lang' => ['bilder/' . str_repeat('a', 1010) . '.jpg'];
        yield 'zu viele Segmente' => [str_repeat('a/', 65) . 'bild.jpg'];
    }

    #[Test]
    #[DataProvider('unsichereReferenzen')]
    public function verwirft_unsichere_oder_nichtlokale_referenzen(mixed $referenz): void
    {
        self::assertNull((new LocalPathNormalizer())->normalize($referenz));
    }

    #[Test]
    public function akzeptiert_eigene_hosts_nur_ueber_explizite_allowlist(): void
    {
        $normalizer = new LocalPathNormalizer(['shop.example', 'cdn.shop.example']);

        self::assertSame(
            'bilder/produkt.avif',
            $normalizer->normalize('https://CDN.SHOP.EXAMPLE/bilder/produkt.avif'),
        );
        self::assertNull($normalizer->normalize('https://fremd.example/bilder/produkt.avif'));
        self::assertNull($normalizer->normalize('https://shop.example.evil/bilder/produkt.avif'));
        self::assertNull($normalizer->normalize('https://shop.example/bilder/produkt.avif?x=1'));
    }

    #[Test]
    public function erzeugt_eine_minimierte_stabile_lokale_referenz_nur_aus_gueltigem_pfad(): void
    {
        $normalizer = new LocalPathNormalizer();
        $reference = LocalImageReference::fromRaw(
            '/bilder/produkt.jpeg',
            AssetSource::Product,
            'artikel:42:bild:7',
            '<b>Produkt</b>   mit   Details',
            $normalizer,
        );

        self::assertNotNull($reference);
        self::assertSame('bilder/produkt.jpeg', $reference->localPath);
        self::assertSame(AssetSource::Product, $reference->source);
        self::assertSame('artikel:42:bild:7', $reference->sourceReference);
        self::assertSame('Produkt mit Details', $reference->context);
        self::assertSame(hash('sha256', 'bilder/produkt.jpeg'), $reference->assetKey);
        self::assertNull(LocalImageReference::fromRaw(
            'https://fremd.example/a.jpg',
            AssetSource::Product,
            'artikel:42',
            null,
            $normalizer,
        ));
    }

    #[Test]
    public function verwirft_freie_oder_ueberlange_quellenwerte_statt_sie_zu_speichern(): void
    {
        $normalizer = new LocalPathNormalizer();

        self::assertNull(LocalImageReference::fromRaw(
            '/bilder/a.jpg',
            AssetSource::Product,
            "artikel:1\0",
            null,
            $normalizer,
        ));
        self::assertNull(LocalImageReference::fromRaw(
            '/bilder/a.jpg',
            AssetSource::Product,
            str_repeat('a', 256),
            null,
            $normalizer,
        ));
    }

    #[Test]
    public function laesst_auch_tief_kodiertes_markup_nicht_in_den_kontext_gelangen(): void
    {
        $kontext = '<img src="x" onerror="alert(1)">';
        for ($round = 0; $round < 12; ++$round) {
            $kontext = htmlspecialchars($kontext, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        $reference = LocalImageReference::fromRaw(
            '/bilder/a.jpg',
            AssetSource::Product,
            'artikel:1',
            $kontext,
            new LocalPathNormalizer(),
        );

        self::assertNotNull($reference);
        self::assertNull($reference->context);
    }
}
