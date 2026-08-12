<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel;

/** Darstellungsfertige Seite ausschließlich veralteter Fundstellen. */
final class CleanupListView
{
    /** @param list<array{id: int, asset_id: int, source_type: string, source_reference: string, last_seen_at: string}> $items */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $pageSize,
    ) {}
}
