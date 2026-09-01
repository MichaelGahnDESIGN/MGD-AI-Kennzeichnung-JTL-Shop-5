<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

require_once __DIR__ . '/../../Stubs/JtlDatabaseStubs.php';
require_once __DIR__ . '/../../Stubs/JtlPluginStubs.php';

use JTL\Backend\AdminAccount;
use JTL\Plugin\PluginInterface;
use JTL\Plugin\Data\Paths;
use JTL\Plugin\Data\AdminMenu;
use JTL\Plugin\Data\Config;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Admin\Controller\AdminAssetController;
use Plugin\MGD_AI_Kennzeichnung\Admin\Factory\AdminRuntimeFactory;
use Psr\Log\NullLogger;
use Tests\Support\TransactionalDatabaseFake;

final class AdminRuntimeFactoryTest extends TestCase
{
    #[Test]
    public function factory_komponiert_den_vollstaendigen_controller_ohne_globals(): void
    {
        $session = ['jtl_token' => 'csrf'];
        $plugin = new class implements PluginInterface {
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
                return new AdminMenu([9]);
            }

            public function getConfig(): Config
            {
                return new Config();
            }
        };

        $controller = (new AdminRuntimeFactory())->create(
            $plugin,
            new TransactionalDatabaseFake(),
            new AdminAccount(['PLUGIN_DETAIL_VIEW_17'], 5),
            new NullLogger(),
            $session,
            'session-id',
            9,
            '/nicht-vorhandene-test-shopwurzel',
        );

        self::assertInstanceOf(AdminAssetController::class, $controller);
    }

    #[Test]
    public function scan_button_erreicht_den_dateispeicher_der_serverseitigen_shopwurzel(): void
    {
        $fixture = new \Tests\Support\OpcStorageFixture();
        try {
            $fixture->file('banner/2026/neues-bild.jpg');
            $db = new TransactionalDatabaseFake();
            foreach (['xplugin_mgd_ai_asset', 'xplugin_mgd_ai_usage'] as $table) {
                $db->setMarker($table, 'mgd-ai-kennzeichnung-jtl-v1');
            }
            $plugin = $this->createStub(PluginInterface::class);
            $plugin->method('getID')->willReturn(17);
            $plugin->method('getPaths')->willReturn(new Paths());
            $session = ['jtl_token' => 'csrf'];
            $controller = (new AdminRuntimeFactory())->create(
                $plugin,
                $db,
                new AdminAccount(['PLUGIN_DETAIL_VIEW_17'], 5),
                new NullLogger(),
                $session,
                'session-id',
                9,
                $fixture->shopRoot,
            );
            $page = $controller->handle('POST', [], ['action' => 'scan', 'csrf_token' => 'csrf']);
            self::assertSame('Der Bildscan wurde abgeschlossen.', $page->variables['message']);
            self::assertSame(1, $db->assetCount());
            self::assertSame('unreviewed', $db->statusForAsset(hash(
                'sha256',
                'media/image/storage/opc/banner/2026/neues-bild.jpg',
            )));
        } finally {
            $fixture->cleanup();
        }
    }
}
