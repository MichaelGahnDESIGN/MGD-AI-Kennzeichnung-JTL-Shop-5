<?php

declare(strict_types=1);

namespace Tests\Unit\Frontend;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Frontend\FooterCreditRenderer;

final class FooterCreditRendererTest extends TestCase
{
    #[Test]
    public function deaktivierte_nennung_liefert_exakt_keine_ausgabe(): void
    {
        $renderer = $this->erstelleRenderer();

        self::assertSame('', $renderer->render(false));
    }

    #[Test]
    public function aktivierte_nennung_liefert_ausschliesslich_festes_sicheres_html(): void
    {
        $renderer = $this->erstelleRenderer();

        $html = $renderer->render(true);

        self::assertSame(
            '<p class="mgd-ai-footer-credit"><a href="https://Michael-Gahn.de" target="_blank" rel="noopener noreferrer" aria-label="Plugin von Michael Gahn DESIGN – Herstellerseite in neuem Fenster öffnen">Plugin von Michael Gahn DESIGN</a></p>',
            $html,
        );
        self::assertStringNotContainsString('<script', strtolower($html));
        self::assertStringNotContainsString('javascript:', strtolower($html));
    }

    private function erstelleRenderer(): FooterCreditRenderer
    {
        self::assertTrue(
            class_exists(FooterCreditRenderer::class),
            sprintf('Die Klasse %s muss implementiert werden.', FooterCreditRenderer::class),
        );

        return new FooterCreditRenderer();
    }
}
