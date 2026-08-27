<?php

declare(strict_types=1);

namespace Tests\Structure;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class DocumentationAndReleaseTest extends TestCase
{
    private const ROOT = __DIR__ . '/../..';
    private const ZIP = self::ROOT . '/dist/MGD_AI_Kennzeichnung-1.1.1.zip';

    #[Test]
    public function releaseziel_und_lokale_artefakte_sind_eindeutig_abgegrenzt(): void
    {
        $script = file_get_contents(self::ROOT . '/scripts/build-release.sh');
        $gitignore = file_get_contents(self::ROOT . '/.gitignore');
        self::assertIsString($script);
        self::assertIsString($gitignore);

        self::assertStringContainsString('MGD_AI_Kennzeichnung-1.1.1.zip', $script);
        self::assertStringNotContainsString('MGD_AI_Kennzeichnung-1.0.0.zip', $script);

        foreach (['/.superpowers/', '*.sql', '*.bak', '.env*'] as $muster) {
            self::assertStringContainsString($muster, $gitignore);
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
        foreach (['Datenminimierung', 'api.github.com', 'standardmäßig deaktiviert', 'keine Bilder'] as $begriff) {
            self::assertStringContainsStringIgnoringCase($begriff, $datenschutz);
        }
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
            'Datenminimierung',
            'Rollback',
            'Fehlerbehebung',
        ] as $begriff) {
            self::assertStringContainsStringIgnoringCase($begriff, $gesamt);
        }
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
        $ausgabe = [];
        $status = 1;
        exec('bash ' . escapeshellarg(self::ROOT . '/scripts/build-release.sh') . ' 2>&1', $ausgabe, $status);
        self::assertSame(0, $status, implode("\n", $ausgabe));
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
