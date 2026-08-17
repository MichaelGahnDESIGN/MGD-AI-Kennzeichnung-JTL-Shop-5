<?php

declare(strict_types=1);

namespace Tests\Unit\Frontend;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Presentation\LabelRenderer;
use Plugin\MGD_AI_Kennzeichnung\Service\LabelViewResolver;

final class LabelRendererTest extends TestCase
{
    #[Test]
    public function ungepruefte_und_ausdruecklich_unmarkierte_inhalte_bleiben_unsichtbar(): void
    {
        $renderer = new LabelRenderer();
        $resolver = new LabelViewResolver();

        self::assertSame('', $renderer->render($resolver->resolve('unreviewed')));
        self::assertSame('', $renderer->render($resolver->resolve('none')));
    }

    #[Test]
    public function sichtbares_label_verwendet_nur_das_gepruefte_view_modell(): void
    {
        $renderer = new LabelRenderer();
        $view = (new LabelViewResolver())->resolve(
            status: 'generated',
            position: 'top-left',
            theme: 'dark',
            language: 'de',
            locale: 'de-DE',
            assetSource: 'product',
            fontSize: 16,
            outerMargin: 9,
            innerPadding: 7,
            borderRadius: 5,
            blur: 3,
        );

        $html = $renderer->render($view);

        self::assertStringContainsString('role="note"', $html);
        self::assertStringContainsString('mgd-ai-status-generated', $html);
        self::assertStringContainsString('mgd-ai-label--position-top-left', $html);
        self::assertStringContainsString('mgd-ai-label--theme-dark', $html);
        self::assertStringContainsString('mgd-ai-label--source-product', $html);
        self::assertStringContainsString('aria-label="Dieser Inhalt wurde vollständig mit künstlicher Intelligenz erzeugt."', $html);
        self::assertStringContainsString('>KI-GENERIERT</span>', $html);
        self::assertStringContainsString('--mgd-ai-font-size:16px', $html);
        self::assertStringNotContainsString('<script', strtolower($html));
    }

    #[Test]
    public function alle_sichtbaren_statuswerte_besitzen_eine_feste_css_klasse(): void
    {
        $renderer = new LabelRenderer();
        $resolver = new LabelViewResolver();

        foreach (['generated', 'partially-generated', 'modified', 'deepfake'] as $status) {
            $html = $renderer->render($resolver->resolve($status));
            self::assertStringContainsString('mgd-ai-status-' . $status, $html);
        }
    }
}
