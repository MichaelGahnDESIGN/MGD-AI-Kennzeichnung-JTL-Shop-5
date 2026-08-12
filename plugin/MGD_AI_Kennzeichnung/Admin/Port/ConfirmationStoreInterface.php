<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Port;

/**
 * Serverinterner Einmalspeicher, beispielsweise eine JTL-Admin-Session.
 * Implementierungen müssen take atomar lesen und entfernen.
 */
interface ConfirmationStoreInterface
{
    public function put(string $key, string $operationDigest, int $expiresAt): void;

    /** @return array{digest: string, expires_at: int}|null */
    public function take(string $key): ?array;
}
