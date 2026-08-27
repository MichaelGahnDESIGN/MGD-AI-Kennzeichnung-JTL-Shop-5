<?php

declare(strict_types=1);

namespace Tests\Structure;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Prüft den rein lesenden und datensparsamen Vertrag des Plugin-Impressums. */
final class ImpressumAdminContractTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../plugin/MGD_AI_Kennzeichnung';

    #[Test]
    public function adminmenue_registriert_das_impressum_vor_den_einstellungen(): void
    {
        $xml = simplexml_load_file(self::ROOT . '/info.xml');
        self::assertNotFalse($xml);
        $links = $xml->xpath('/jtlshopplugin/Install/Adminmenu/*');
        self::assertIsArray($links);

        $menue = [];
        foreach ($links as $link) {
            $menue[(int) $link['sort']] = trim((string) $link->Name);
        }

        self::assertSame('Impressum', $menue[3] ?? null);
        self::assertSame('Einstellungen', $menue[4] ?? null);
        self::assertFileExists(self::ROOT . '/adminmenu/impressum.php');
        self::assertFileExists(self::ROOT . '/adminmenu/templates/impressum.tpl');
    }

    #[Test]
    public function template_zeigt_nur_freigegebene_geschaeftsangaben_ohne_datenerfassung(): void
    {
        $template = (string) file_get_contents(self::ROOT . '/adminmenu/templates/impressum.tpl');
        foreach ([
            '§ 5 DDG',
            'Michael Gahn DESIGN',
            'Dr.-Theodor-Brugsch Str. 12',
            '+49 (0) 151 59156639',
            'Anfrage@Michael-Gahn.de',
            '223/222/02451',
            'DE288143343',
        ] as $wert) {
            self::assertStringContainsString($wert, $template);
        }

        self::assertStringContainsString('href="tel:+4915159156639"', $template);
        self::assertStringContainsString('href="mailto:Anfrage@Michael-Gahn.de"', $template);
        self::assertStringNotContainsString('<form', strtolower($template));
        self::assertStringNotContainsString('<script', strtolower($template));
        self::assertDoesNotMatchRegularExpression('~(?:src|href)="https?://~i', $template);
    }

    #[Test]
    public function einstiegspunkt_bleibt_lesend_und_geschuetzt(): void
    {
        $php = (string) file_get_contents(self::ROOT . '/adminmenu/impressum.php');

        self::assertStringContainsString("defined('PFAD_ROOT')", $php);
        self::assertStringContainsString('assertCanManageAssets()', $php);
        self::assertStringContainsString("\$request->method !== 'GET'", $php);
        self::assertStringNotContainsString('getDB()', $php);
        self::assertStringNotContainsString('$_COOKIE', $php);
    }
}
