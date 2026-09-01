<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelLanguage;
use Plugin\MGD_AI_Kennzeichnung\Service\DisplaySettings;
use ReflectionClass;

final class DisplaySettingsTest extends TestCase
{
    private const INFO_XML = __DIR__ . '/../../../plugin/MGD_AI_Kennzeichnung/info.xml';

    #[Test]
    public function sichere_standardwerte_sind_unveraenderlich_und_datenschutzfreundlich(): void
    {
        $this->erwarteEinstellungenKlasse();

        $einstellungen = DisplaySettings::fromInput([]);
        $reflexion = new ReflectionClass($einstellungen);

        self::assertTrue($reflexion->isFinal());
        foreach ($reflexion->getProperties() as $eigenschaft) {
            self::assertTrue($eigenschaft->isReadOnly());
        }

        self::assertFalse($einstellungen->showCredit);
        self::assertFalse($einstellungen->updateNoticesEnabled);
        self::assertSame(LabelLanguage::Auto, $einstellungen->language);
        self::assertSame([12, 8, 6, 4, 0, 8], $this->zahlen($einstellungen));
        self::assertFalse(property_exists($einstellungen, 'position'));
        self::assertFalse(property_exists($einstellungen, 'theme'));
    }

    #[Test]
    public function direkte_eingaben_akzeptieren_nur_strikte_typen_und_geschlossene_werte(): void
    {
        $this->erwarteEinstellungenKlasse();

        $gueltig = DisplaySettings::fromInput([
            'showCredit' => true,
            'updateNoticesEnabled' => true,
            'language' => 'de',
            'fontSize' => 48,
            'outerMargin' => 64,
            'innerPadding' => 32,
            'borderRadius' => 32,
            'blur' => 24,
            'transparency' => 90,
            'cssClass' => 'eingeschleust',
        ]);

        self::assertTrue($gueltig->showCredit);
        self::assertTrue($gueltig->updateNoticesEnabled);
        self::assertSame(LabelLanguage::De, $gueltig->language);
        self::assertSame([48, 64, 32, 32, 24, 90], $this->zahlen($gueltig));
        self::assertFalse(property_exists($gueltig, 'cssClass'));

        $manipuliert = DisplaySettings::fromInput([
            'showCredit' => 'Y',
            'updateNoticesEnabled' => 1,
            'language' => ' DE ',
            'fontSize' => '48',
            'outerMargin' => 1.5,
            'innerPadding' => 'NaN',
            'borderRadius' => INF,
            'blur' => '<24>',
            'transparency' => '90',
        ]);

        self::assertFalse($manipuliert->showCredit);
        self::assertFalse($manipuliert->updateNoticesEnabled);
        self::assertSame(LabelLanguage::Auto, $manipuliert->language);
        self::assertSame([12, 8, 6, 4, 0, 8], $this->zahlen($manipuliert));
    }

    #[Test]
    public function direkte_ganzzahlen_werden_auf_die_label_view_grenzen_begrenzt(): void
    {
        $this->erwarteEinstellungenKlasse();

        $unten = DisplaySettings::fromInput([
            'fontSize' => PHP_INT_MIN,
            'outerMargin' => -1,
            'innerPadding' => -1,
            'borderRadius' => -1,
            'blur' => -1,
            'transparency' => -1,
        ]);
        $oben = DisplaySettings::fromInput([
            'fontSize' => PHP_INT_MAX,
            'outerMargin' => PHP_INT_MAX,
            'innerPadding' => PHP_INT_MAX,
            'borderRadius' => PHP_INT_MAX,
            'blur' => PHP_INT_MAX,
            'transparency' => PHP_INT_MAX,
        ]);

        self::assertSame([8, 0, 0, 0, 0, 0], $this->zahlen($unten));
        self::assertSame([48, 64, 32, 32, 24, 90], $this->zahlen($oben));
    }

    #[Test]
    public function jtl_adapter_akzeptiert_nur_kanonische_konfigurationsstrings(): void
    {
        $this->erwarteEinstellungenKlasse();

        $gueltig = DisplaySettings::fromJtlConfig([
            'show_credit' => 'Y',
            'update_notices' => 'Y',
            'language' => 'en',
            'font_size' => '18',
            'outer_margin' => '14',
            'inner_padding' => '10',
            'border_radius' => '7',
            'blur' => '5',
            'transparency' => '8',
        ]);

        self::assertTrue($gueltig->showCredit);
        self::assertTrue($gueltig->updateNoticesEnabled);
        self::assertSame(LabelLanguage::En, $gueltig->language);
        self::assertSame([18, 14, 10, 7, 5, 8], $this->zahlen($gueltig));

        $manipuliert = DisplaySettings::fromJtlConfig([
            'show_credit' => 'yes',
            'update_notices' => '1',
            'font_size' => '12px',
            'outer_margin' => '1.5',
            'inner_padding' => 'NaN',
            'border_radius' => [],
            'blur' => ' 5 ',
            'transparency' => '8px',
        ]);

        self::assertFalse($manipuliert->showCredit);
        self::assertFalse($manipuliert->updateNoticesEnabled);
        self::assertSame([12, 8, 6, 4, 0, 8], $this->zahlen($manipuliert));
    }

    #[Test]
    public function jtl_transparenz_wird_streng_normalisiert_und_auf_den_sicheren_bereich_begrenzt(): void
    {
        $this->erwarteEinstellungenKlasse();

        self::assertSame(0, DisplaySettings::fromJtlConfig(['transparency' => '-1'])->transparency);
        self::assertSame(90, DisplaySettings::fromJtlConfig(['transparency' => '91'])->transparency);
        self::assertSame(8, DisplaySettings::fromJtlConfig(['transparency' => '8px'])->transparency);
    }

    #[Test]
    public function info_xml_verwendet_offizielle_jtl_settinglinks_mit_sicheren_defaults(): void
    {
        $dokument = new DOMDocument();
        self::assertTrue($dokument->load(self::INFO_XML));
        $xpath = new DOMXPath($dokument);

        $settingslinks = $xpath->query('/jtlshopplugin/Install/Adminmenu/Settingslink');
        self::assertNotFalse($settingslinks);
        self::assertCount(1, $settingslinks);

        $erwarteteDefaults = [
            'show_credit' => 'N',
            'update_notices' => 'Y',
            'language' => 'auto',
            'font_size' => '12',
            'outer_margin' => '8',
            'inner_padding' => '6',
            'border_radius' => '4',
            'blur' => '0',
            'transparency' => '8',
        ];

        foreach ($erwarteteDefaults as $name => $standard) {
            $abfrage = sprintf(
                '/jtlshopplugin/Install/Adminmenu/Settingslink/Setting[ValueName="%s"]',
                $name,
            );
            $knoten = $xpath->query($abfrage);
            self::assertNotFalse($knoten);
            self::assertCount(1, $knoten, sprintf('Die Einstellung %s muss genau einmal existieren.', $name));
            $element = $knoten->item(0);
            self::assertInstanceOf(DOMElement::class, $element);
            self::assertSame('Y', $element->attributes->getNamedItem('conf')?->nodeValue);
            self::assertSame($standard, $element->attributes->getNamedItem('initialValue')?->nodeValue);
        }

        foreach (['language', 'font_size', 'outer_margin', 'inner_padding', 'border_radius', 'blur', 'transparency'] as $name) {
            $nodes = $xpath->query(sprintf('/jtlshopplugin/Install/Adminmenu/Settingslink/Setting[ValueName="%s"]', $name));
            if ($nodes === false) {
                self::fail(sprintf('Die XML-Abfrage für %s konnte nicht ausgeführt werden.', $name));
            }
            $element = $nodes->item(0);
            self::assertInstanceOf(DOMElement::class, $element);
            self::assertSame('none', $element->getAttribute('type'));
            self::assertSame('Y', $element->getAttribute('conf'));
        }
        foreach (['show_credit', 'update_notices'] as $name) {
            $nodes = $xpath->query(sprintf('/jtlshopplugin/Install/Adminmenu/Settingslink/Setting[ValueName="%s"]', $name));
            if ($nodes === false) {
                self::fail(sprintf('Die XML-Abfrage für %s konnte nicht ausgeführt werden.', $name));
            }
            $element = $nodes->item(0);
            self::assertInstanceOf(DOMElement::class, $element);
            self::assertSame('selectbox', $element->getAttribute('type'));
        }
        $updateDescription = $xpath->evaluate('string(/jtlshopplugin/Install/Adminmenu/Settingslink/Setting[ValueName="update_notices"]/Description)');
        self::assertIsString($updateDescription);
        foreach (['Standardmäßig aktiviert', 'Server-zu-GitHub', 'Server-IP', 'Zeitpunkt', 'User-Agent', 'keine Tokens', 'keine Shop-, Kunden- oder Formulardaten', 'öffentlichen GitHub-Repository'] as $phrase) {
            self::assertStringContainsString($phrase, $updateDescription);
        }
        self::assertStringNotContainsString('privates Repository', $updateDescription);
        self::assertStringNotContainsString('anonym', strtolower($updateDescription));
        self::assertSame('', $xpath->evaluate('string(/jtlshopplugin/Install/Adminmenu/Settingslink/Setting[ValueName="position"])'));
        self::assertSame('', $xpath->evaluate('string(/jtlshopplugin/Install/Adminmenu/Settingslink/Setting[ValueName="theme"])'));

        $version = $xpath->evaluate('string(/jtlshopplugin/Version)');
        $dateiname = $xpath->evaluate('string(/jtlshopplugin/Install/Adminmenu/Customlink[Name="Darstellung"]/Filename)');
        self::assertIsString($version);
        self::assertIsString($dateiname);
        self::assertSame('1.3.3', trim($version));
        self::assertSame('display.php', trim($dateiname));
    }

    /** @return array{int, int, int, int, int, int} */
    private function zahlen(DisplaySettings $einstellungen): array
    {
        return [
            $einstellungen->fontSize,
            $einstellungen->outerMargin,
            $einstellungen->innerPadding,
            $einstellungen->borderRadius,
            $einstellungen->blur,
            $einstellungen->transparency,
        ];
    }

    private function erwarteEinstellungenKlasse(): void
    {
        self::assertTrue(
            class_exists(DisplaySettings::class),
            sprintf('Die Klasse %s muss implementiert werden.', DisplaySettings::class),
        );
    }
}
