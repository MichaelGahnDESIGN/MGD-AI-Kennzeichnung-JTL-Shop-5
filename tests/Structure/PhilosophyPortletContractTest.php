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
    public function philosophie_editor_ist_lokal_progressiv_und_ohne_externe_assets(): void
    {
        $template = (string) file_get_contents(self::ROOT . '/adminmenu/templates/philosophy.tpl');
        $cssPfad = self::ROOT . '/adminmenu/philosophy.css';
        $css = is_file($cssPfad) ? (string) file_get_contents($cssPfad) : '';
        $editorPfad = self::ROOT . '/adminmenu/js/philosophy-editor.mjs';
        $startPfad = self::ROOT . '/adminmenu/js/philosophy-editor-init.js';
        $start = is_file($startPfad) ? (string) file_get_contents($startPfad) : '';

        /* Alle Verletzungen gemeinsam ausgeben, damit der Rotlauf den vollständigen Assetvertrag zeigt. */
        $verletzungen = [];
        foreach ([
            'exakt zwei Sprachkarten' => substr_count($template, 'data-philosophy-language=') === 2,
            'deutsche Sprachkarte' => str_contains($template, 'data-philosophy-language="de"'),
            'englische Sprachkarte' => str_contains($template, 'data-philosophy-language="en"'),
            'deutsches Textfeld' => str_contains($template, 'name="content_de"'),
            'englisches Textfeld' => str_contains($template, 'name="content_en"'),
            'Fallback-Labels' => substr_count($template, 'data-philosophy-source-label') === 2,
            'Fallback-Textfelder' => preg_match_all(
                '/<textarea\\b[^>]*\\bdata-philosophy-source(?:\\s|>)/iu',
                $template,
            ) === 2,
            'lokales Stylesheet als Formularwert' => str_contains($template, 'data-philosophy-stylesheet='),
            'lokales Editor-Modul als Formularwert' => str_contains($template, 'data-philosophy-module='),
            'klassischer JTL-AJAX-Starter' => str_contains($template, 'js/philosophy-editor-init.js'),
            'kein direktes Modulskript im AJAX-Fragment' => preg_match(
                '/<script\\b[^>]*\\btype=["\']module["\']/iu',
                $template,
            ) !== 1,
            'CSS-Datei' => is_file($cssPfad),
            'Editor-Modul' => is_file($editorPfad),
            'Starter-Datei' => is_file($startPfad),
            'Starter lädt das lokale Modul dynamisch' => str_contains($start, 'import(moduleUrl.href)'),
            'Starter hängt das lokale Stylesheet ein' => str_contains($start, 'stylesheetUrl.href'),
            'Mindesthöhe der Textfelder' => str_contains($css, 'min-height: 22.5rem'),
            'visuelle Editorfläche' => str_contains($css, '.mgd-philosophy-editor__visual'),
            'HTML-Editorfläche' => str_contains($css, '.mgd-philosophy-editor__html'),
            'Editorstatus' => str_contains($css, '.mgd-philosophy-editor__status'),
        ] as $vertragsteil => $erfuellt) {
            if (!$erfuellt) {
                $verletzungen[] = $vertragsteil;
            }
        }

        if (preg_match('~(?:src|href)\\s*=\\s*["\'][^"\']*https?://~i', $template) === 1) {
            $verletzungen[] = 'externe Template-Referenz';
        }
        if (preg_match('~(?:@import|url\\()\\s*["\']?https?://~i', $css) === 1) {
            $verletzungen[] = 'externe CSS-Referenz';
        }
        if (preg_match('~https?://[A-Za-z0-9]~iu', $start) === 1) {
            $verletzungen[] = 'externe Starter-Referenz';
        }

        self::assertSame([], $verletzungen, 'Der Editor darf ausschließlich lokale, progressive Assets verwenden.');
    }

    #[Test]
    public function philosophie_editor_module_verwenden_weder_netz_noch_browser_speicher(): void
    {
        $dateien = [
            self::ROOT . '/adminmenu/js/philosophy-editor-init.js',
            self::ROOT . '/adminmenu/js/philosophy-sanitizer.mjs',
            self::ROOT . '/adminmenu/js/philosophy-source-sync.mjs',
            self::ROOT . '/adminmenu/js/philosophy-link-dialog.mjs',
            self::ROOT . '/adminmenu/js/philosophy-toolbar.mjs',
            self::ROOT . '/adminmenu/js/philosophy-editor.mjs',
            self::ROOT . '/adminmenu/philosophy.css',
        ];
        $gesamt = '';
        foreach ($dateien as $datei) {
            self::assertFileExists($datei);
            $inhalt = file_get_contents($datei);
            self::assertIsString($inhalt);
            $gesamt .= "\n" . $inhalt;
        }

        /* `https:` als Protokollvergleich im Sanitizer ist erlaubt, vollständige Fremd-URLs nicht. */
        self::assertDoesNotMatchRegularExpression('~https?://[A-Za-z0-9]~iu', $gesamt);
        self::assertDoesNotMatchRegularExpression(
            '/\\b(?:fetch|XMLHttpRequest|WebSocket|sendBeacon|localStorage|sessionStorage)\\b/u',
            $gesamt,
        );
        self::assertDoesNotMatchRegularExpression('/@import\\b/iu', $gesamt);
        self::assertDoesNotMatchRegularExpression('~(?:cdn|fonts?\\.(?:googleapis|gstatic)|iconfont)~iu', $gesamt);
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
