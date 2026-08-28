<?php

declare(strict_types=1);

namespace Tests\Unit\Frontend;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Presentation\FooterCreditRenderer;

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
        self::assertSame(
            '<p class="mgd-ai-footer-credit">supported by: <a href="https://Michael-Gahn.de" target="_blank" rel="noopener noreferrer" aria-label="Michael Gahn DESIGN – Herstellerseite in neuem Fenster öffnen">Michael Gahn DESIGN</a></p>',
            $this->erstelleRenderer()->render(true),
        );
    }

    #[Test]
    public function aktivierte_nennung_verlinkt_ausschliesslich_den_herstellernamen(): void
    {
        $html = $this->erstelleRenderer()->render(true);

        self::assertStringContainsString('>supported by: <a ', $html);
        self::assertStringContainsString('>Michael Gahn DESIGN</a>', $html);
        self::assertStringNotContainsString('>supported by: <a href="https://Michael-Gahn.de" target="_blank" rel="noopener noreferrer" aria-label="Michael Gahn DESIGN – Herstellerseite in neuem Fenster öffnen">supported by:', $html);
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
