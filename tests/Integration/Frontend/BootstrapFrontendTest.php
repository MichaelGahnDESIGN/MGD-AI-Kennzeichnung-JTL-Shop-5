<?php

declare(strict_types=1);

namespace Tests\Integration\Frontend;

require_once __DIR__ . '/../../Stubs/JtlDatabaseStubs.php';
require_once __DIR__ . '/../../Stubs/JtlPluginStubs.php';

use JTL\DB\DbInterface;
use JTL\Events\Dispatcher;
use JTL\Plugin\Data\AdminMenu;
use JTL\Plugin\Data\Config;
use JTL\Plugin\Data\Paths;
use JTL\Plugin\PluginInterface;
use JTL\Shop;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Bootstrap;
use Tests\Support\TransactionalDatabaseFake;

final class BootstrapFrontendTest extends TestCase
{
    #[Test]
    public function outputfilter_verbindet_plugin_assets_einstellungen_und_native_labels(): void
    {
        Shop::$frontend = true;
        $db = new TransactionalDatabaseFake();
        $db->seedScanAsset('sichtbar', 'media/sichtbar.webp', 'generated');
        $db->seedScanUsage('sichtbar', 'media/sichtbar.webp', 'produkt-1');
        $plugin = $this->plugin(new Config(['language' => 'auto', 'show_credit' => 'N']));
        $bootstrap = new class ($db, $plugin) extends Bootstrap {
            public function __construct(
                private readonly DbInterface $db,
                private readonly PluginInterface $plugin,
            ) {}

            public function getDB(): DbInterface
            {
                return $this->db;
            }

            public function getPlugin(): PluginInterface
            {
                return $this->plugin;
            }
        };
        $dispatcher = new Dispatcher();
        $dokument = new FrontendDocument();

        $bootstrap->boot($dispatcher);
        $dispatcher->dispatch('shop.hook.140', ['document' => $dokument]);

        self::assertStringContainsString('mgd-ai-labels.css', $dokument->head->markup[0]);
        self::assertStringContainsString('mgd-ai-marked-elements.js', $dokument->body->markup[0]);
        self::assertStringContainsString('KI-GENERIERT', $dokument->bilder->markup[0]);
        self::assertContains('mgd-ai-label-host', $dokument->bilder->classes);
    }

    private function plugin(Config $config): PluginInterface
    {
        return new class ($config) implements PluginInterface {
            public function __construct(private readonly Config $config) {}

            public function getID(): int
            {
                return 1;
            }

            public function getPaths(): Paths
            {
                return new Paths(frontendURL: 'https://onvis-shop.de/plugins/MGD_AI_Kennzeichnung/frontend/');
            }

            public function getAdminMenu(): AdminMenu
            {
                return new AdminMenu();
            }

            public function getConfig(): Config
            {
                return $this->config;
            }
        };
    }
}

final class FrontendDocument
{
    public FrontendTarget $head;
    public FrontendTarget $body;
    public FrontendTarget $bilder;

    public function __construct()
    {
        $this->head = new FrontendTarget();
        $this->body = new FrontendTarget();
        $this->bilder = new FrontendTarget();
    }

    public function find(string $selector): FrontendTarget
    {
        if ($selector === 'head') {
            return $this->head;
        }

        return $selector === 'body' ? $this->body : $this->bilder;
    }
}

final class FrontendTarget
{
    /** @var list<string> */
    public array $markup = [];
    /** @var list<string> */
    public array $classes = [];

    public function append(string $markup): void
    {
        $this->markup[] = $markup;
    }

    public function parent(): self
    {
        return $this;
    }

    public function addClass(string $class): void
    {
        $this->classes[] = $class;
    }
}
