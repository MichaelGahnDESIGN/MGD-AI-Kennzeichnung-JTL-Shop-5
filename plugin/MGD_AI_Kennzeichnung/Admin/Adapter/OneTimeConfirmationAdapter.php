<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Adapter;

use Plugin\MGD_AI_Kennzeichnung\Admin\Port\ConfirmationPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\ConfirmationStoreInterface;

/** Erzeugt kryptografische Einmaltokens; gespeichert werden nur Hash und Vorgangsdigest. */
final class OneTimeConfirmationAdapter implements ConfirmationPortInterface
{
    private const LIFETIME_SECONDS = 600;

    public function __construct(private readonly ConfirmationStoreInterface $store) {}

    public function issue(string $subjectKey, string $operationDigest): string
    {
        $token = bin2hex(random_bytes(32));
        $this->store->put($this->key($subjectKey, $token), $operationDigest, time() + self::LIFETIME_SECONDS);

        return $token;
    }

    public function consume(string $subjectKey, string $operationDigest, string $token): bool
    {
        if (preg_match('/^[a-f0-9]{64}$/D', $token) !== 1) {
            return false;
        }
        $entry = $this->store->take($this->key($subjectKey, $token));

        return $entry !== null
            && $entry['expires_at'] >= time()
            && hash_equals($entry['digest'], $operationDigest);
    }

    private function key(string $subjectKey, string $token): string
    {
        return hash('sha256', $subjectKey . "\0" . $token);
    }
}
