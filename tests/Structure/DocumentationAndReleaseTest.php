<?php

declare(strict_types=1);

namespace Tests\Structure;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class DocumentationAndReleaseTest extends TestCase
{
    private const ROOT = __DIR__ . '/../..';
    private const VERSION = '1.3.2';
    private const ZIP = self::ROOT . '/dist/MGD_AI_Kennzeichnung-1.3.2.zip';

    #[Test]
    public function releaseziel_und_lokale_artefakte_sind_eindeutig_abgegrenzt(): void
    {
        $script = file_get_contents(self::ROOT . '/scripts/build-release.sh');
        $checker = file_get_contents(
            self::ROOT . '/plugin/MGD_AI_Kennzeichnung/Infrastructure/Update/GitHubReleaseChecker.php',
        );
        $display = file_get_contents(self::ROOT . '/plugin/MGD_AI_Kennzeichnung/adminmenu/display.php');
        $infoXml = file_get_contents(self::ROOT . '/plugin/MGD_AI_Kennzeichnung/info.xml');
        $gitignore = file_get_contents(self::ROOT . '/.gitignore');
        self::assertIsString($script);
        self::assertIsString($checker);
        self::assertIsString($display);
        self::assertIsString($infoXml);
        self::assertIsString($gitignore);

        self::assertStringContainsString('MGD_AI_Kennzeichnung-1.3.2.zip', $script);
        self::assertStringNotContainsString('MGD_AI_Kennzeichnung-1.2.1.zip', $script);
        self::assertStringContainsString('<Version>1.3.2</Version>', $infoXml);
        self::assertStringContainsString('MGD-AI-Kennzeichnung-JTL-Shop-5/1.3.2', $checker);
        self::assertStringContainsString("check(true, '1.3.2')", $display);
        self::assertStringNotContainsString('MGD-AI-Kennzeichnung-JTL-Shop-5/1.2.1', $checker);
        self::assertStringNotContainsString("check(true, '1.2.1')", $display);
        self::assertStringNotContainsString('cp -R "${quellordner}/."', $script);
        self::assertStringContainsString('mktemp -d "${ausgabeordner}/', $script);
        self::assertStringContainsString('unzip -tq "${temporaeres_zip}"', $script);
        self::assertStringNotContainsString('rm -f "${ausgabedatei}"', $script);

        foreach (['/.superpowers/', '*.sql', '*.bak', '.env*'] as $muster) {
            self::assertStringContainsString($muster, $gitignore);
        }
    }

    #[Test]
    public function build_bewahrt_das_alte_zip_wenn_die_integritaetspruefung_des_neuen_archivs_fehlschlaegt(): void
    {
        $this->buildRelease();
        $vorherigerHash = hash_file('sha256', self::ZIP);
        self::assertIsString($vorherigerHash);

        $fakeBin = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mgd-fake-zip-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($fakeBin, 0700));
        $fakeZip = $fakeBin . DIRECTORY_SEPARATOR . 'zip';
        $fakeZipScript = <<<'BASH'
#!/usr/bin/env bash
for argument in "$@"; do
    case "${argument}" in
        -*) ;;
        *) printf '%s' 'kein gültiges ZIP' > "${argument}"; exit 0 ;;
    esac
done
exit 1
BASH;

        try {
            self::assertNotFalse(file_put_contents($fakeZip, $fakeZipScript));
            self::assertTrue(chmod($fakeZip, 0700));

            [$status, $ausgabe] = $this->runBuildRelease($fakeBin);
            self::assertNotSame(0, $status, 'Ein beschädigtes temporäres ZIP muss den Build abbrechen.');
            self::assertStringContainsString('Integritätsprüfung', $ausgabe);
            self::assertSame(
                $vorherigerHash,
                hash_file('sha256', self::ZIP),
                'Das vorherige Release-ZIP muss bei jedem Fehler unverändert erhalten bleiben.',
            );
        } finally {
            if (is_file($fakeZip)) {
                unlink($fakeZip);
            }
            if (is_dir($fakeBin)) {
                rmdir($fakeBin);
            }
        }
    }

    #[Test]
    public function build_erzeugt_zweimal_dasselbe_minimale_installationspaket(): void
    {
        $this->buildRelease();
        self::assertFileExists(self::ZIP);
        $ersterHash = hash_file('sha256', self::ZIP);
        self::assertIsString($ersterHash);

        $this->buildRelease();
        self::assertSame($ersterHash, hash_file('sha256', self::ZIP));

        $eintraege = $this->entries();
        self::assertSame(
            'MGD_AI_Kennzeichnung/',
            $eintraege[0] ?? null,
            'JTL-Shop 5.7.2 leitet den Plugin-Stamm aus dem ersten ZIP-Eintrag ab.',
        );
        self::assertContains('MGD_AI_Kennzeichnung/Bootstrap.php', $eintraege);
        self::assertContains('MGD_AI_Kennzeichnung/info.xml', $eintraege);
        self::assertContains('MGD_AI_Kennzeichnung/Portlets/AIPhilosophie/AIPhilosophie.php', $eintraege);
        self::assertContains('MGD_AI_Kennzeichnung/adminmenu/impressum.php', $eintraege);
        self::assertContains('MGD_AI_Kennzeichnung/adminmenu/templates/impressum.tpl', $eintraege);
        self::assertContains('MGD_AI_Kennzeichnung/adminmenu/display.php', $eintraege);
        self::assertContains('MGD_AI_Kennzeichnung/adminmenu/templates/display.tpl', $eintraege);
        self::assertContains('MGD_AI_Kennzeichnung/adminmenu/display.css', $eintraege);
        self::assertContains('MGD_AI_Kennzeichnung/adminmenu/js/display-range-sync.mjs', $eintraege);
        self::assertContains('MGD_AI_Kennzeichnung/adminmenu/js/display-preview.mjs', $eintraege);
        self::assertContains('MGD_AI_Kennzeichnung/adminmenu/js/display-controls.mjs', $eintraege);
        self::assertContains(
            'MGD_AI_Kennzeichnung/adminmenu/images/michael-gahn-design-schuh.png',
            $eintraege,
        );

        $zip = new ZipArchive();
        self::assertTrue($zip->open(self::ZIP));
        $infoXml = $zip->getFromName('MGD_AI_Kennzeichnung/info.xml');
        $zip->close();
        self::assertIsString($infoXml);
        self::assertStringContainsString('<Version>' . self::VERSION . '</Version>', $infoXml);

        foreach ($eintraege as $eintrag) {
            self::assertStringStartsWith('MGD_AI_Kennzeichnung/', $eintrag);
            self::assertStringNotContainsString('/tests/', $eintrag);
            self::assertStringNotContainsString('/vendor/', $eintrag);
            self::assertStringNotContainsString('/.git', $eintrag);
            self::assertStringNotContainsString('.env', $eintrag);
            self::assertStringNotContainsString('.DS_Store', $eintrag);
        }
    }

    #[Test]
    public function build_erzeugt_in_unterschiedlichen_prozesszeitzonen_dasselbe_paket(): void
    {
        [$utcStatus, $utcAusgabe] = $this->runBuildRelease(timezone: 'UTC');
        self::assertSame(0, $utcStatus, $utcAusgabe);
        $utcHash = hash_file('sha256', self::ZIP);
        self::assertIsString($utcHash);

        [$berlinStatus, $berlinAusgabe] = $this->runBuildRelease(timezone: 'Europe/Berlin');
        self::assertSame(0, $berlinStatus, $berlinAusgabe);

        self::assertSame(
            $utcHash,
            hash_file('sha256', self::ZIP),
            'Die Prozesszeitzone darf weder ZIP-Zeitstempel noch Paketinhalt verändern.',
        );
    }

    #[Test]
    public function build_lehnt_sensible_dateitypen_in_freigegebenen_verzeichnissen_fail_closed_ab(): void
    {
        $this->buildRelease();
        $vorherigerHash = hash_file('sha256', self::ZIP);
        self::assertIsString($vorherigerHash);

        $testverzeichnis = self::ROOT . '/plugin/MGD_AI_Kennzeichnung/adminmenu';
        $dateien = [
            $testverzeichnis . '/release-test.key',
            $testverzeichnis . '/release-test.p12',
            $testverzeichnis . '/release-test.pfx',
            $testverzeichnis . '/release-test.crt',
            $testverzeichnis . '/release-test.sql',
            $testverzeichnis . '/release-test.php.bak',
        ];

        try {
            foreach ($dateien as $datei) {
                self::assertNotFalse(file_put_contents($datei, 'DARF NICHT INS RELEASE'));
            }

            [$status, $ausgabe] = $this->runBuildRelease();
            self::assertNotSame(0, $status, 'Nicht freigegebene Dateien müssen den Build sicher abbrechen.');
            self::assertStringContainsString('nicht freigegeben', $ausgabe);
            self::assertSame($vorherigerHash, hash_file('sha256', self::ZIP));
        } finally {
            foreach ($dateien as $datei) {
                if (is_file($datei)) {
                    unlink($datei);
                }
            }
        }
    }

    #[Test]
    public function dokumentation_deckt_live_installation_datenschutz_und_rueckfall_ab(): void
    {
        $readme = file_get_contents(self::ROOT . '/README.md');
        $installation = file_get_contents(self::ROOT . '/Dokumentation/Installation-und-Livetest.md');
        $datenschutz = file_get_contents(self::ROOT . '/Dokumentation/Datenschutz-und-Sicherheit.md');
        self::assertIsString($readme);
        self::assertIsString($installation);
        self::assertIsString($datenschutz);

        foreach (['JTL-Shop 5.7.2', 'NOVA', 'OnvisTheme', 'keine automatische KI-Erkennung'] as $begriff) {
            self::assertStringContainsStringIgnoringCase($begriff, $readme);
        }
        foreach (['Pflichtbackup', 'Wartungsfenster', 'Plugin-Manager', 'Rollback', 'https://onvis-shop.de'] as $begriff) {
            self::assertStringContainsStringIgnoringCase($begriff, $installation);
        }
        foreach (['Datenminimierung', 'api.github.com', 'Server-IP', 'keine Bilder'] as $begriff) {
            self::assertStringContainsStringIgnoringCase($begriff, $datenschutz);
        }
        self::assertStringContainsStringIgnoringCase('Plugin-Tabellen für Bildzuordnungen', $readme);
        self::assertStringContainsStringIgnoringCase('JTL-Plugin-Konfiguration für Darstellungswerte', $readme);
    }

    #[Test]
    public function version_1_3_0_erklaert_editor_datenschutz_und_nachhaltige_monetarisierung(): void
    {
        $dateien = [
            'README' => self::ROOT . '/README.md',
            'README Englisch' => self::ROOT . '/README.en.md',
            'Änderungsprotokoll' => self::ROOT . '/CHANGELOG.md',
            'Sicherheit' => self::ROOT . '/SECURITY.md',
            'Dokumentationsübersicht' => self::ROOT . '/Dokumentation/README.md',
            'Monetarisierung' => self::ROOT . '/Dokumentation/Monetarisierung-und-Marketplaces.md',
            'Release' => self::ROOT . '/Dokumentation/Release-1.3.0.md',
            'Wiki AI-Philosophie' => self::ROOT . '/wiki/AI-Philosophie.md',
            'Wiki Datenschutz' => self::ROOT . '/wiki/Datenschutz-und-Sicherheit.md',
            'Wiki Update' => self::ROOT . '/wiki/Installation-und-Update.md',
        ];
        $inhalte = [];
        foreach ($dateien as $name => $datei) {
            self::assertFileExists($datei, $name . ' fehlt.');
            $inhalt = file_get_contents($datei);
            self::assertIsString($inhalt);
            self::assertNotSame('', trim($inhalt), $name . ' ist leer.');
            $inhalte[$name] = $inhalt;
        }

        foreach (['Version 1.3.0', 'Visuell', 'HTML', 'Beide Sprachfassungen speichern'] as $begriff) {
            self::assertStringContainsStringIgnoringCase($begriff, $inhalte['README']);
        }
        foreach (['p', 'h2', 'h3', 'ul', 'ol', 'li', 'strong', 'em', 'a'] as $element) {
            self::assertStringContainsString('`' . $element . '`', $inhalte['Wiki AI-Philosophie']);
        }
        foreach (['keine externen', 'keine Drittinhalte', 'keine Telemetrie', 'No-JavaScript'] as $begriff) {
            self::assertStringContainsStringIgnoringCase(
                $begriff,
                $inhalte['Sicherheit'] . "\n" . $inhalte['Wiki Datenschutz'],
            );
        }
        foreach (['JTL', 'Shopware', 'WordPress', 'Shopify', 'keine Rechtsberatung', '30.08.2026'] as $begriff) {
            self::assertStringContainsStringIgnoringCase($begriff, $inhalte['Monetarisierung']);
        }
        foreach (['keine Lizenzschlüssel', 'keine Zahlung', 'keine Sperren', 'keine Telemetrie'] as $begriff) {
            self::assertStringContainsStringIgnoringCase($begriff, $inhalte['Monetarisierung']);
        }
        self::assertStringNotContainsStringIgnoringCase(
            'privates Repository',
            $inhalte['README'] . "\n"
                . $inhalte['README Englisch'] . "\n"
                . $inhalte['Sicherheit'] . "\n"
                . $inhalte['Wiki Update'],
        );
    }

    #[Test]
    public function version_1_3_2_ist_in_paket_ci_und_benutzerdokumentation_konsistent(): void
    {
        $dateien = [
            'README' => self::ROOT . '/README.md',
            'CHANGELOG' => self::ROOT . '/CHANGELOG.md',
            'Sicherheit' => self::ROOT . '/SECURITY.md',
            'Darstellung' => self::ROOT . '/Dokumentation/Darstellung.md',
            'Release' => self::ROOT . '/Dokumentation/Release-1.3.2.md',
            'Datenschutz' => self::ROOT . '/Dokumentation/Datenschutz-und-Sicherheit.md',
            'Installation' => self::ROOT . '/Dokumentation/Installation-und-Livetest.md',
            'Wiki' => self::ROOT . '/wiki/Home.md',
            'Wiki-Einstellungen' => self::ROOT . '/wiki/Einstellungen.md',
            'Wiki-Darstellung' => self::ROOT . '/wiki/Status-und-Darstellung.md',
            'Wiki-Update' => self::ROOT . '/wiki/Installation-und-Update.md',
            'Wiki-Datenschutz' => self::ROOT . '/wiki/Datenschutz-und-Sicherheit.md',
            'Wiki-Rollback' => self::ROOT . '/wiki/Release-und-Rollback.md',
            'Wiki-Fehlerhilfe' => self::ROOT . '/wiki/Fehlerbehebung.md',
            'Wiki-FAQ' => self::ROOT . '/wiki/FAQ.md',
            'Wiki-Fußzeile' => self::ROOT . '/wiki/_Footer.md',
        ];
        $gesamt = '';
        foreach ($dateien as $name => $datei) {
            self::assertFileExists($datei, $name . ' fehlt.');
            $inhalt = file_get_contents($datei);
            self::assertIsString($inhalt);
            self::assertNotSame('', trim($inhalt), $name . ' ist leer.');
            $gesamt .= "\n" . $inhalt;
        }

        foreach ([
            'Version 1.3.2',
            'Live-Vorschau',
            'Transparenz',
            'Nur Vorschau',
            'manueller ZIP-Upload',
            'Server-IP',
            'User-Agent',
            'zwölf Stunden',
            'supported by: Michael Gahn DESIGN',
            'Dev-Test',
            'Rollback',
        ] as $begriff) {
            self::assertStringContainsStringIgnoringCase($begriff, $gesamt);
        }
        self::assertStringContainsString('0 %', $gesamt);
        self::assertStringContainsString('90 %', $gesamt);
        self::assertStringContainsStringIgnoringCase('installiert keine Updates automatisch', $gesamt);
        self::assertStringNotContainsStringIgnoringCase(
            'Updatehinweise sind bei Neuinstallationen standardmäßig deaktiviert',
            $gesamt,
        );
        self::assertStringNotContainsStringIgnoringCase('privates Repository', $gesamt);

        $build = file_get_contents(self::ROOT . '/scripts/build-release.sh');
        $workflow = file_get_contents(self::ROOT . '/.github/workflows/quality.yml');
        $infoXml = file_get_contents(self::ROOT . '/plugin/MGD_AI_Kennzeichnung/info.xml');
        self::assertIsString($build);
        self::assertIsString($workflow);
        self::assertIsString($infoXml);
        $dateiname = 'MGD_AI_Kennzeichnung-' . self::VERSION . '.zip';
        self::assertStringContainsString($dateiname, $build);
        self::assertStringContainsString($dateiname, $workflow);
        self::assertSame(1, substr_count($workflow, 'run: composer test:js'));
        self::assertStringContainsString('<Version>' . self::VERSION . '</Version>', $infoXml);
        self::assertStringNotContainsString('MGD_AI_Kennzeichnung-1.2.1.zip', $build . $workflow);
    }

    #[Test]
    public function sicherheitsdokumentation_verspricht_nur_die_tatsaechlich_angebotene_pruefsumme(): void
    {
        $sicherheit = file_get_contents(self::ROOT . '/SECURITY.md');
        self::assertIsString($sicherheit);

        self::assertStringContainsString('SHA-256', $sicherheit);
        self::assertStringNotContainsStringIgnoringCase('signiert', $sicherheit);
        self::assertStringNotContainsStringIgnoringCase('Signatur', $sicherheit);
    }

    #[Test]
    public function herstellerhinweis_verwendet_auch_in_den_pluginmetadaten_den_freigegebenen_wortlaut(): void
    {
        $infoXml = file_get_contents(self::ROOT . '/plugin/MGD_AI_Kennzeichnung/info.xml');
        self::assertIsString($infoXml);

        self::assertStringContainsString(
            '<Description>Zeigt optional den festen Hinweis „supported by: Michael Gahn DESIGN“ an.</Description>',
            $infoXml,
        );
        self::assertStringNotContainsString('Plugin von Michael Gahn DESIGN', $infoXml);
    }

    #[Test]
    public function version_1_1_1_erklaert_galerie_opc_dateimanager_und_sicheren_rollback(): void
    {
        $dateien = [
            'README' => self::ROOT . '/README.md',
            'Bildverwaltung' => self::ROOT . '/Dokumentation/Admin-Bildverwaltung.md',
            'OPC' => self::ROOT . '/Dokumentation/OPC-Kennzeichnung.md',
            'Installation' => self::ROOT . '/Dokumentation/Installation-und-Livetest.md',
            'Rollback' => self::ROOT . '/Dokumentation/Rollback-1.1.0.md',
        ];
        $inhalt = [];
        foreach ($dateien as $name => $datei) {
            self::assertFileExists($datei, $name . ' fehlt.');
            $inhalt[$name] = (string) file_get_contents($datei);
        }

        foreach (['Version 1.1.1', 'Bildgalerie', 'Kennzeichnung speichern'] as $begriff) {
            self::assertStringContainsStringIgnoringCase($begriff, $inhalt['README'] . $inhalt['Bildverwaltung']);
        }
        foreach (['Bild neu scannen', 'Filter', 'Stapelbearbeitung', 'Abbrechen'] as $begriff) {
            self::assertStringContainsStringIgnoringCase($begriff, $inhalt['Bildverwaltung']);
        }
        foreach (['OnPage Composer', 'Dateimanager', 'Kompatibilitätsgrenze', 'same-origin'] as $begriff) {
            self::assertStringContainsStringIgnoringCase($begriff, $inhalt['OPC']);
        }
        foreach (['dev.onvis-shop.de', 'vor', 'onvis-shop.de', 'Backup'] as $begriff) {
            self::assertStringContainsStringIgnoringCase($begriff, $inhalt['Installation']);
        }
        foreach (['Plugin 1.1.0 deaktivieren', 'Pluginverzeichnis 1.0.0', 'Datenbanktabellen nicht löschen', 'Caches leeren'] as $begriff) {
            self::assertStringContainsStringIgnoringCase($begriff, $inhalt['Rollback']);
        }
        self::assertStringNotContainsStringIgnoringCase('Deinstallation mit Datenlöschung empfehlen', $inhalt['Rollback']);
    }

    #[Test]
    public function github_wiki_deckt_funktionen_bedienung_sicherheit_und_fehlerhilfe_ab(): void
    {
        $seiten = [
            'Home.md',
            'Erste-Schritte.md',
            'Installation-und-Update.md',
            'Bildverwaltung.md',
            'Status-und-Darstellung.md',
            'OnPage-Composer-und-Dateimanager.md',
            'AI-Philosophie.md',
            'Impressum.md',
            'Einstellungen.md',
            'Datenschutz-und-Sicherheit.md',
            'Fehlerbehebung.md',
            'Release-und-Rollback.md',
            'FAQ.md',
            'Fuer-Entwickler.md',
            '_Sidebar.md',
            '_Footer.md',
        ];
        $gesamt = '';
        foreach ($seiten as $seite) {
            $pfad = self::ROOT . '/wiki/' . $seite;
            self::assertFileExists($pfad, 'Wiki-Seite fehlt: ' . $seite);
            $inhalt = file_get_contents($pfad);
            self::assertIsString($inhalt);
            self::assertNotSame('', trim($inhalt), 'Wiki-Seite ist leer: ' . $seite);
            $gesamt .= "\n" . $inhalt;
        }

        foreach ([
            'keine automatische KI-Erkennung',
            'Kennzeichnung speichern',
            'Stapelbearbeitung',
            'OnPage Composer',
            'AI-Philosophie',
            '§ 5 DDG',
            'Datenminimierung',
            'Rollback',
            'Fehlerbehebung',
        ] as $begriff) {
            self::assertStringContainsStringIgnoringCase($begriff, $gesamt);
        }
    }

    #[Test]
    public function version_1_2_0_erklaert_den_rein_lesenden_impressum_tab(): void
    {
        $dateien = [
            self::ROOT . '/README.md',
            self::ROOT . '/Dokumentation/Impressum.md',
            self::ROOT . '/Dokumentation/Release-1.2.0.md',
            self::ROOT . '/wiki/Impressum.md',
        ];
        $gesamt = '';
        foreach ($dateien as $datei) {
            self::assertFileExists($datei);
            $gesamt .= "\n" . (string) file_get_contents($datei);
        }

        foreach (['Version 1.2.0', '§ 5 DDG', 'nur im Administrationsbereich', 'keine Datenbank'] as $begriff) {
            self::assertStringContainsStringIgnoringCase($begriff, $gesamt);
        }
        self::assertStringContainsString('+49 (0) 151 59156639', $gesamt);
        self::assertStringContainsString('Anfrage@Michael-Gahn.de', $gesamt);
        self::assertStringContainsString('ersetzt nicht das öffentliche Impressum', $gesamt);
    }

    #[Test]
    public function release_enthaelt_galerie_und_lokale_opc_module(): void
    {
        $this->buildRelease();
        $eintraege = $this->entries();

        foreach ([
            'MGD_AI_Kennzeichnung/adminmenu/assets.css',
            'MGD_AI_Kennzeichnung/adminmenu/js/label-dialog.mjs',
            'MGD_AI_Kennzeichnung/Portlets/AIPhilosophie/editor_init.js',
            'MGD_AI_Kennzeichnung/Portlets/AIPhilosophie/editor/opc-integration.mjs',
            'MGD_AI_Kennzeichnung/Portlets/AIPhilosophie/editor/file-manager-integration.mjs',
        ] as $eintrag) {
            self::assertContains($eintrag, $eintraege);
        }
    }

    private function buildRelease(): void
    {
        [$status, $ausgabe] = $this->runBuildRelease();
        self::assertSame(0, $status, $ausgabe);
    }

    /** @return array{0: int, 1: string} */
    private function runBuildRelease(?string $pathPrefix = null, ?string $timezone = null): array
    {
        $ausgabe = [];
        $status = 1;
        $path = (string) getenv('PATH');
        $pathAssignment = $pathPrefix === null
            ? ''
            : 'PATH=' . escapeshellarg($pathPrefix . PATH_SEPARATOR . $path) . ' ';
        $timezoneAssignment = $timezone === null
            ? ''
            : 'TZ=' . escapeshellarg($timezone) . ' ';
        exec(
            $pathAssignment
                . $timezoneAssignment
                . 'bash '
                . escapeshellarg(self::ROOT . '/scripts/build-release.sh')
                . ' 2>&1',
            $ausgabe,
            $status,
        );

        return [$status, implode("\n", $ausgabe)];
    }

    /** @return list<string> */
    private function entries(): array
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open(self::ZIP));
        $eintraege = [];
        for ($index = 0; $index < $zip->numFiles; ++$index) {
            $name = $zip->getNameIndex($index);
            self::assertIsString($name);
            $eintraege[] = $name;
        }
        $zip->close();

        return $eintraege;
    }
}
