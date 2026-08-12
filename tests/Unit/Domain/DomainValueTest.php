<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Domain\AssetSource;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelLanguage;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelPosition;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelStatus;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelTheme;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelView;
use ReflectionClass;
use ReflectionException;
use TypeError;

final class DomainValueTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function statusEingaben(): iterable
    {
        yield 'ungeprüft' => ['unreviewed', 'unreviewed'];
        yield 'ohne Kennzeichnung' => ['none', 'none'];
        yield 'vollständig generiert' => ['generated', 'generated'];
        yield 'teilweise generiert' => ['partially-generated', 'partially-generated'];
        yield 'bearbeitet' => ['modified', 'modified'];
        yield 'Deepfake' => ['deepfake', 'deepfake'];
        yield 'Großschreibung und Leerraum' => ['  DEEPFAKE  ', 'deepfake'];
        yield 'unbekannter Text' => ['eigene-css-klasse', 'unreviewed'];
        yield 'Ganzzahl' => [123, 'unreviewed'];
        yield 'Array' => [['generated'], 'unreviewed'];
        yield 'Null' => [null, 'unreviewed'];
    }

    #[Test]
    #[DataProvider('statusEingaben')]
    public function status_normalisiert_erlaubte_und_manipulierte_eingaben(mixed $eingabe, string $erwartet): void
    {
        $this->erwarteEnum(LabelStatus::class);

        self::assertSame($erwartet, LabelStatus::fromInput($eingabe)->value);
    }

    #[Test]
    public function ausschließlich_inhaltlich_gekennzeichnete_status_sind_sichtbar(): void
    {
        $this->erwarteEnum(LabelStatus::class);

        self::assertFalse(LabelStatus::Unreviewed->isVisible());
        self::assertFalse(LabelStatus::None->isVisible());
        self::assertTrue(LabelStatus::Generated->isVisible());
        self::assertTrue(LabelStatus::PartiallyGenerated->isVisible());
        self::assertTrue(LabelStatus::Modified->isVisible());
        self::assertTrue(LabelStatus::Deepfake->isVisible());
    }

    /**
     * @return iterable<string, array{mixed, string, string}>
     */
    public static function positionsEingaben(): iterable
    {
        yield 'oben links' => ['top-left', 'top-left', 'mgd-ai-label--position-top-left'];
        yield 'oben rechts' => ['top-right', 'top-right', 'mgd-ai-label--position-top-right'];
        yield 'unten links' => ['bottom-left', 'bottom-left', 'mgd-ai-label--position-bottom-left'];
        yield 'unten rechts' => ['bottom-right', 'bottom-right', 'mgd-ai-label--position-bottom-right'];
        yield 'normalisierte Großschreibung' => [' TOP-LEFT ', 'top-left', 'mgd-ai-label--position-top-left'];
        yield 'eingeschleuste Klasse' => ['bottom-right eigene-klasse', 'bottom-right', 'mgd-ai-label--position-bottom-right'];
        yield 'nicht-string Eingabe' => [false, 'bottom-right', 'mgd-ai-label--position-bottom-right'];
    }

    #[Test]
    #[DataProvider('positionsEingaben')]
    public function position_liefert_nur_geschlossene_werte_und_feste_klassen(
        mixed $eingabe,
        string $erwarteterWert,
        string $erwarteteKlasse,
    ): void {
        $this->erwarteEnum(LabelPosition::class);

        $position = LabelPosition::fromInput($eingabe);

        self::assertSame($erwarteterWert, $position->value);
        self::assertSame($erwarteteKlasse, $position->cssClass());
    }

    /**
     * @return iterable<string, array{mixed, string, string}>
     */
    public static function themeEingaben(): iterable
    {
        yield 'automatisch' => ['auto', 'auto', 'mgd-ai-label--theme-auto'];
        yield 'hell' => ['light', 'light', 'mgd-ai-label--theme-light'];
        yield 'dunkel' => ['dark', 'dark', 'mgd-ai-label--theme-dark'];
        yield 'normalisierte Großschreibung' => [' DARK ', 'dark', 'mgd-ai-label--theme-dark'];
        yield 'unbekannt' => ['transparent', 'auto', 'mgd-ai-label--theme-auto'];
        yield 'nicht-string Eingabe' => [new \stdClass(), 'auto', 'mgd-ai-label--theme-auto'];
    }

    #[Test]
    #[DataProvider('themeEingaben')]
    public function theme_liefert_nur_geschlossene_werte_und_feste_klassen(
        mixed $eingabe,
        string $erwarteterWert,
        string $erwarteteKlasse,
    ): void {
        $this->erwarteEnum(LabelTheme::class);

        $theme = LabelTheme::fromInput($eingabe);

        self::assertSame($erwarteterWert, $theme->value);
        self::assertSame($erwarteteKlasse, $theme->cssClass());
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function sprachEingaben(): iterable
    {
        yield 'automatisch' => ['auto', 'auto'];
        yield 'Deutsch' => ['de', 'de'];
        yield 'Englisch' => ['en', 'en'];
        yield 'normalisierte Großschreibung' => [' EN ', 'en'];
        yield 'nicht unterstützte Sprache' => ['fr', 'auto'];
        yield 'nicht-string Eingabe' => [42, 'auto'];
    }

    #[Test]
    #[DataProvider('sprachEingaben')]
    public function sprache_normalisiert_ausschließlich_die_erlaubten_werte(mixed $eingabe, string $erwartet): void
    {
        $this->erwarteEnum(LabelLanguage::class);

        self::assertSame($erwartet, LabelLanguage::fromInput($eingabe)->value);
    }

    #[Test]
    public function automatische_sprache_folgt_deutschen_kontexten_und_nutzt_sonst_englisch(): void
    {
        $this->erwarteEnum(LabelLanguage::class);

        self::assertSame(LabelLanguage::De, LabelLanguage::Auto->resolve('de'));
        self::assertSame(LabelLanguage::De, LabelLanguage::Auto->resolve('de-DE'));
        self::assertSame(LabelLanguage::De, LabelLanguage::Auto->resolve(' DE-DE '));
        self::assertSame(LabelLanguage::En, LabelLanguage::Auto->resolve('de-AT'));
        self::assertSame(LabelLanguage::En, LabelLanguage::Auto->resolve('de-CH'));
        self::assertSame(LabelLanguage::En, LabelLanguage::Auto->resolve('de_CH'));
        self::assertSame(LabelLanguage::En, LabelLanguage::Auto->resolve('de_DE'));
        self::assertSame(LabelLanguage::En, LabelLanguage::Auto->resolve('en-US'));
        self::assertSame(LabelLanguage::En, LabelLanguage::Auto->resolve('fr-FR'));
        self::assertSame(LabelLanguage::En, LabelLanguage::Auto->resolve(['de-DE']));
        self::assertSame(LabelLanguage::De, LabelLanguage::De->resolve('en-US'));
        self::assertSame(LabelLanguage::En, LabelLanguage::En->resolve('de-DE'));
    }

    /**
     * @return iterable<string, array{mixed, string, string}>
     */
    public static function quellenEingaben(): iterable
    {
        yield 'Produkt' => ['product', 'product', 'mgd-ai-label--source-product'];
        yield 'Kategorie' => ['category', 'category', 'mgd-ai-label--source-category'];
        yield 'Hersteller' => ['manufacturer', 'manufacturer', 'mgd-ai-label--source-manufacturer'];
        yield 'Banner' => ['banner', 'banner', 'mgd-ai-label--source-banner'];
        yield 'OnPage Composer' => ['opc', 'opc', 'mgd-ai-label--source-opc'];
        yield 'lokal manuell' => [
            'custom-local-manual',
            'custom-local-manual',
            'mgd-ai-label--source-custom-local-manual',
        ];
        yield 'alte unvereinbarte Variante' => ['custom-local', 'unknown', 'mgd-ai-label--source-unknown'];
        yield 'normalisierte Großschreibung' => [' PRODUCT ', 'product', 'mgd-ai-label--source-product'];
        yield 'eingeschleuste Quelle' => ['remote<script>', 'unknown', 'mgd-ai-label--source-unknown'];
        yield 'nicht-string Eingabe' => [['product'], 'unknown', 'mgd-ai-label--source-unknown'];
    }

    #[Test]
    #[DataProvider('quellenEingaben')]
    public function quelle_liefert_nur_positiv_gelistete_werte_und_feste_klassen(
        mixed $eingabe,
        string $erwarteterWert,
        string $erwarteteKlasse,
    ): void {
        $this->erwarteEnum(AssetSource::class);

        $quelle = AssetSource::fromInput($eingabe);

        self::assertSame($erwarteterWert, $quelle->value);
        self::assertSame($erwarteteKlasse, $quelle->cssClass());
    }

    #[Test]
    public function label_view_ist_ein_unveränderliches_endgültiges_datenmodell(): void
    {
        $this->erwarteKlasse(LabelView::class);

        $reflexion = new ReflectionClass(LabelView::class);

        self::assertTrue($reflexion->isFinal());
        foreach ($reflexion->getProperties() as $eigenschaft) {
            self::assertTrue(
                $eigenschaft->isReadOnly(),
                sprintf('Die Eigenschaft %s muss unter PHP 8.1 readonly sein.', $eigenschaft->getName()),
            );
        }
    }

    #[Test]
    public function label_view_konstruktor_ist_für_aufrufer_nicht_zugänglich(): void
    {
        $reflexion = new ReflectionClass(LabelView::class);
        $konstruktor = $reflexion->getConstructor();

        self::assertNotNull($konstruktor);
        self::assertTrue($konstruktor->isPrivate());

        $this->expectException(ReflectionException::class);
        $reflexion->newInstance(
            true,
            '<script>frei</script>',
            '<b>frei</b>',
            'fremde-position',
            'fremdes-theme',
            'fremde-quelle',
            999,
            999,
            999,
            999,
            999,
        );
    }

    #[Test]
    public function sichere_factory_erzeugt_nur_kontrollierte_inhalte_klassen_und_begrenzte_zahlen(): void
    {
        $reflexion = new ReflectionClass(LabelView::class);
        self::assertTrue($reflexion->hasMethod('forVisibleLabel'));

        $view = LabelView::forVisibleLabel(
            LabelStatus::Deepfake,
            LabelLanguage::De,
            LabelPosition::TopLeft,
            LabelTheme::Dark,
            AssetSource::CustomLocalManual,
            999,
            -10,
            999,
            -10,
            999,
        );

        self::assertTrue($view->visible);
        self::assertSame('KI-DEEPFAKE', $view->visibleText);
        self::assertSame(
            'Dieser Inhalt ist ein mit künstlicher Intelligenz erzeugter oder manipulierter Deepfake.',
            $view->assistiveText,
        );
        self::assertSame('mgd-ai-label--position-top-left', $view->positionClass);
        self::assertSame('mgd-ai-label--theme-dark', $view->themeClass);
        self::assertSame('mgd-ai-label--source-custom-local-manual', $view->sourceClass);
        self::assertSame([48, 0, 32, 0, 24], [
            $view->fontSize,
            $view->outerMargin,
            $view->innerPadding,
            $view->borderRadius,
            $view->blur,
        ]);
        self::assertStringNotContainsString('<', $view->visibleText . $view->assistiveText);
        self::assertStringNotContainsString('>', $view->visibleText . $view->assistiveText);
    }

    #[Test]
    public function sichere_factory_weist_freie_statuswerte_durch_typisierung_zurück(): void
    {
        $reflexion = new ReflectionClass(LabelView::class);
        self::assertTrue($reflexion->hasMethod('forVisibleLabel'));
        $factory = $reflexion->getMethod('forVisibleLabel');

        $this->expectException(TypeError::class);
        $factory->invoke(
            null,
            'generated',
            LabelLanguage::De,
            LabelPosition::TopLeft,
            LabelTheme::Dark,
            AssetSource::Product,
            12,
            8,
            6,
            4,
            0,
        );
    }

    #[Test]
    public function sichere_factory_macht_unsichtbare_status_vollständig_leer(): void
    {
        $reflexion = new ReflectionClass(LabelView::class);
        self::assertTrue($reflexion->hasMethod('forVisibleLabel'));

        $view = LabelView::forVisibleLabel(
            LabelStatus::Unreviewed,
            LabelLanguage::De,
            LabelPosition::TopLeft,
            LabelTheme::Dark,
            AssetSource::Product,
            48,
            64,
            32,
            32,
            24,
        );

        self::assertFalse($view->visible);
        self::assertSame('', $view->visibleText);
        self::assertSame('', $view->assistiveText);
        self::assertSame('', $view->positionClass);
        self::assertSame('', $view->themeClass);
        self::assertSame('', $view->sourceClass);
        self::assertSame([0, 0, 0, 0, 0], [
            $view->fontSize,
            $view->outerMargin,
            $view->innerPadding,
            $view->borderRadius,
            $view->blur,
        ]);
    }

    #[Test]
    public function bereits_normalisierte_enumwerte_bleiben_unverändert(): void
    {
        self::assertSame(LabelStatus::Deepfake, LabelStatus::fromInput(LabelStatus::Deepfake));
        self::assertSame(LabelPosition::TopLeft, LabelPosition::fromInput(LabelPosition::TopLeft));
        self::assertSame(LabelTheme::Dark, LabelTheme::fromInput(LabelTheme::Dark));
        self::assertSame(LabelLanguage::De, LabelLanguage::fromInput(LabelLanguage::De));
        self::assertSame(AssetSource::Product, AssetSource::fromInput(AssetSource::Product));
    }

    /**
     * Stellt sicher, dass ein fehlendes Enum als verständlicher Rotlauf und
     * nicht als schwer einzuordnender Autoload-Fehler sichtbar wird.
     *
     * @param class-string $enum
     */
    private function erwarteEnum(string $enum): void
    {
        self::assertTrue(enum_exists($enum), sprintf('Das Enum %s muss implementiert werden.', $enum));
    }

    /**
     * Stellt sicher, dass eine fehlende Klasse im Rotlauf klar benannt wird.
     *
     * @param class-string $klasse
     */
    private function erwarteKlasse(string $klasse): void
    {
        self::assertTrue(class_exists($klasse), sprintf('Die Klasse %s muss implementiert werden.', $klasse));
    }
}
