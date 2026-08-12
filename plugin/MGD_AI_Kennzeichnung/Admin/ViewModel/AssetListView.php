<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel;

/**
 * Enthält ausschließlich einfache, escaped-ready Daten. HTML wird erst im
 * Template kontextgerecht erzeugt und niemals in diesem Objekt gespeichert.
 */
final class AssetListView
{
    /**
     * @param list<array<string, scalar|null>> $items
     * @param array<string, scalar|null>|null $detail
     * @param array<string, string|bool> $filters
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $pageSize,
        public readonly array $filters,
        public readonly string $sort,
        public readonly string $direction,
        public readonly ?array $detail = null,
    ) {}
}
