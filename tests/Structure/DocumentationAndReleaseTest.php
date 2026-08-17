<?php

declare(strict_types=1);

namespace Tests\Structure;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class DocumentationAndReleaseTest extends TestCase
{
    private const ROOT = __DIR__ . '/../..';
    private const ZIP = self::ROOT . '/dist/MGD_AI_Kennzeichnung-1.0.0.zip';

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
