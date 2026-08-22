<?php

declare(strict_types=1);

namespace Tests\Unit\Admin\Presentation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Admin\Presentation\LocalPreviewUrlResolver;

final class LocalPreviewUrlResolverTest extends TestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function erlaubtePfade(): iterable
    {
        yield 'JTL-Bildspeicher' => [
            '/media/image/storage/1200_1200/produkt.jpg',
            'https://shop.example/media/image/storage/1200_1200/produkt.jpg',
        ];
        yield 'klassischer Bilderordner' => [
            'bilder/kategorien/Bild groß.webp',
            'https://shop.example/bilder/kategorien/Bild%20gro%C3%9F.webp',
        ];
        yield 'OPC-Banner' => [
            '/opc/banner/kategorien/2026/motiv.png',
            'https://shop.example/opc/banner/kategorien/2026/motiv.png',
        ];
        yield 'lokales Templatebild' => [
            '/templates/OnvisTheme/img/logo.avif',
            'https://shop.example/templates/OnvisTheme/img/logo.avif',
        ];
    }

    #[Test]
    #[DataProvider('erlaubtePfade')]
    public function erzeugt_nur_fuer_freigegebene_lokale_bildwurzeln_eine_url(
        string $pfad,
        string $erwarteteUrl,
    ): void {
        $resolver = new LocalPreviewUrlResolver();

        self::assertTrue($resolver->accepts($pfad));
        self::assertSame($erwarteteUrl, $resolver->resolve($pfad, 'https://shop.example/'));
    }

    /** @return iterable<string, array{string}> */
    public static function unsicherePfade(): iterable
    {
        yield 'externe URL' => ['https://fremd.example/media/image/a.jpg'];
        yield 'Protocol Relative' => ['//fremd.example/media/image/a.jpg'];
        yield 'Data URL' => ['data:image/png;base64,AAAA'];
        yield 'JavaScript' => ['javascript:alert(1).jpg'];
        yield 'Traversal' => ['/media/image/../config.jpg'];
        yield 'kodiertes Traversal' => ['/media/image/%2e%2e/config.jpg'];
        yield 'doppelt kodiertes Traversal' => ['/media/image/%252e%252e/config.jpg'];
        yield 'Nullbyte' => ["/media/image/a.jpg\0.png"];
        yield 'Backslash' => ['media\\image\\a.jpg'];
        yield 'nicht erlaubte Wurzel' => ['/uploads/frei.jpg'];
        yield 'Nichtbild' => ['/media/image/datei.pdf'];
        yield 'SVG mit möglichem Aktivinhalt' => ['/media/image/vektor.svg'];
        yield 'leerer Pfad' => [''];
    }

    #[Test]
    #[DataProvider('unsicherePfade')]
    public function verwirft_unsichere_externe_oder_nicht_freigegebene_pfade(string $pfad): void
    {
        $resolver = new LocalPreviewUrlResolver();

        self::assertFalse($resolver->accepts($pfad));
        self::assertNull($resolver->resolve($pfad, 'https://shop.example/'));
    }

    #[Test]
    public function verwirft_eine_ungueltige_shop_basis_auch_bei_gueltigem_bildpfad(): void
    {
        $resolver = new LocalPreviewUrlResolver();

        self::assertNull($resolver->resolve('/media/image/a.jpg', 'javascript:alert(1)'));
        self::assertNull($resolver->resolve('/media/image/a.jpg', 'https://user:pass@shop.example/'));
        self::assertNull($resolver->resolve('/media/image/a.jpg', '//shop.example/'));
    }

    #[Test]
    public function verwendet_aus_einer_plugin_url_nur_die_same_origin_shop_basis(): void
    {
        $resolver = new LocalPreviewUrlResolver();

        self::assertSame(
            'https://shop.example/media/image/a.jpg',
            $resolver->resolve('/media/image/a.jpg', 'https://shop.example/plugins/MGD_AI_Kennzeichnung/frontend/'),
        );
    }
}
