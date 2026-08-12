<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

require_once __DIR__ . '/../../Stubs/JtlDatabaseStubs.php';
require_once __DIR__ . '/../../Stubs/JtlPluginStubs.php';

use JTL\Backend\AdminAccount;
use JTL\Plugin\PluginInterface;
use JTL\Plugin\Data\Paths;
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
        };

        $controller = (new AdminRuntimeFactory())->create(
            $plugin,
            new TransactionalDatabaseFake(),
            new AdminAccount(['PLUGIN_DETAIL_VIEW_17'], 5),
            new NullLogger(),
            $session,
            'session-id',
        );

        self::assertInstanceOf(AdminAssetController::class, $controller);
    }
}
