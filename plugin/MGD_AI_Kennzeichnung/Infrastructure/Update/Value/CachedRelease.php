<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Value;

/** Lokal gespeicherte, zuvor vollständig geprüfte Release-Information. */
final class CachedRelease
{
    public function __construct(
        public readonly string $tag,
        public readonly string $url,
        public readonly int $fetchedAt,
    ) {}
}
