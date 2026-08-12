<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Request;

/** Geschlossene und begrenzte Parameter einer Übersichtsseite. */
final class AssetListRequest
{
    /** @param array<string, string|bool> $filters */
    public function __construct(
        public readonly int $page,
        public readonly int $pageSize,
        public readonly array $filters,
        public readonly string $sort,
        public readonly string $direction,
    ) {}
}
