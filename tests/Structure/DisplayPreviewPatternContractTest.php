<?php

declare(strict_types=1);

namespace Tests\Structure;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Sichert die rein lokale Muster-Vorschau und die vom JTL-Theme unabhängige Kopfzeile ab. */
final class DisplayPreviewPatternContractTest extends TestCase
{
    private const ADMIN = __DIR__ . '/../../plugin/MGD_AI_Kennzeichnung/adminmenu/';

    #[Test]
    public function muster_stylesheet_wird_lokal_nach_dem_bestehenden_stylesheet_eingebunden(): void
    {
        $template = (string) file_get_contents(self::ADMIN . 'templates/display.tpl');

        self::assertMatchesRegularExpression(
            '~<link rel="stylesheet" href="\{\$adminUrl\|escape:\'html\':\'UTF-8\'\}display\.css">\s*'
            . '<link rel="stylesheet" href="\{\$adminUrl\|escape:\'html\':\'UTF-8\'\}display-preview-pattern\.css">~',
            $template,
        );
    }

    #[Test]
    public function muster_benoetigt_keine_externen_ressourcen_und_bleibt_im_darstellungstab(): void
    {
        $stylesheet = $this->leseMusterStylesheet();

        self::assertDoesNotMatchRegularExpression('~url\s*\(|@import~i', $stylesheet);
        self::assertStringContainsString('repeating-conic-gradient(#e9eeeb 0% 25%, #74867b 0% 50%)', $stylesheet);
        self::assertStringContainsString('background-size: 24px 24px;', $stylesheet);

        // Jeder echte CSS-Selektor muss einen Nachfahren unseres eigenen Tab-Containers betreffen.
        $ohneKommentare = (string) preg_replace('~/\*.*?\*/~s', '', $stylesheet);
        preg_match_all('~([^{}]+)\{~', $ohneKommentare, $regeln);
        self::assertNotEmpty($regeln[1]);
        foreach ($regeln[1] as $selektoren) {
            foreach (explode(',', trim($selektoren)) as $selektor) {
                self::assertStringStartsWith('.mgd-display ', trim($selektor));
            }
        }
    }

    #[Test]
    public function bild_bleibt_proportional_zentriert_zwischen_mitwachsenden_musterstreifen(): void
    {
        $stylesheet = $this->leseMusterStylesheet();
        $rahmen = $this->leseRegel($stylesheet, '.mgd-display .mgd-display__image-wrap');
        $bild = $this->leseRegel($stylesheet, '.mgd-display .mgd-display__image-wrap img');

        self::assertStringContainsString('display: grid;', $rahmen);
        self::assertStringContainsString('grid-template-columns: 1rem minmax(0, 1fr) 1rem;', $rahmen);
        self::assertMatchesRegularExpression(
            '~grid-template-rows:\s*minmax\(var\(--mgd-preview-pattern-space\), 1fr\)\s*auto\s*minmax\(var\(--mgd-preview-pattern-space\), 1fr\);~',
            $rahmen,
        );
        foreach (['var(--mgd-preview-outer-margin)', 'var(--mgd-preview-inner-padding)', 'var(--mgd-preview-font-size) * 1.2', '1rem'] as $bestandteil) {
            self::assertStringContainsString($bestandteil, $rahmen);
        }
        self::assertSame(2, substr_count($rahmen, 'var(--mgd-preview-inner-padding)'));
        foreach (['grid-column: 2;', 'grid-row: 2;', 'place-self: center;', 'width: 100%;', 'height: auto;', 'object-fit: contain;'] as $eigenschaft) {
            self::assertStringContainsString($eigenschaft, $bild);
        }
    }

    #[Test]
    public function jede_labelposition_nutzt_den_musterstreifen_auch_bei_zeilenumbruch(): void
    {
        $stylesheet = $this->leseMusterStylesheet();
        $label = $this->leseRegel($stylesheet, '.mgd-display .mgd-display__label');

        // Ein reguläres Grid-Kind vergrößert seine Zeile; absolute Positionierung würde das verhindern.
        foreach (['position: relative;', 'inset: auto;', 'grid-column: 1 / -1;', 'margin: var(--mgd-preview-outer-margin);', 'width: fit-content;', 'min-width: 0;'] as $eigenschaft) {
            self::assertStringContainsString($eigenschaft, $label);
        }
        foreach (['top-right' => ['1', 'start', 'end'], 'top-left' => ['1', 'start', 'start'], 'bottom-right' => ['3', 'end', 'end'], 'bottom-left' => ['3', 'end', 'start']] as $position => [$zeile, $vertikal, $horizontal]) {
            $regel = $this->leseRegel($stylesheet, '.mgd-display .mgd-display-preview--' . $position . ' .mgd-display__label');
            self::assertStringContainsString('grid-row: ' . $zeile . ';', $regel);
            self::assertStringContainsString('align-self: ' . $vertikal . ';', $regel);
            self::assertStringContainsString('justify-self: ' . $horizontal . ';', $regel);
        }
    }

    #[Test]
    public function kopfzeile_hat_eine_eigene_helle_flaeche_mit_explizit_dunkler_schrift(): void
    {
        $stylesheet = (string) file_get_contents(self::ADMIN . 'display.css');
        $kopf = $this->leseRegel($stylesheet, '.mgd-display .mgd-display__header');

        foreach (['background: var(--mgd-display-surface);', 'color: var(--mgd-display-text);', 'border: 1px solid var(--mgd-display-border);', 'border-radius:', 'padding: 1.25rem;'] as $eigenschaft) {
            self::assertStringContainsString($eigenschaft, $kopf);
        }
        self::assertStringContainsString(
            'color: var(--mgd-display-text);',
            $this->leseRegel($stylesheet, '.mgd-display .mgd-display__header h1'),
        );
    }

    #[Test]
    public function erklaerung_unterscheidet_das_muster_von_den_nicht_gespeicherten_vorschauoptionen(): void
    {
        $template = (string) file_get_contents(self::ADMIN . 'templates/display.tpl');

        self::assertStringContainsString('Muster nur zur Vorschau: So erkennst du Transparenz und Hintergrundunschärfe. Deine Shopbilder bleiben unverändert.', $template);
        self::assertStringContainsString('Die folgenden Optionen ändern nur diese Vorschau und werden nicht gespeichert.', $template);
        self::assertStringContainsString('aria-describedby="mgd-display-preview-pattern-help"', $template);
        self::assertStringContainsString('id="mgd-display-preview-pattern-help"', $template);
    }

    /** Liest erst nach einer verständlichen Existenzprüfung, damit ein fehlendes Stylesheet gezielt rot wird. */
    private function leseMusterStylesheet(): string
    {
        self::assertFileExists(self::ADMIN . 'display-preview-pattern.css');

        return (string) file_get_contents(self::ADMIN . 'display-preview-pattern.css');
    }

    /** Prüft den konkreten Selektor statt beliebiger Texttreffer in anderen Regeln oder Kommentaren. */
    private function leseRegel(string $stylesheet, string $selektor): string
    {
        // Nur ein erfolgreicher Treffer garantiert die Capture-Gruppe mit dem Regelinhalt.
        if (preg_match('~' . preg_quote($selektor, '~') . '\s*\{([^}]+)\}~', $stylesheet, $treffer) !== 1) {
            self::fail('Erwartete CSS-Regel fehlt: ' . $selektor);
        }

        return $treffer[1];
    }
}
