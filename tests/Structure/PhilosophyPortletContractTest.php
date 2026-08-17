<?php

declare(strict_types=1);

namespace Tests\Structure;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PhilosophyPortletContractTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../plugin/MGD_AI_Kennzeichnung';

    #[Test]
    public function portlet_besitzt_die_offizielle_jtl_dateistruktur(): void
    {
        foreach (['AIPhilosophie.php', 'AIPhilosophie.tpl', 'AIPhilosophie.css', 'icon.svg'] as $datei) {
            self::assertFileExists(self::ROOT . '/Portlets/AIPhilosophie/' . $datei);
        }

        $klasse = file_get_contents(self::ROOT . '/Portlets/AIPhilosophie/AIPhilosophie.php');
        self::assertIsString($klasse);
        self::assertStringContainsString('extends Portlet', $klasse);
        self::assertStringContainsString('PhilosophyRepository', $klasse);
        self::assertStringContainsString('Shop::getLanguageCode()', $klasse);
        self::assertStringNotContainsString('getProperty(', $klasse);
    }

    #[Test]
    public function template_gibt_nur_den_bereinigten_datenbankinhalt_aus(): void
    {
        $template = file_get_contents(self::ROOT . '/Portlets/AIPhilosophie/AIPhilosophie.tpl');
        self::assertIsString($template);

        self::assertStringContainsString('$portlet->getSanitizedContent()', $template);
        self::assertStringContainsString('data-portlet=', $template);
        self::assertStringNotContainsString('$instance->getProperty(', $template);
        self::assertStringNotContainsString('<script', strtolower($template));
    }

    #[Test]
    public function adminformular_trennt_deutsch_und_englisch_und_fordert_csrf(): void
    {
        $template = file_get_contents(self::ROOT . '/adminmenu/templates/philosophy.tpl');
        self::assertIsString($template);

        self::assertStringContainsString('name="content_de"', $template);
        self::assertStringContainsString('name="content_en"', $template);
        self::assertStringContainsString('name="csrf_token"', $template);
        self::assertStringNotContainsString('method="get"', strtolower($template));
    }

    #[Test]
    public function plugin_veroeffentlicht_oder_verknuepft_keine_seite_automatisch(): void
    {
        $info = file_get_contents(self::ROOT . '/info.xml');
        self::assertIsString($info);

        self::assertStringNotContainsString('<LinkGroup', $info);
        self::assertStringNotContainsString('<SpecialPage', $info);
        self::assertStringNotContainsString('<Blueprint', $info);
    }
}
