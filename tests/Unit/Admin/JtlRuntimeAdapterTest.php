<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

require_once __DIR__ . '/../../Stubs/JtlPluginStubs.php';

use JTL\Backend\AdminAccount;
use JTL\Helpers\Form;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlAuthorizationAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlCsrfAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\AccessDeniedException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\CsrfException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlHttpRequestAdapter;

final class JtlRuntimeAdapterTest extends TestCase
{
    #[Test]
    public function ausgeloggter_oder_unvollstaendig_zwei_faktor_bestaetigter_admin_wird_vor_rechtepruefung_abgewiesen(): void
    {
        $account = new AdminAccount(['PLUGIN_DETAIL_VIEW_17'], 4, false);
        $adapter = new JtlAuthorizationAdapter($account, 17, 'session-a');

        $this->expectException(AccessDeniedException::class);
        try {
            $adapter->assertCanManageAssets();
        } finally {
            self::assertSame(0, $account->permissionCalls);
        }
    }

    #[Test]
    public function eingeloggter_admin_benoetigt_zusaetzlich_das_passende_pluginrecht(): void
    {
        $denied = new AdminAccount([], 4, true);
        try {
            (new JtlAuthorizationAdapter($denied, 17, 'session-a'))->assertCanManageAssets();
            self::fail('Ein Login ohne Pluginrecht muss abgewiesen werden.');
        } catch (AccessDeniedException) {
            self::assertGreaterThan(0, $denied->permissionCalls);
        }

        $allowed = new AdminAccount(['PLUGIN_DETAIL_VIEW_17'], 4, true);
        (new JtlAuthorizationAdapter($allowed, 17, 'session-a'))->assertCanManageAssets();
        self::assertGreaterThan(0, $allowed->permissionCalls);
    }

    #[Test]
    public function berechtigung_nutzt_exakt_jtls_pluginspezifisches_recht(): void
    {
        $allowed = new JtlAuthorizationAdapter(new AdminAccount(['PLUGIN_DETAIL_VIEW_17'], 4), 17, 'session-a');
        $allowed->assertCanManageAssets();

        $this->expectException(AccessDeniedException::class);
        (new JtlAuthorizationAdapter(new AdminAccount(['PLUGIN_DETAIL_VIEW_18'], 4), 17, 'session-a'))
            ->assertCanManageAssets();
    }

    #[Test]
    public function subjektbindung_ist_pseudonym_und_an_admin_und_session_gebunden(): void
    {
        $one = new JtlAuthorizationAdapter(new AdminAccount([], 4), 17, 'session-a');
        $two = new JtlAuthorizationAdapter(new AdminAccount([], 4), 17, 'session-b');

        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $one->subjectKey());
        self::assertNotSame($one->subjectKey(), $two->subjectKey());
        self::assertStringNotContainsString('session-a', $one->subjectKey());
    }

    #[Test]
    public function csrf_adapter_nutzt_jtls_explizite_tokenpruefung_und_liefert_sessiontoken(): void
    {
        Form::$validToken = 'sicher';
        $session = ['jtl_token' => 'sicher'];
        $adapter = new JtlCsrfAdapter($session);

        $adapter->assertValid('sicher');
        self::assertSame('sicher', $adapter->token());

        $this->expectException(CsrfException::class);
        $adapter->assertValid('falsch');
    }

    #[Test]
    public function http_adapter_entfernt_nur_exakt_passende_feste_jtl_route_aus_dem_fachpayload(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = [];
        $_POST = [
            'kPlugin' => '17',
            'kPluginAdminMenu' => '9',
            'action' => 'scan',
            'csrf_token' => 'csrf',
        ];

        $request = (new JtlHttpRequestAdapter())->capture(17, 9);

        self::assertSame(['action' => 'scan', 'csrf_token' => 'csrf'], $request->post);
    }

    #[Test]
    public function manipulierte_oder_nicht_kanonische_jtl_route_wird_vor_dem_controller_abgelehnt(): void
    {
        foreach (['01', '+17', ' 17', '1e1', '18', '0', '999999999999999999999'] as $routeId) {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET = ['kPlugin' => $routeId, 'kPluginAdminMenu' => '9', 'view' => 'list'];
            $_POST = [];

            try {
                (new JtlHttpRequestAdapter())->capture(17, 9);
                self::fail('Eine offene oder nicht kanonische JTL-Route muss abgewiesen werden.');
            } catch (ValidationException) {
                self::addToAssertionCount(1);
            }
        }
    }

    #[Test]
    public function aktiver_request_muss_beide_jtl_routenwerte_enthalten(): void
    {
        foreach ([[], ['kPlugin' => '17'], ['kPluginAdminMenu' => '9']] as $route) {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_GET = [];
            $_POST = $route + ['action' => 'scan', 'csrf_token' => 'csrf'];

            try {
                (new JtlHttpRequestAdapter())->capture(17, 9);
                self::fail('Eine unvollständige JTL-Route muss abgewiesen werden.');
            } catch (ValidationException) {
                self::addToAssertionCount(1);
            }
        }
    }
}
