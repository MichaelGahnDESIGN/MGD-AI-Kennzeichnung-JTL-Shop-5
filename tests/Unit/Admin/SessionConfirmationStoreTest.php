<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\SessionConfirmationStore;
use Plugin\MGD_AI_Kennzeichnung\Admin\Value\StoredOperation;

final class SessionConfirmationStoreTest extends TestCase
{
    #[Test]
    public function session_speichert_minimierte_operation_und_take_entfernt_sie_atomar(): void
    {
        $session = ['anderes_plugin' => ['bleibt' => true]];
        $store = new SessionConfirmationStore($session);
        $operation = new StoredOperation('asset-bulk-update', [3], ['theme' => 'dark']);

        $store->put('hash-key', $operation, 123456);
        $entries = $session['mgd_ai_confirmations'] ?? null;
        if (!is_array($entries) || !is_array($entries['hash-key'] ?? null)) {
            self::fail('Die Sessionoperation wurde nicht in der erwarteten Form gespeichert.');
        }
        self::assertSame(
            ['name' => 'asset-bulk-update', 'ids' => [3], 'changes' => ['theme' => 'dark']],
            $entries['hash-key']['operation'] ?? null,
        );
        $stored = $store->take('hash-key');

        self::assertEquals(['operation' => $operation, 'expires_at' => 123456], $stored);
        self::assertNull($store->take('hash-key'));
        self::assertSame(['bleibt' => true], $session['anderes_plugin']);
        self::assertSame([], $session['mgd_ai_confirmations']);
    }
}
