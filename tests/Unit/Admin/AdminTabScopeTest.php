<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

require_once __DIR__ . '/../../Stubs/JtlPluginStubs.php';

use JTL\Plugin\Data\AdminMenu;
use JTL\Plugin\Data\Config;
use JTL\Plugin\Data\Paths;
use JTL\Plugin\PluginInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Admin\Http\AdminTabScope;

/** Prüft, dass JTLs vollständiger Customlink-Zyklus nie fremde Requests an einen Tab weitergibt. */
final class AdminTabScopeTest extends TestCase
{
    #[Test]
    public function nur_der_kanonisch_adressierte_tab_erhaelt_den_echten_post_payload(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = [];
        $_POST = [
            'kPlugin' => '17',
            'kPluginAdminMenu' => '9',
            'csrf_token' => 'csrf',
            'font_size' => '12',
        ];

        $active = AdminTabScope::capture($this->plugin(), 9, 'display.php', true);
        $inactive = AdminTabScope::capture($this->plugin(), 7, 'assets.php');

        self::assertTrue($active->isAddressed);
        self::assertSame($_POST, $active->request->post);
        self::assertFalse($inactive->isAddressed);
        self::assertTrue($inactive->shouldRender);
        self::assertSame('GET', $inactive->request->method);
        self::assertSame([], $inactive->request->query);
        self::assertSame([], $inactive->request->post);
    }

    #[Test]
    public function fremde_oder_unkanonische_routen_werden_ohne_payload_traversierung_neutralisiert(): void
    {
        foreach ([
            ['kPlugin' => '17', 'kPluginAdminMenu' => '7'],
            ['kPlugin' => '017', 'kPluginAdminMenu' => '9'],
            ['kPlugin' => '17'],
        ] as $route) {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_GET = [];
            $_POST = $route + ['fremd' => ['a' => ['b' => ['c' => ['d' => 'ignorieren']]]]];

            $scope = AdminTabScope::capture($this->plugin(), 9, 'display.php', true);

            self::assertFalse($scope->isAddressed);
            self::assertTrue($scope->shouldRender);
            self::assertEquals(new \Plugin\MGD_AI_Kennzeichnung\Admin\Http\AdminHttpRequest('GET', [], []), $scope->request);
        }
    }

    #[Test]
    public function aktiver_get_reicht_nur_die_routebereinigte_query_an_den_adressierten_tab_durch(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['kPlugin' => '17', 'kPluginAdminMenu' => '7', 'view' => 'list'];
        $_POST = [];

        $active = AdminTabScope::capture($this->plugin(), 7, 'assets.php');
        $inactive = AdminTabScope::capture($this->plugin(), 9, 'display.php');

        self::assertTrue($active->isAddressed);
        self::assertSame(['view' => 'list'], $active->request->query);
        self::assertFalse($inactive->isAddressed);
        self::assertTrue($inactive->shouldRender);
        self::assertSame([], $inactive->request->query);
    }

    #[Test]
    public function normaler_get_ohne_route_laesst_jeden_gueltigen_tab_seinen_neutralen_lesezustand_rendern(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = [];
        $_POST = [];

        $assets = AdminTabScope::capture($this->plugin(), 7, 'assets.php');
        $display = AdminTabScope::capture($this->plugin(), 9, 'display.php', true);

        foreach ([$assets, $display] as $scope) {
            self::assertFalse($scope->isAddressed);
            self::assertTrue($scope->shouldRender);
            self::assertEquals(new \Plugin\MGD_AI_Kennzeichnung\Admin\Http\AdminHttpRequest('GET', [], []), $scope->request);
        }
    }

    #[Test]
    public function ungueltiger_dateikontext_bleibt_gesperrt_und_rendert_nicht(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = [];
        $_POST = [];

        $scope = AdminTabScope::capture($this->plugin(), 7, 'display.php');

        self::assertFalse($scope->isAddressed);
        self::assertFalse($scope->shouldRender);
        self::assertEquals(new \Plugin\MGD_AI_Kennzeichnung\Admin\Http\AdminHttpRequest('GET', [], []), $scope->request);
    }

    private function plugin(): PluginInterface
    {
        return new class implements PluginInterface {
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
                return new AdminMenu([7 => 'assets.php', 9 => 'display.php']);
            }

            public function getConfig(): Config
            {
                return new Config();
            }
        };
    }
}
