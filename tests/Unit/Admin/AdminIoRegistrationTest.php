<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

require_once __DIR__ . '/../../Stubs/JtlDatabaseStubs.php';
require_once __DIR__ . '/../../Stubs/JtlPluginStubs.php';

use JTL\Backend\AdminAccount;
use JTL\Backend\AdminIO;
use JTL\Cache\JTLCache;
use JTL\Cache\JTLCacheInterface;
use JTL\DB\DbInterface;
use JTL\Events\Dispatcher;
use JTL\Plugin\Data\AdminMenu;
use JTL\Plugin\Data\Config;
use JTL\Plugin\Data\Paths;
use JTL\Plugin\PluginInterface;
use JTL\Services\DefaultServicesInterface;
use JTL\Shop;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Bootstrap;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Tests\Support\TransactionalDatabaseFake;

final class AdminIoRegistrationTest extends TestCase
{
    #[Test]
    public function backend_bootstrap_registriert_den_offiziellen_admin_io_hook(): void
    {
        $db = new TransactionalDatabaseFake();
        $account = new AdminAccount(['PLUGIN_DETAIL_VIEW_17'], 5);
        Shop::$frontend = false;
        Shop::$container = new AdminIoServicesFake($db, $account);
        $plugin = new AdminIoPluginFake();
        $bootstrap = new class ($db, $plugin) extends Bootstrap {
            public function __construct(
                private readonly DbInterface $database,
                private readonly PluginInterface $plugin,
            ) {}

            public function getDB(): DbInterface
            {
                return $this->database;
            }

            public function getPlugin(): PluginInterface
            {
                return $this->plugin;
            }
        };
        $dispatcher = new Dispatcher();

        $bootstrap->boot($dispatcher);

        self::assertSame(['shop.hook.311'], $dispatcher->events());
        $io = new AdminIO();
        $dispatcher->dispatch('shop.hook.311', ['io' => $io]);
        self::assertSame(['mgd_ai_label_load', 'mgd_ai_label_save'], $io->registeredNames());

        Shop::$frontend = true;
        Shop::$container = null;
    }
}

final class AdminIoServicesFake implements DefaultServicesInterface
{
    public function __construct(
        private readonly DbInterface $db,
        private readonly AdminAccount $account,
    ) {}

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
        return new JTLCache();
    }

    public function getLogService(): LoggerInterface
    {
        return new NullLogger();
    }
}

final class AdminIoPluginFake implements PluginInterface
{
    public function getID(): int
    {
        return 17;
    }

    public function getPaths(): Paths
    {
        return new Paths();
    }

    public function getAdminMenu(): AdminMenu
    {
        return new AdminMenu();
    }

    public function getConfig(): Config
    {
        return new Config();
    }
}
