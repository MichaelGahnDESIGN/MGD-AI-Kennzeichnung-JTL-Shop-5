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
    public function opc_editor_laesst_nur_lokale_modulare_kennzeichnung_zu(): void
    {
        $root = self::ROOT . '/Portlets/AIPhilosophie/';
        foreach ([
            'editor_init.js',
            'editor/admin-io-client.mjs',
            'editor/image-field-detector.mjs',
            'editor/opc-integration.mjs',
            'editor/label-dialog.mjs',
            'editor/label-preview.mjs',
            'editor/editor.css',
        ] as $datei) {
            self::assertFileExists($root . $datei);
        }

        $entry = (string) file_get_contents($root . 'editor_init.js');
        $all = $entry;
        foreach (glob($root . 'editor/*') ?: [] as $file) {
            $all .= "\n" . (string) file_get_contents($file);
        }

        self::assertStringContainsString('document.currentScript.src', $entry);
        self::assertStringContainsString("./editor/opc-integration.mjs", $entry);
        self::assertStringNotContainsString('eval(', $all);
        self::assertStringNotContainsString('innerHTML', $all);
        self::assertDoesNotMatchRegularExpression('~https?://(?!shop\.test)~i', $all);
        self::assertDoesNotMatchRegularExpression('/(?:password|passwd|secret|api[_-]?key|private[_-]?key)/i', $all);
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
        $klasse = file_get_contents(self::ROOT . '/Portlets/AIPhilosophie/AIPhilosophie.php');
        self::assertIsString($info);
        self::assertIsString($klasse);

        self::assertStringContainsString('<Class>AIPhilosophie</Class>', $info);
        self::assertStringContainsString('<Group>Custom Portlets</Group>', $info);
        self::assertStringContainsString("protected string \$group = 'Custom Portlets';", $klasse);
        self::assertStringContainsString('<Active>1</Active>', $info);
        self::assertStringContainsString('<Filename>philosophy.php</Filename>', $info);
        self::assertFileExists(self::ROOT . '/adminmenu/philosophy.php');
        self::assertStringNotContainsString('<LinkGroup', $info);
        self::assertStringNotContainsString('<SpecialPage', $info);
        self::assertStringNotContainsString('<Blueprint', $info);
    }
}
