<?php

declare(strict_types=1);

namespace Tests\Integration\Admin;

require_once __DIR__ . '/../../Stubs/JtlDatabaseStubs.php';
require_once __DIR__ . '/../../Stubs/JtlPluginStubs.php';

use JTL\Backend\AdminAccount;
use JTL\Cache\JTLCache;
use JTL\Cache\JTLCacheInterface;
use JTL\DB\DbInterface;
use JTL\Plugin\Data\Paths;
use JTL\Plugin\Data\AdminMenu;
use JTL\Plugin\PluginInterface;
use JTL\Services\DefaultServicesInterface;
use JTL\Shop;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\SchemaOwnershipGuard;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Tests\Support\TransactionalDatabaseFake;

/** Prüft den echten JTL-Einstieg bis zur sicheren, leeren Listenansicht. */
final class AdminEntryPointTest extends TestCase
{
    #[Test]
    public function gueltiger_jtl_kontext_komponiert_und_rendert_ohne_undefinierte_variablen(): void
    {
        if (!defined('PFAD_ROOT')) {
            define('PFAD_ROOT', dirname(__DIR__, 3) . '/');
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_id('mgd-entry-point-test');
            session_start();
        }
        $_SESSION = ['jtl_token' => 'csrf'];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = [
            'kPlugin' => '17',
            'kPluginAdminMenu' => '9',
            'view' => 'list',
            'status' => 'generated',
            'sort' => 'status',
            'direction' => 'desc',
        ];
        $_POST = [];

        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_asset', SchemaOwnershipGuard::OWNERSHIP_MARKER);
        $db->setMarker('xplugin_mgd_ai_usage', SchemaOwnershipGuard::OWNERSHIP_MARKER);
        $logger = new AdminEntryLogger();
        Shop::$container = new class ($db, $logger) implements DefaultServicesInterface {
            private readonly AdminAccount $account;
            private readonly JTLCache $cache;

            public function __construct(private readonly DbInterface $db, private readonly LoggerInterface $logger)
            {
                $this->account = new AdminAccount(['PLUGIN_DETAIL_VIEW_17'], 5);
                $this->cache = new JTLCache();
            }

            public function getDB(): DbInterface
            {
                return $this->db;
            }

            public function getAdminAccount(): AdminAccount
            {
                return $this->account;
            }

            public function getCache(): JTLCacheInterface
            {
                return $this->cache;
            }

            public function getLogService(): LoggerInterface
            {
                return $this->logger;
            }
        };
        $oPlugin = new class implements PluginInterface {
            public function getID(): int
            {
                return 17;
            }

            public function getPaths(): Paths
            {
                return new Paths('/plugin/17/adminmenu/');
            }

            public function getAdminMenu(): AdminMenu
            {
                return new AdminMenu([
                    9 => 'assets.php',
                    10 => 'philosophy.php',
                    11 => 'display.php',
                    12 => 'impressum.php',
                ]);
            }

            public function getConfig(): \JTL\Plugin\Data\Config
            {
                return new \JTL\Plugin\Data\Config();
            }
        };
        $menu = (object) ['kPluginAdminMenu' => 9];

        ob_start();
        include dirname(__DIR__, 3) . '/plugin/MGD_AI_Kennzeichnung/adminmenu/assets.php';
        $html = ob_get_clean();

        self::assertIsString($html);
        self::assertStringContainsString('KI-Bildkennzeichnungen', $html);
        self::assertStringContainsString('name="kPlugin" value="17"', $html);
        self::assertStringContainsString('name="kPluginAdminMenu" value="9"', $html);
        self::assertStringContainsString('kPlugin=17&amp;kPluginAdminMenu=9&amp;view=cleanup', $html);
        self::assertStringContainsString('sort=status&amp;direction=desc&amp;status=generated', $html);
        self::assertStringNotContainsString('evil=', $html);

        \JTL\Smarty\JTLSmarty::$testFetchOutput = '<section>Neutraler Lesetab</section>';
        try {
            $otherOutputs = [];
            foreach ([['philosophy.php', 10], ['display.php', 11], ['impressum.php', 12]] as [$file, $menuId]) {
                $menu = (object) ['kPluginAdminMenu' => $menuId];
                ob_start();
                include dirname(__DIR__, 3) . '/plugin/MGD_AI_Kennzeichnung/adminmenu/' . $file;
                $otherOutputs[$file] = (string) ob_get_clean();
            }
        } finally {
            \JTL\Smarty\JTLSmarty::$testFetchOutput = '';
        }
        foreach ($otherOutputs as $file => $output) {
            self::assertSame('<section>Neutraler Lesetab</section>', $output, $file . ' darf die Assets-Query nicht verarbeiten.');
        }
        self::assertSame([], $logger->records, 'Die fremde Assets-Query darf in keinem Nachbartab Fehler loggen.');
    }
}

/** Zeichnet technische Warnungen auf, damit der Zyklus keine fremden Fehler verbergen kann. */
final class AdminEntryLogger extends AbstractLogger
{
    /** @var list<array{string, array<mixed>}> */
    public array $records = [];

    public function log(mixed $level, string|\Stringable $message, array $context = []): void
    {
        if ($level === 'warning' && is_string($message)) {
            $this->records[] = [$message, $context];
        }
    }
}
