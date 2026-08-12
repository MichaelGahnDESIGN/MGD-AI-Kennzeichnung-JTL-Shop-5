<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\OneTimeConfirmationAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\ConfirmationStoreInterface;

final class OneTimeConfirmationTest extends TestCase
{
    #[Test]
    public function token_ist_undurchsichtig_an_subjekt_und_digest_gebunden_und_nur_einmal_nutzbar(): void
    {
        $store = new MemoryConfirmationStore();
        $confirmation = new OneTimeConfirmationAdapter($store);
        $token = $confirmation->issue('sitzung-a', hash('sha256', 'operation-a'));

        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $token);
        self::assertFalse($confirmation->consume('sitzung-b', hash('sha256', 'operation-a'), $token));
        self::assertTrue($confirmation->consume('sitzung-a', hash('sha256', 'operation-a'), $token));
        self::assertFalse($confirmation->consume('sitzung-a', hash('sha256', 'operation-a'), $token));
        self::assertFalse($confirmation->consume('sitzung-a', hash('sha256', 'operation-b'), $token));
    }
}

final class MemoryConfirmationStore implements ConfirmationStoreInterface
{
    /** @var array<string, array{digest: string, expires_at: int}> */
    private array $entries = [];

    public function put(string $key, string $operationDigest, int $expiresAt): void
    {
        $this->entries[$key] = ['digest' => $operationDigest, 'expires_at' => $expiresAt];
    }

    public function take(string $key): ?array
    {
        $entry = $this->entries[$key] ?? null;
        unset($this->entries[$key]);

        return $entry;
    }
}
