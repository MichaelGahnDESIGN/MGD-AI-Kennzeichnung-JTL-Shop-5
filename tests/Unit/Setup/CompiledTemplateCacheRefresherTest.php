<?php

declare(strict_types=1);

namespace Tests\Unit\Setup;

use JTL\Smarty\JTLSmarty;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Setup\CompiledTemplateCacheRefresher;
use SplFileInfo;

final class CompiledTemplateCacheRefresherTest extends TestCase
{
    private string $testverzeichnis;

    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../Stubs/JtlPluginStubs.php';

        $this->testverzeichnis = sys_get_temp_dir() . '/mgd-ai-template-cache-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->testverzeichnis . '/adminmenu/templates', 0700, true));
        self::assertTrue(mkdir($this->testverzeichnis . '/frontend/template', 0700, true));
    }

    protected function tearDown(): void
    {
        $this->entferneTestverzeichnis($this->testverzeichnis);
        parent::tearDown();
    }

    #[Test]
    public function leert_ausschliesslich_kompilierte_tpl_dateien_des_pluginverzeichnisses(): void
    {
        $klassendatei = __DIR__ . '/../../../plugin/MGD_AI_Kennzeichnung/Setup/CompiledTemplateCacheRefresher.php';
        self::assertFileExists(
            $klassendatei,
            'Der Update-Schutz gegen veraltete Smarty-Templates muss als eigene, prüfbare Klasse vorhanden sein.',
        );
        require_once $klassendatei;

        $adminTemplate = $this->testverzeichnis . '/adminmenu/templates/philosophy.tpl';
        $frontendTemplate = $this->testverzeichnis . '/frontend/template/label.tpl';
        self::assertNotFalse(file_put_contents($adminTemplate, 'Admin'));
        self::assertNotFalse(file_put_contents($frontendTemplate, 'Frontend'));
        self::assertNotFalse(file_put_contents($this->testverzeichnis . '/adminmenu/templates/kein-template.php', 'PHP'));

        $aktiveSmartyEngine = new class extends JTLSmarty {
            /** @var list<string> */
            public array $geleerteRessourcen = [];

            /**
             * @param mixed $resource_name
             * @param mixed $compile_id
             * @param mixed $exp_time
             */
            public function clearCompiledTemplate(
                $resource_name = null,
                $compile_id = null,
                $exp_time = null,
            ): int {
                if (is_string($resource_name)) {
                    $this->geleerteRessourcen[] = $resource_name;
                }

                return 1;
            }
        };
        $smartyFassade = new class ($aktiveSmartyEngine) extends JTLSmarty {
            public function __construct(private readonly JTLSmarty $aktiveSmartyEngine) {}

            public function getSmarty(): JTLSmarty
            {
                return $this->aktiveSmartyEngine;
            }

            public function clearCompiledTemplate(
                $resource_name = null,
                $compile_id = null,
                $exp_time = null,
            ): int {
                throw new \RuntimeException(
                    'Die äußere JTL-Fassade darf im Smarty-4-Kompatibilitätsmodus nicht den Cache leeren.',
                );
            }
        };

        $anzahl = (new CompiledTemplateCacheRefresher($smartyFassade))->refresh($this->testverzeichnis);
        sort($aktiveSmartyEngine->geleerteRessourcen, SORT_STRING);
        $erwartet = [realpath($adminTemplate), realpath($frontendTemplate)];
        self::assertNotContains(false, $erwartet);
        sort($erwartet, SORT_STRING);

        self::assertSame(2, $anzahl);
        self::assertSame($erwartet, $aktiveSmartyEngine->geleerteRessourcen);
    }

    /** Entfernt ausschließlich das zuvor erzeugte, zufällige Testverzeichnis. */
    private function entferneTestverzeichnis(string $pfad): void
    {
        if (!is_dir($pfad)) {
            return;
        }
        $eintraege = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($pfad, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($eintraege as $eintrag) {
            if (!$eintrag instanceof SplFileInfo) {
                continue;
            }
            if ($eintrag->isDir() && !$eintrag->isLink()) {
                rmdir($eintrag->getPathname());
            } else {
                unlink($eintrag->getPathname());
            }
        }
        rmdir($pfad);
    }
}
