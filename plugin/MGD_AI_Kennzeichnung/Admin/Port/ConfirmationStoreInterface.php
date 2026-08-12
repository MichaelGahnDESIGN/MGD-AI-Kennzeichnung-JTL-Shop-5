<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Port;

use Plugin\MGD_AI_Kennzeichnung\Admin\Value\StoredOperation;

/**
 * Serverinterner Einmalspeicher, beispielsweise eine JTL-Admin-Session.
 * Implementierungen müssen take atomar lesen und entfernen.
 */
interface ConfirmationStoreInterface
{
    public function put(string $key, StoredOperation $operation, int $expiresAt): void;

    /** @return array{operation: StoredOperation, expires_at: int}|null */
    public function take(string $key): ?array;
}
