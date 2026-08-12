<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Adapter;

use Plugin\MGD_AI_Kennzeichnung\Admin\Port\ConfirmationPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\ConfirmationStoreInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Value\StoredOperation;

/** Erzeugt kryptografische Einmaltokens; gespeichert werden nur Hash und unveränderlicher Vorgang. */
final class OneTimeConfirmationAdapter implements ConfirmationPortInterface
{
    private const LIFETIME_SECONDS = 600;

    public function __construct(private readonly ConfirmationStoreInterface $store) {}

    public function issue(string $subjectKey, StoredOperation $operation): string
    {
        $token = bin2hex(random_bytes(32));
        $this->store->put($this->key($subjectKey, $token), $operation, time() + self::LIFETIME_SECONDS);

        return $token;
    }

    public function consume(string $subjectKey, string $token): ?StoredOperation
    {
        if (preg_match('/^[a-f0-9]{64}$/D', $token) !== 1) {
            return null;
        }
        $entry = $this->store->take($this->key($subjectKey, $token));

        if ($entry === null || $entry['expires_at'] < time()) {
            return null;
        }

        return $entry['operation'];
    }

    private function key(string $subjectKey, string $token): string
    {
        return hash('sha256', $subjectKey . "\0" . $token);
    }
}
