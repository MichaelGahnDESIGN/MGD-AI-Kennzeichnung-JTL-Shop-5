<?php

declare(strict_types=1);

namespace Tests\Structure;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Sichert die separate lokale Detailprobe anstelle des bisherigen Schachbrettrands. */
final class DisplayDetailPreviewContractTest extends TestCase
{
    private const ADMIN = __DIR__ . '/../../plugin/MGD_AI_Kennzeichnung/adminmenu/';

    #[Test]
    public function produkt_bekommt_kein_muster_und_detail_template_folgt_danach(): void
    {
        $template = $this->lese('templates/display.tpl');
        self::assertStringNotContainsString('display-preview-pattern.css', $template);
        self::assertFileDoesNotExist(self::ADMIN . 'display-preview-pattern.css');
        self::assertMatchesRegularExpression(
            '~images/michael-gahn-design-schuh\.png[\s\S]+\{include file=\'\./display-detail-preview\.tpl\'\}~',
            $template,
        );
        self::assertStringContainsString('Die folgenden Optionen ändern nur diese Vorschau und werden nicht gespeichert.', $template);
    }

    #[Test]
    public function detailprobe_hat_alle_vereinbarten_elemente_aber_keine_persistenten_felder(): void
    {
        $template = $this->lese('templates/display-detail-preview.tpl');
        foreach (['Detail-Lupe', '2× vergrößert', 'data-mgd-detail-preview', 'data-mgd-detail-label', 'data-mgd-detail-transparency', 'data-mgd-detail-blur', 'data-mgd-detail-opaque'] as $inhalt) {
            self::assertStringContainsString($inhalt, $template);
        }
        self::assertStringContainsString('mgd-display__label', $template);
        self::assertStringContainsString('aria-hidden="true"', $template);
        self::assertStringContainsString('Deine Shopbilder bleiben unverändert.', $template);
        self::assertStringNotContainsString('aria-live', $template, 'Das zweite Label soll keine doppelte Screenreader-Meldung auslösen.');
        self::assertDoesNotMatchRegularExpression('~<(?:input|select|form)|\bon\w+\s*=|https?://~i', $template);
    }

    #[Test]
    public function detail_css_bleibt_lokal_zentriert_und_waechst_mit_dem_vergroesserten_label(): void
    {
        $css = $this->lese('display-detail-preview.css');
        self::assertDoesNotMatchRegularExpression('~url\s*\(|@import|repeating-conic-gradient~i', $css);
        self::assertStringContainsString('repeating-linear-gradient', $css);
        self::assertStringContainsString('linear-gradient', $css);
        self::assertStringContainsString('zoom: 2;', $css);
        self::assertStringContainsString('min-height: 90px;', $css);
        self::assertStringContainsString('place-items: center;', $css);
        self::assertStringContainsString('@supports not', $css);
        self::assertStringContainsString('-webkit-backdrop-filter', $css);
        self::assertStringContainsString('overflow-wrap: anywhere;', $css);
        // Kein fester Deckel: umgebrochene Labels dürfen die Lupenbox höher machen.
        self::assertDoesNotMatchRegularExpression('~(?:^|[;{\s])(?:height|max-height):~m', $css);
    }

    #[Test]
    public function produktbild_und_label_bleiben_im_gleichen_gridbereich(): void
    {
        $css = $this->lese('display.css');
        self::assertStringContainsString('grid-area: 1 / 1;', $css);
        self::assertStringContainsString('position: relative;', $css);
        self::assertStringContainsString('object-fit: contain;', $css);
        self::assertStringNotContainsString('repeating-conic-gradient', $css);
        foreach (['top-left', 'top-right', 'bottom-left', 'bottom-right'] as $position) {
            self::assertStringContainsString('.mgd-display-preview--' . $position, $css);
        }
        self::assertStringContainsString('color: var(--mgd-display-text);', $css);
    }

    #[Test]
    public function geaenderte_ressourcen_haben_eine_echte_inhaltskennung(): void
    {
        $template = $this->lese('templates/display.tpl');
        foreach (['display.css', 'display-detail-preview.css', 'js/display-controls.mjs'] as $datei) {
            self::assertFileExists(self::ADMIN . $datei);
            $hash = substr((string) hash_file('sha256', self::ADMIN . $datei), 0, 12);
            self::assertStringContainsString($datei . '?v=' . $hash, $template);
        }
    }

    /** Liefert bei fehlender Datei eine verständliche Testverletzung statt PHP-Warnungen. */
    private function lese(string $name): string
    {
        self::assertFileExists(self::ADMIN . $name);

        return (string) file_get_contents(self::ADMIN . $name);
    }
}
