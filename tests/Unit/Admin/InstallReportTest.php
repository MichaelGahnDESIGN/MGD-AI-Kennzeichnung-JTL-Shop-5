<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Admin\Update\InstallReport;
use Plugin\MGD_AI_Kennzeichnung\Admin\Update\UpdatesPageFactory;
use Plugin\MGD_AI_Kennzeichnung\Install\InstallClient;
use Plugin\MGD_AI_Kennzeichnung\Install\Response;
use Plugin\MGD_AI_Kennzeichnung\Install\Settings;
use Plugin\MGD_AI_Kennzeichnung\Install\Store;
use Plugin\MGD_AI_Kennzeichnung\Install\Transport;

// Free-Lizenz Anmeldung, ohne echtes Netz
final class InstallReportTest extends TestCase
{
    private const SHORT_TEXT = 'Meldet sich höchstens einmal am Tag bei theme.michael-gahn.de (Plugin, Version, Shopsystem, Domain, zufällige ID). Abschaltbar in den Einstellungen.';

    #[Test]
    #[RunInSeparateProcess]
    public function ohne_shop_pfad_gibt_es_keinen_client_und_keinen_fehler(): void
    {
        if (defined('PFAD_ROOT')) {
            self::markTestSkipped('PFAD_ROOT ist in diesem Lauf schon gesetzt.');
        }
        $pluginDir = dirname(__DIR__, 3) . '/plugin/MGD_AI_Kennzeichnung';

        self::assertNull(InstallReport::client($pluginDir));
        InstallReport::ping($pluginDir, true);
        self::assertNull(InstallReport::reporting(null));
    }

    #[Test]
    public function schalter_leitet_an_den_install_client_weiter(): void
    {
        $transport = new class implements Transport {
            /** @var list<string> */
            public array $calls = [];

            public function send(string $method, string $url, array $headers): Response
            {
                $this->calls[] = $method . ' ' . $url;

                return new Response(200, ['x-mgd-install-key' => str_repeat('a', 64)], '{}');
            }
        };
        $store = new class implements Store {
            /** @var array<string,mixed> */
            public array $state = [];

            public function load(): array
            {
                return $this->state;
            }

            public function save(array $state): void
            {
                $this->state = $state;
            }
        };
        $client = new InstallClient($transport, $store, UpdatesPageFactory::PRODUCT, 'jtl5', '1.5.0', 'shop.example.test');

        $reporting = InstallReport::reporting($client);
        self::assertNotNull($reporting);
        self::assertSame(Settings::HINT, $reporting->hint);
        self::assertTrue(($reporting->isEnabled)(), 'Der Schalter ist ab Werk an.');

        ($reporting->ping)();
        self::assertNotSame([], $transport->calls, 'Die erste Meldung muss gesendet werden.');

        ($reporting->setEnabled)(false);
        self::assertFalse(($reporting->isEnabled)());
        $vorher = count($transport->calls);
        ($reporting->ping)();
        self::assertCount($vorher, $transport->calls, 'Mit Schalter aus wird nichts mehr gesendet.');

        ($reporting->setEnabled)(true);
        self::assertTrue(($reporting->isEnabled)());
        self::assertNotSame('', ($reporting->status)());
    }

    #[Test]
    public function kurztext_steht_woertlich_in_den_beschreibungen(): void
    {
        $root = dirname(__DIR__, 3);
        $info = file_get_contents($root . '/plugin/MGD_AI_Kennzeichnung/info.xml');
        $composer = file_get_contents($root . '/composer.json');

        self::assertIsString($info);
        self::assertIsString($composer);
        self::assertStringContainsString(self::SHORT_TEXT, $info);
        self::assertStringContainsString(self::SHORT_TEXT, $composer);
    }
}
