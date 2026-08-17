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
    }

    #[Test]
    public function smarty_template_escaped_alle_sichtbaren_werte(): void
    {
        $template = file_get_contents(self::FRONTEND . '/template/label.tpl');
        self::assertIsString($template);

        self::assertStringContainsString('role="note"', $template);
        self::assertGreaterThanOrEqual(4, substr_count($template, '|escape'));
        self::assertStringNotContainsString('nofilter', $template);
    }
}
