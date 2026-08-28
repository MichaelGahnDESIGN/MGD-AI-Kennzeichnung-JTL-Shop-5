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

        self::assertStringContainsString('supported by: <a ', $dokument->body->markup[0]);
        self::assertStringContainsString('>Michael Gahn DESIGN</a>', $dokument->body->markup[0]);
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
        self::assertStringContainsString('role="note"', $dokument->linkHosts->markup[0]);
        self::assertStringContainsString('KI-GENERIERT', $dokument->linkHosts->markup[0]);
        self::assertContains('picture', $dokument->imageParents->filters);
        self::assertContains('a', $dokument->pictureHosts->filters);
        self::assertContains('mgd-ai-label-host', $dokument->linkHosts->classes);
        self::assertContains('mgd-ai-label-host--inline', $dokument->linkHosts->classes);
    }

    #[Test]
    public function opc_hintergrundbilder_erhalten_das_label_direkt_im_container(): void
    {
        $dokument = new RecordingDocument();

        (new FrontendDocumentIntegrator())->integrateLabels(['document' => $dokument], [[
            'local_path' => 'media/image/storage/opc/banner/kategorien/2026/werbemittel.png',
            'status' => 'generated',
            'position' => 'top-right',
            'theme' => 'dark',
            'source_type' => 'opc',
        ]], DisplaySettings::fromInput([]), 'de');

        self::assertContains(
            '[style*="/werbemittel.png"], [data-image-src="werbemittel.png"], '
            . '[data-image-src$="/werbemittel.png"], [data-image-src*="/werbemittel.png?"], '
            . '[data-image-src*="/werbemittel.png#"]',
            $dokument->selectors,
        );
        self::assertContains('mgd-ai-label-host', $dokument->backgrounds->classes);
        self::assertStringContainsString('KI-GENERIERT', $dokument->backgrounds->markup[0]);
    }

    #[Test]
    public function dasselbe_ziel_wird_bei_wiederholter_integration_nicht_doppelt_ausgezeichnet(): void
    {
        $dokument = new RecordingDocument();
        $labels = [[
            'local_path' => 'media/image/storage/opc/bilder/wiederholt.webp',
            'status' => 'generated',
            'position' => 'bottom-right',
            'theme' => 'auto',
            'source_type' => 'opc',
        ]];
        $integrator = new FrontendDocumentIntegrator();

        $integrator->integrateLabels(['document' => $dokument], $labels, DisplaySettings::fromInput([]), 'de');
        $integrator->integrateLabels(['document' => $dokument], $labels, DisplaySettings::fromInput([]), 'de');

        self::assertCount(1, $dokument->linkHosts->markup);
        self::assertCount(1, $dokument->backgrounds->markup);
    }
}

final class RecordingDocument
{
    public RecordingTarget $head;
    public RecordingTarget $body;
    public RecordingTarget $images;
    public RecordingTarget $imageParents;
    public RecordingTarget $pictureElements;
    public RecordingTarget $pictureHosts;
    public RecordingTarget $directHosts;
    public RecordingTarget $linkHosts;
    public RecordingTarget $blockHosts;
    public RecordingTarget $backgrounds;

    /** @var list<string> */
    public array $selectors = [];

    public function __construct()
    {
        $this->head = new RecordingTarget();
        $this->body = new RecordingTarget();
        $this->images = new RecordingTarget();
        $this->imageParents = new RecordingTarget();
        $this->pictureElements = new RecordingTarget();
        $this->pictureHosts = new RecordingTarget();
        $this->directHosts = new RecordingTarget();
        $this->linkHosts = new RecordingTarget();
        $this->blockHosts = new RecordingTarget();
        $this->backgrounds = new RecordingTarget();

        $this->images->routes['parent'] = $this->imageParents;
        $this->imageParents->routes['filter:picture'] = $this->pictureElements;
        $this->imageParents->routes['not:picture'] = $this->directHosts;
        $this->pictureElements->routes['parent'] = $this->pictureHosts;

        foreach ([$this->pictureHosts, $this->directHosts] as $hosts) {
            $hosts->routes['filter:a'] = $this->linkHosts;
            $hosts->routes['not:a'] = $this->blockHosts;
        }
    }

    public function find(string $selector): RecordingTarget
    {
        $this->selectors[] = $selector;
        if ($selector === 'head') {
            return $this->head;
        }

        if ($selector === 'body') {
            return $this->body;
        }

        return str_starts_with($selector, '[style*=') ? $this->backgrounds : $this->images;
    }
}

final class RecordingTarget
{
    /** @var list<string> */
    public array $markup = [];

    /** @var list<string> */
    public array $classes = [];

    /** @var list<string> */
    public array $filters = [];

    /** @var array<string, self> */
    public array $routes = [];

    public function append(string $markup): void
    {
        $this->markup[] = $markup;
    }

    public function parent(): self
    {
        return $this->routes['parent'] ?? $this;
    }

    public function addClass(string $class): void
    {
        $this->classes[] = $class;
    }

    public function filter(string $selector): self
    {
        $this->filters[] = $selector;

        return $this->routes['filter:' . $selector] ?? $this;
    }

    public function not(string $selector): self
    {
        $this->filters[] = $selector;
        if ($selector === '.mgd-ai-label-host' && in_array('mgd-ai-label-host', $this->classes, true)) {
            return new RecordingTarget();
        }

        return $this->routes['not:' . $selector] ?? $this;
    }
}
