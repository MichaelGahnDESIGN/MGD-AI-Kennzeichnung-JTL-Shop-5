<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Value;

/**
 * Unveränderliche, vollständig serverseitig gespeicherte Vorschauoperation.
 * Der Browser kennt nur das zugehörige zufällige Einmaltoken.
 */
final class StoredOperation
{
    /**
     * @param list<int>             $ids
     * @param array<string, string> $changes
     */
    public function __construct(
        public readonly string $name,
        public readonly array $ids,
        public readonly array $changes,
    ) {}
}
