<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use Error;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelView;
use Plugin\MGD_AI_Kennzeichnung\Service\LabelViewResolver;
use ReflectionProperty;

final class LabelViewResolverTest extends TestCase
{
    #[Test]
    public function jtl_sprachcode_ger_wird_als_deutsch_aufgeloest(): void
    {
        $view = (new LabelViewResolver())->resolve('generated', language: 'auto', locale: 'ger');

        self::assertSame('KI-GENERIERT', $view->visibleText);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function unsichtbareStatusEingaben(): iterable
    {
        yield 'ungeprüft' => ['unreviewed'];
        yield 'ohne Kennzeichnung' => ['none'];
        yield 'unbekannter Text' => ['sichtbar eigene-klasse'];
        yield 'nicht-string Eingabe' => [['generated']];
    }

    #[Test]
    #[DataProvider('unsichtbareStatusEingaben')]
    public function unsichere_oder_unsichtbare_status_erzeugen_eine_leere_ausgabe(mixed $status): void
    {
        $resolver = $this->erstelleResolver();

        $view = $resolver->resolve(
            status: $status,
            position: 'top-left',
            theme: 'dark',
            language: 'de',
            locale: 'de-DE',
            assetSource: 'product',
            fontSize: 99,
            outerMargin: 99,
            innerPadding: 99,
            borderRadius: 99,
            blur: 99,
            transparency: 90,
        );

        self::assertFalse($view->visible);
        self::assertSame('', $view->visibleText);
        self::assertSame('', $view->assistiveText);
        self::assertSame('', $view->positionClass);
        self::assertSame('', $view->themeClass);
        self::assertSame('', $view->sourceClass);
        self::assertSame(0, $view->fontSize);
        self::assertSame(0, $view->outerMargin);
        self::assertSame(0, $view->innerPadding);
        self::assertSame(0, $view->borderRadius);
        self::assertSame(0, $view->blur);
        self::assertSame(0, $view->transparency);
        self::assertSame('1.00', $view->backgroundOpacity);
    }

    /**
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function sichtbareTexte(): iterable
    {
        yield 'generiert Deutsch' => [
            'generated',
            'de',
            'KI-GENERIERT',
            'Dieser Inhalt wurde vollständig mit künstlicher Intelligenz erzeugt.',
        ];
        yield 'generiert Englisch' => [
            'generated',
            'en',
            'AI-GENERATED',
            'This content was generated entirely using artificial intelligence.',
        ];
        yield 'teilweise generiert Deutsch' => [
            'partially-generated',
            'de',
            'TEILWEISE KI-GENERIERT',
            'Dieser Inhalt wurde teilweise mit künstlicher Intelligenz erzeugt.',
        ];
        yield 'teilweise generiert Englisch' => [
            'partially-generated',
            'en',
            'PARTIALLY AI-GENERATED',
            'This content was partially generated using artificial intelligence.',
        ];
        yield 'bearbeitet Deutsch' => [
            'modified',
            'de',
            'MIT KI BEARBEITET',
            'Dieser Inhalt wurde mit künstlicher Intelligenz bearbeitet.',
        ];
        yield 'bearbeitet Englisch' => [
            'modified',
            'en',
            'AI-MODIFIED',
            'This content was modified using artificial intelligence.',
        ];
        yield 'Deepfake Deutsch' => [
            'deepfake',
            'de',
            'KI-DEEPFAKE',
            'Dieser Inhalt ist ein mit künstlicher Intelligenz erzeugter oder manipulierter Deepfake.',
        ];
        yield 'Deepfake Englisch' => [
            'deepfake',
            'en',
            'AI DEEPFAKE',
            'This content is a deepfake generated or manipulated using artificial intelligence.',
        ];
    }

    #[Test]
    #[DataProvider('sichtbareTexte')]
    public function sichtbare_status_erhalten_feste_deutsche_und_englische_texte(
        string $status,
        string $sprache,
        string $sichtbarerText,
        string $assistiverText,
    ): void {
        $view = $this->erstelleResolver()->resolve(status: $status, language: $sprache);

        self::assertTrue($view->visible);
        self::assertSame($sichtbarerText, $view->visibleText);
        self::assertSame($assistiverText, $view->assistiveText);
        self::assertStringNotContainsString('<', $view->visibleText . $view->assistiveText);
        self::assertStringNotContainsString('>', $view->visibleText . $view->assistiveText);
    }

    #[Test]
    public function automatische_sprache_verwendet_deutsch_nur_in_deutschen_kontexten(): void
    {
        $resolver = $this->erstelleResolver();

        $deutsch = $resolver->resolve(status: 'deepfake', language: 'auto', locale: 'de-DE');
        $englisch = $resolver->resolve(status: 'deepfake', language: 'auto', locale: 'en-US');

        self::assertSame('KI-DEEPFAKE', $deutsch->visibleText);
        self::assertNotSame('', $deutsch->assistiveText);
        self::assertStringContainsString('künstlicher Intelligenz', $deutsch->assistiveText);
        self::assertSame('AI DEEPFAKE', $englisch->visibleText);
        self::assertStringContainsString('artificial intelligence', $englisch->assistiveText);
    }

    #[Test]
    public function automatische_sprache_verwendet_für_andere_deutsche_regionen_englisch(): void
    {
        $resolver = $this->erstelleResolver();

        foreach (['de-AT', 'de-CH', 'de_CH', 'de_DE'] as $locale) {
            $view = $resolver->resolve(status: 'deepfake', language: 'auto', locale: $locale);

            self::assertSame('AI DEEPFAKE', $view->visibleText, sprintf('Locale %s muss Englisch nutzen.', $locale));
        }
    }

    #[Test]
    public function resolver_leitet_ausschließlich_feste_klassen_aus_geschlossenen_werten_ab(): void
    {
        $resolver = $this->erstelleResolver();

        $erlaubt = $resolver->resolve(
            status: 'generated',
            position: 'top-left',
            theme: 'dark',
            assetSource: 'opc',
        );
        $manipuliert = $resolver->resolve(
            status: 'generated',
            position: 'top-left fremd',
            theme: 'dark<script>',
            assetSource: 'https://fremd.example/klasse',
        );

        self::assertSame('mgd-ai-label--position-top-left', $erlaubt->positionClass);
        self::assertSame('mgd-ai-label--theme-dark', $erlaubt->themeClass);
        self::assertSame('mgd-ai-label--source-opc', $erlaubt->sourceClass);
        self::assertSame('mgd-ai-label--position-bottom-right', $manipuliert->positionClass);
        self::assertSame('mgd-ai-label--theme-auto', $manipuliert->themeClass);
        self::assertSame('mgd-ai-label--source-unknown', $manipuliert->sourceClass);
        self::assertStringNotContainsString('fremd', implode(' ', [
            $manipuliert->positionClass,
            $manipuliert->themeClass,
            $manipuliert->sourceClass,
        ]));
    }

    /**
     * @return iterable<string, array{mixed, mixed, mixed, mixed, mixed, array{int, int, int, int, int}}>
     */
    public static function darstellungsEingaben(): iterable
    {
        yield 'Untergrenzen' => [-50, -1, -5, -9, -12, [8, 0, 0, 0, 0]];
        yield 'Obergrenzen' => [500, 200, 100, 90, 75, [48, 64, 32, 32, 24]];
        yield 'gültige Ganzzahlen' => [16, 12, 8, 6, 4, [16, 12, 8, 6, 4]];
        yield 'Ganzzahl-Strings aus Einstellungen' => ['18', '14', '10', '7', '5', [18, 14, 10, 7, 5]];
        yield 'manipulierte Werte verwenden Defaults' => ['12px', '<8>', [], null, 4.5, [12, 8, 6, 4, 0]];
    }

    /**
     * @param array{int, int, int, int, int} $erwartet Erwartete normalisierte Zahlenwerte
     */
    #[Test]
    #[DataProvider('darstellungsEingaben')]
    public function darstellungswerte_werden_als_begrenzte_ganzzahlen_normalisiert(
        mixed $schriftgroesse,
        mixed $aussenabstand,
        mixed $innenabstand,
        mixed $radius,
        mixed $blur,
        array $erwartet,
    ): void {
        $view = $this->erstelleResolver()->resolve(
            status: 'generated',
            fontSize: $schriftgroesse,
            outerMargin: $aussenabstand,
            innerPadding: $innenabstand,
            borderRadius: $radius,
            blur: $blur,
        );

        self::assertSame($erwartet, [
            $view->fontSize,
            $view->outerMargin,
            $view->innerPadding,
            $view->borderRadius,
            $view->blur,
        ]);
    }

    #[Test]
    public function erzeugtes_view_modell_kann_nachträglich_nicht_verändert_werden(): void
    {
        $view = $this->erstelleResolver()->resolve(status: 'generated');
        $eigenschaft = new ReflectionProperty($view, 'visibleText');

        $this->expectException(Error::class);
        $eigenschaft->setValue($view, 'eingeschleuster Text');
    }

    #[Test]
    public function transparenz_wird_im_sichtbaren_modell_begrenzt_und_als_deckkraft_abgeleitet(): void
    {
        $resolver = $this->erstelleResolver();

        $standard = $resolver->resolve(status: 'generated', transparency: 8);
        $deckend = $resolver->resolve(status: 'generated', transparency: 0);
        $transparent = $resolver->resolve(status: 'generated', transparency: 90);

        self::assertSame(8, $standard->transparency);
        self::assertSame('0.92', $standard->backgroundOpacity);
        self::assertSame('1.00', $deckend->backgroundOpacity);
        self::assertSame('0.10', $transparent->backgroundOpacity);
    }

    /**
     * Liefert erst nach einer verständlichen Existenzprüfung den echten Dienst.
     * Damit dokumentiert der initiale Rotlauf eindeutig die fehlende Funktion.
     */
    private function erstelleResolver(): LabelViewResolver
    {
        self::assertTrue(
            class_exists(LabelViewResolver::class),
            sprintf('Die Klasse %s muss implementiert werden.', LabelViewResolver::class),
        );
        self::assertTrue(
            class_exists(LabelView::class),
            sprintf('Die Klasse %s muss implementiert werden.', LabelView::class),
        );

        return new LabelViewResolver();
    }
}
