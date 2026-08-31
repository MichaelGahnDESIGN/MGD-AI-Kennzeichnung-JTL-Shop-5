<?php

declare(strict_types=1);

namespace Tests\Unit\Setup;

require_once __DIR__ . '/../../Stubs/JtlPluginStubs.php';

use JTL\Plugin\Data\AdminMenu;
use JTL\Plugin\Data\Config;
use JTL\Plugin\Data\Paths;
use JTL\Plugin\PluginInterface;
use JTL\Smarty\JTLSmarty;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Bootstrap;

final class BootstrapTemplateCacheRefreshTest extends TestCase
{
    private string $testverzeichnis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testverzeichnis = sys_get_temp_dir() . '/mgd-ai-bootstrap-cache-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->testverzeichnis . '/adminmenu/templates', 0700, true));
        self::assertNotFalse(file_put_contents(
            $this->testverzeichnis . '/adminmenu/templates/philosophy.tpl',
            'Aktuelle Vorlage',
        ));
        JTLSmarty::$geleerteTemplates = [];
    }

    protected function tearDown(): void
    {
        $template = $this->testverzeichnis . '/adminmenu/templates/philosophy.tpl';
        if (is_file($template)) {
            unlink($template);
        }
        if (is_dir($this->testverzeichnis . '/adminmenu/templates')) {
            rmdir($this->testverzeichnis . '/adminmenu/templates');
        }
        if (is_dir($this->testverzeichnis . '/adminmenu')) {
            rmdir($this->testverzeichnis . '/adminmenu');
        }
        if (is_dir($this->testverzeichnis)) {
            rmdir($this->testverzeichnis);
        }
        parent::tearDown();
    }

    #[Test]
    public function jtl_update_invalidiert_kompilierte_plugin_templates(): void
    {
        $plugin = new class ($this->testverzeichnis) implements PluginInterface {
            public function __construct(private readonly string $basePath) {}

            public function getID(): int
            {
                return 47;
            }

            public function getPaths(): Paths
            {
                return new Paths(basePath: $this->basePath);
            }

            public function getAdminMenu(): AdminMenu
            {
                return new AdminMenu();
            }

            public function getConfig(): Config
            {
                return new Config();
            }
        };
        $bootstrap = new class ($plugin) extends Bootstrap {
            public function __construct(private readonly PluginInterface $plugin) {}

            public function getPlugin(): PluginInterface
            {
                return $this->plugin;
            }
        };

        $bootstrap->updated('1.3.0', '1.3.1');

        self::assertSame(
            [realpath($this->testverzeichnis . '/adminmenu/templates/philosophy.tpl')],
            JTLSmarty::$geleerteTemplates,
            'Nach dem JTL-Update darf keine kompilierte Oberfläche der Vorversion weiterverwendet werden.',
        );
    }
}
