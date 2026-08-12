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
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlSessionContext;

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

    #[Test]
    public function initialer_get_darf_ohne_clientroute_starten_und_einzelne_passende_route_mitbringen(): void
    {
        foreach ([[], ['kPlugin' => '17'], ['kPluginAdminMenu' => '9']] as $route) {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET = $route + ['view' => 'list'];
            $_POST = [];

            $request = (new JtlHttpRequestAdapter())->capture(17, 9);
            self::assertSame(['view' => 'list'], $request->query);
        }
    }

    #[Test]
    public function jeder_vorhandene_get_routenwert_wird_auch_ohne_den_anderen_exakt_validiert(): void
    {
        foreach ([['kPlugin' => '18'], ['kPluginAdminMenu' => '10'], ['kPlugin' => '017']] as $route) {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET = $route + ['view' => 'list'];
            $_POST = [];

            try {
                (new JtlHttpRequestAdapter())->capture(17, 9);
                self::fail('Jeder vorhandene GET-Routenwert muss exakt zum JTL-Kontext passen.');
            } catch (ValidationException) {
                self::addToAssertionCount(1);
            }
        }
    }

    #[Test]
    public function session_context_bewahrt_bestehende_referenzen_und_mutationen_in_beide_richtungen(): void
    {
        $_SESSION = ['vorhanden' => 'ja'];
        $bereitsGehalten = &$_SESSION;
        $context = &JtlSessionContext::current();

        $context['vom_context'] = 'sichtbar';
        $bereitsGehalten['von_referenz'] = 'sichtbar';
        self::assertSame([
            'vorhanden' => 'ja',
            'vom_context' => 'sichtbar',
            'von_referenz' => 'sichtbar',
        ], $context);
        self::assertSame($context, $bereitsGehalten);
    }

    #[Test]
    public function session_context_initialisiert_nur_einen_ungueltigen_sessionwert(): void
    {
        $_SESSION = 'ungueltig';

        $context = &JtlSessionContext::current();

        self::assertSame([], $context);
        self::assertSame([], $_SESSION);
    }

    #[Test]
    public function numerische_http_hauptschluessel_werden_nicht_stillschweigend_verworfen(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = [0 => ['zu' => ['tief' => ['und' => ['gross' => str_repeat('x', 1000)]]]]];
        $_POST = [];

        $this->expectException(ValidationException::class);
        (new JtlHttpRequestAdapter())->capture(17, 9);
    }

    #[Test]
    public function http_adapter_begrenzt_verschachtelung_bevor_der_fachnormalizer_laeuft(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['view' => 'list', 'nested' => ['a' => ['b' => ['c' => ['d' => 'zu tief']]]]];
        $_POST = [];

        $this->expectException(ValidationException::class);
        (new JtlHttpRequestAdapter())->capture(17, 9);
    }
}
