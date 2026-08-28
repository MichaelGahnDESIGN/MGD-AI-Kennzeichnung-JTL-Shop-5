<?php

declare(strict_types=1);

namespace Tests\Structure;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FrontendAssetContractTest extends TestCase
{
    private const FRONTEND = __DIR__ . '/../../plugin/MGD_AI_Kennzeichnung/frontend';

    #[Test]
    public function javascript_verarbeitet_nur_ausdruecklich_markierte_elemente(): void
    {
        $javascript = file_get_contents(self::FRONTEND . '/js/mgd-ai-marked-elements.js');
        self::assertIsString($javascript);

        self::assertStringContainsString("document.querySelectorAll('.mgd-ai-label')", $javascript);
        self::assertStringContainsString('textContent', $javascript);
        self::assertStringNotContainsString("querySelectorAll('img')", $javascript);
        self::assertStringNotContainsString('getElementsByTagName', $javascript);
        self::assertStringNotContainsString('innerHTML', $javascript);
        self::assertStringNotContainsString('outerHTML', $javascript);
        self::assertStringNotContainsString('document.body.querySelectorAll', $javascript);
    }

    #[Test]
    public function css_enthaelt_fallback_varianten_ohne_bedienung_zu_blockieren(): void
    {
        $css = file_get_contents(self::FRONTEND . '/css/mgd-ai-labels.css');
        self::assertIsString($css);

        foreach (['top-left', 'top-right', 'bottom-left', 'bottom-right'] as $position) {
            self::assertStringContainsString('mgd-ai-label--position-' . $position, $css);
        }
        foreach (['auto', 'light', 'dark'] as $theme) {
            self::assertStringContainsString('mgd-ai-label--theme-' . $theme, $css);
        }
        foreach (['generated', 'partially-generated', 'modified', 'deepfake'] as $status) {
            self::assertStringContainsString('mgd-ai-status-' . $status, $css);
        }

        self::assertStringContainsString('pointer-events: none', $css);
        self::assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
        self::assertStringContainsString('.mgd-ai-label-host--inline', $css);
        self::assertStringContainsString('display: inline-block', $css);
        self::assertStringContainsString('max-width: 100%', $css);
        self::assertStringContainsString('vertical-align: top', $css);
        self::assertStringContainsString('--mgd-ai-background-opacity: 0.92', $css);
        self::assertStringContainsString(
            ".mgd-ai-label--theme-auto,\n.mgd-ai-label--theme-dark {\n    color: #fff;\n    background: rgba(17, 24, 39, var(--mgd-ai-background-opacity));",
            $css,
        );
        self::assertStringContainsString(
            ".mgd-ai-label--theme-light {\n    color: #111827;\n    background: rgba(255, 255, 255, var(--mgd-ai-background-opacity));",
            $css,
        );
    }

    #[Test]
    public function smarty_template_escaped_alle_sichtbaren_werte(): void
    {
        $template = file_get_contents(self::FRONTEND . '/template/label.tpl');
        self::assertIsString($template);

        self::assertStringContainsString('role="note"', $template);
        self::assertGreaterThanOrEqual(4, substr_count($template, '|escape'));
        self::assertStringContainsString(
            "--mgd-ai-background-opacity:{\$mgdAiLabel.backgroundOpacity|escape:'html':'UTF-8'}",
            $template,
        );
        self::assertStringNotContainsString('nofilter', $template);
    }

    #[Test]
    public function footer_fallback_verlinkt_ausschliesslich_den_herstellernamen_sicher(): void
    {
        $template = file_get_contents(self::FRONTEND . '/template/layout/footer.tpl');
        self::assertIsString($template);

        self::assertMatchesRegularExpression(
            '~<p\\s+class="mgd-ai-footer-credit">\\s*supported by:\\s*<a\\s+href="https://Michael-Gahn\\.de"\\s+target="_blank"\\s+rel="noopener noreferrer"\\s+aria-label="Michael Gahn DESIGN – Herstellerseite in neuem Fenster öffnen">Michael Gahn DESIGN</a>\\s*</p>~u',
            $template,
        );
    }
}
