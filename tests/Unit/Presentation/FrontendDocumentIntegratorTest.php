<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Presentation\FrontendDocumentIntegrator;
use Plugin\MGD_AI_Kennzeichnung\Service\DisplaySettings;

final class FrontendDocumentIntegratorTest extends TestCase
{
    #[Test]
    public function lokale_assets_werden_genau_einmal_in_head_und_body_eingefuegt(): void
    {
        $dokument = new RecordingDocument();

        (new FrontendDocumentIntegrator())->integrate(
            ['document' => $dokument],
            'https://onvis-shop.de/plugins/MGD_AI_Kennzeichnung/frontend/',
            false,
        );

        self::assertCount(1, $dokument->head->markup);
        self::assertCount(1, $dokument->body->markup);
        self::assertStringContainsString('/css/mgd-ai-labels.css', $dokument->head->markup[0]);
        self::assertStringContainsString('/js/mgd-ai-marked-elements.js', $dokument->body->markup[0]);
        self::assertStringNotContainsString('Michael-Gahn.de', $dokument->body->markup[0]);
    }

    #[Test]
    public function footer_nennung_wird_nur_nach_opt_in_angehaengt(): void
    {
        $dokument = new RecordingDocument();

        (new FrontendDocumentIntegrator())->integrate(
            ['document' => $dokument],
            'https://onvis-shop.de/plugin/frontend',
            true,
        );

        self::assertStringContainsString('Plugin von Michael Gahn DESIGN', $dokument->body->markup[0]);
        self::assertStringContainsString('rel="noopener noreferrer"', $dokument->body->markup[0]);
    }

    #[Test]
    public function unvollstaendiger_hook_kontext_bleibt_ohne_ausgabe_oder_fehler(): void
    {
        $integrator = new FrontendDocumentIntegrator();
        $integrator->integrate([], 'https://onvis-shop.de/plugin/frontend', true);
        $integrator->integrate(['document' => new \stdClass()], 'https://onvis-shop.de/plugin/frontend', true);

        self::addToAssertionCount(1);
    }

    #[Test]
    public function native_labels_suchen_nur_den_geprueften_dateinamen_und_ergaenzen_semantik(): void
    {
        $dokument = new RecordingDocument();
        $integrator = new FrontendDocumentIntegrator();

        $integrator->integrateLabels(['document' => $dokument], [[
            'local_path' => 'media/image/storage/produkte/sicheres-bild.webp',
            'status' => 'generated',
            'position' => 'bottom-right',
            'theme' => 'auto',
            'source_type' => 'product',
        ]], DisplaySettings::fromInput([]), 'de');

        self::assertContains(
            'img[src="sicheres-bild.webp"], img[src$="/sicheres-bild.webp"], '
            . 'img[src*="/sicheres-bild.webp?"], img[src*="/sicheres-bild.webp#"]',
            $dokument->selectors,
        );
        self::assertNotContains('img', $dokument->selectors);
        self::assertStringContainsString('role="note"', $dokument->images->markup[0]);
        self::assertStringContainsString('KI-GENERIERT', $dokument->images->markup[0]);
        self::assertContains('mgd-ai-label-host', $dokument->images->classes);
    }
}

final class RecordingDocument
{
    public RecordingTarget $head;
    public RecordingTarget $body;
    public RecordingTarget $images;

    /** @var list<string> */
    public array $selectors = [];

    public function __construct()
    {
        $this->head = new RecordingTarget();
        $this->body = new RecordingTarget();
        $this->images = new RecordingTarget();
    }

    public function find(string $selector): RecordingTarget
    {
        $this->selectors[] = $selector;
        if ($selector === 'head') {
            return $this->head;
        }

        return $selector === 'body' ? $this->body : $this->images;
    }
}

final class RecordingTarget
{
    /** @var list<string> */
    public array $markup = [];

    /** @var list<string> */
    public array $classes = [];

    public function append(string $markup): void
    {
        $this->markup[] = $markup;
    }

    public function parent(): self
    {
        return $this;
    }

    public function addClass(string $class): void
    {
        $this->classes[] = $class;
    }
}
