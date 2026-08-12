<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Adapter;

use Plugin\MGD_AI_Kennzeichnung\Admin\Port\ConfirmationStoreInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Value\StoredOperation;

/**
 * Speichert Vorschauen ausschließlich in der bereits geschützten JTL-Admin-
 * Session. Weder Token noch Operation werden in Datenbank oder Log geschrieben.
 */
final class SessionConfirmationStore implements ConfirmationStoreInterface
{
    private const SESSION_KEY = 'mgd_ai_confirmations';

    /** @var array<mixed> */
    private array $session;

    /** @param array<mixed> $session */
    public function __construct(array &$session)
    {
        $this->session = &$session;
    }

    public function put(string $key, StoredOperation $operation, int $expiresAt): void
    {
        $now = time();
        $entries = $this->entries($now);
        if ($expiresAt <= $now) {
            /* Auch beim abgewiesenen Schreiben werden zuvor gelesene Altlasten sofort entfernt. */
            $this->session[self::SESSION_KEY] = $entries;

            return;
        }
        if (count($entries) >= 20) {
            uasort($entries, static fn(array $left, array $right): int => $left['expires_at'] <=> $right['expires_at']);
            array_shift($entries);
        }
        /* Nur skalare Arrays überstehen den nächsten JTL-Request unabhängig von der Autoload-Reihenfolge. */
        $entries[$key] = [
            'operation' => [
                'name' => $operation->name,
                'ids' => $operation->ids,
                'changes' => $operation->changes,
            ],
            'expires_at' => $expiresAt,
        ];
        $this->session[self::SESSION_KEY] = $entries;
    }

    public function take(string $key): ?array
    {
        $entries = $this->entries(time());
        $entry = $entries[$key] ?? null;
        unset($entries[$key]);
        $this->session[self::SESSION_KEY] = $entries;

        if ($entry === null) {
            return null;
        }
        $operation = $this->hydrateOperation($entry['operation']);

        return $operation === null ? null : ['operation' => $operation, 'expires_at' => $entry['expires_at']];
    }

    /**
     * @return array<string, array{
     *   operation: array{name: string, ids: list<int>, changes: array<string, string>},
     *   expires_at: int
     * }>
     */
    private function entries(int $now): array
    {
        $entries = $this->session[self::SESSION_KEY] ?? [];
        if (!is_array($entries)) {
            return [];
        }

        $valid = [];
        foreach ($entries as $key => $entry) {
            if (!is_string($key) || !is_array($entry)) {
                continue;
            }
            $operation = $this->hydrateOperation($entry['operation'] ?? null);
            $expiresAt = $entry['expires_at'] ?? null;
            if ($operation === null || !is_int($expiresAt) || $expiresAt <= $now) {
                continue;
            }
            /* In der Session verbleiben ausschließlich skalare, validierte Arrays; niemals PHP-Objekte. */
            $valid[$key] = [
                'operation' => [
                    'name' => $operation->name,
                    'ids' => $operation->ids,
                    'changes' => $operation->changes,
                ],
                'expires_at' => $expiresAt,
            ];
        }

        return $valid;
    }

    /** Rekonstruiert ausschließlich die erwartete skalare Operationsform. */
    private function hydrateOperation(mixed $raw): ?StoredOperation
    {
        if (!is_array($raw) || !is_string($raw['name'] ?? null)) {
            return null;
        }
        $rawIds = $raw['ids'] ?? null;
        $rawChanges = $raw['changes'] ?? null;
        if (!is_array($rawIds) || !array_is_list($rawIds) || !is_array($rawChanges) || array_is_list($rawChanges)) {
            return null;
        }
        $ids = [];
        foreach ($rawIds as $id) {
            if (!is_int($id)) {
                return null;
            }
            $ids[] = $id;
        }
        $changes = [];
        foreach ($rawChanges as $field => $value) {
            if (!is_string($field) || !is_string($value)) {
                return null;
            }
            $changes[$field] = $value;
        }

        return new StoredOperation($raw['name'], $ids, $changes);
    }
}
