<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\OneTimeConfirmationAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\ConfirmationStoreInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Value\StoredOperation;

final class OneTimeConfirmationTest extends TestCase
{
    #[Test]
    public function token_ist_undurchsichtig_an_subjekt_und_digest_gebunden_und_nur_einmal_nutzbar(): void
    {
        $store = new MemoryConfirmationStore();
        $confirmation = new OneTimeConfirmationAdapter($store);
        $operation = new StoredOperation('asset-bulk-update', [1], ['status' => 'generated']);
        $token = $confirmation->issue('sitzung-a', $operation);

        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $token);
        self::assertNull($confirmation->consume('sitzung-b', $token));
        $lease = $confirmation->consume('sitzung-a', $token);
        self::assertNotNull($lease);
        self::assertEquals($operation, $lease->operation);
        $lease->release();
        self::assertNull($confirmation->consume('sitzung-a', $token));
    }

    #[Test]
    public function abgelaufener_vorgang_wird_entfernt_und_nicht_ausgefuehrt(): void
    {
        $store = new MemoryConfirmationStore();
        $confirmation = new OneTimeConfirmationAdapter($store);
        $token = $confirmation->issue(
            'sitzung-a',
            new StoredOperation('asset-bulk-update', [1], ['status' => 'generated']),
        );
        $store->expireAll();

        self::assertNull($confirmation->consume('sitzung-a', $token));
        self::assertNull($confirmation->consume('sitzung-a', $token));
    }

    #[Test]
    public function exakt_jetzt_ablaufende_bestaetigung_ist_bereits_ungueltig(): void
    {
        $store = new MemoryConfirmationStore();
        $confirmation = new OneTimeConfirmationAdapter($store);
        $token = $confirmation->issue(
            'sitzung-a',
            new StoredOperation('asset-bulk-update', [1], ['status' => 'generated']),
        );
        $store->expireAt(time());

        self::assertNull($confirmation->consume('sitzung-a', $token));
    }
}

final class MemoryConfirmationStore implements ConfirmationStoreInterface
{
    /** @var array<string, array{operation: StoredOperation, expires_at: int}> */
    private array $entries = [];

    public function put(string $key, StoredOperation $operation, int $expiresAt): void
    {
        $this->entries[$key] = ['operation' => $operation, 'expires_at' => $expiresAt];
    }

    public function take(string $key): ?array
    {
        $entry = $this->entries[$key] ?? null;
        unset($this->entries[$key]);

        return $entry;
    }

    public function expireAll(): void
    {
        foreach ($this->entries as &$entry) {
            $entry['expires_at'] = 1;
        }
        unset($entry);
    }

    public function expireAt(int $timestamp): void
    {
        foreach ($this->entries as &$entry) {
            $entry['expires_at'] = $timestamp;
        }
        unset($entry);
    }
}
