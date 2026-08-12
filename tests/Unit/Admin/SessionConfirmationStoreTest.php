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

        $expiresAt = time() + 300;
        $store->put('hash-key', $operation, $expiresAt);
        $entries = $session['mgd_ai_confirmations'] ?? null;
        if (!is_array($entries) || !is_array($entries['hash-key'] ?? null)) {
            self::fail('Die Sessionoperation wurde nicht in der erwarteten Form gespeichert.');
        }
        self::assertSame(
            ['name' => 'asset-bulk-update', 'ids' => [3], 'changes' => ['theme' => 'dark']],
            $entries['hash-key']['operation'] ?? null,
        );
        $stored = $store->take('hash-key');

        self::assertEquals(['operation' => $operation, 'expires_at' => $expiresAt], $stored);
        self::assertNull($store->take('hash-key'));
        self::assertSame(['bleibt' => true], $session['anderes_plugin']);
        self::assertSame([], $session['mgd_ai_confirmations']);
    }

    #[Test]
    public function put_entfernt_abgelaufene_und_malformedte_altlasten_und_bewahrt_lebende(): void
    {
        $session = ['mgd_ai_confirmations' => [
            'expired' => $this->rawEntry(time() - 1),
            'exact-now' => $this->rawEntry(time()),
            'malformed' => ['operation' => new \stdClass(), 'expires_at' => 'morgen'],
            'live' => $this->rawEntry(time() + 300),
        ]];
        $store = new SessionConfirmationStore($session);

        $store->put(
            'new',
            new StoredOperation('asset-bulk-update', [4], ['theme' => 'light']),
            time() + 300,
        );

        $entries = $session['mgd_ai_confirmations'] ?? null;
        self::assertIsArray($entries);
        self::assertSame(['live', 'new'], array_keys($entries));
        $live = $entries['live'] ?? null;
        self::assertIsArray($live);
        self::assertIsArray($live['operation'] ?? null);
    }

    #[Test]
    public function take_entfernt_abgelaufene_und_malformedte_altlasten_auch_bei_fremdem_schluessel(): void
    {
        $session = ['mgd_ai_confirmations' => [
            'expired' => $this->rawEntry(time() - 60),
            'malformed' => ['operation' => ['name' => 'x'], 'expires_at' => null],
            'live' => $this->rawEntry(time() + 300),
        ]];
        $store = new SessionConfirmationStore($session);

        self::assertNull($store->take('nicht-vorhanden'));
        $entries = $session['mgd_ai_confirmations'] ?? null;
        self::assertIsArray($entries);
        self::assertSame(['live'], array_keys($entries));
        $live = $entries['live'] ?? null;
        self::assertIsArray($live);
        self::assertIsArray($live['operation'] ?? null);
    }

    #[Test]
    public function neu_eingestellter_exakt_jetzt_abgelaufener_vorgang_wird_nicht_gespeichert(): void
    {
        $session = [];
        $store = new SessionConfirmationStore($session);

        $store->put(
            'expired-now',
            new StoredOperation('asset-bulk-update', [1], ['theme' => 'dark']),
            time(),
        );

        self::assertNull($store->take('expired-now'));
        self::assertSame([], $session['mgd_ai_confirmations']);
    }

    /** @return array{operation: array{name: string, ids: list<int>, changes: array<string, string>}, expires_at: int} */
    private function rawEntry(int $expiresAt): array
    {
        return [
            'operation' => ['name' => 'asset-bulk-update', 'ids' => [1], 'changes' => ['theme' => 'dark']],
            'expires_at' => $expiresAt,
        ];
    }
}
