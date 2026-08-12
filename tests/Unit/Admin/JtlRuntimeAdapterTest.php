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

final class JtlRuntimeAdapterTest extends TestCase
{
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
}
