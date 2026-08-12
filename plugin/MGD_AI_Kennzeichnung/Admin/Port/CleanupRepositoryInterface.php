<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Port;

/** Begrenzt Bereinigung auf fehlende, plugin-eigene Nutzungszeilen. */
interface CleanupRepositoryInterface
{
    /** @return list<array{id: int, asset_id: int, source_type: string, source_reference: string, last_seen_at: string}> */
    public function listOwnedStaleUsages(int $offset, int $limit): array;

    public function countOwnedStaleUsages(): int;

    /** @param list<int> $usageIds */
    public function countOwnedStaleUsageIds(array $usageIds): int;

    /** @param list<int> $usageIds */
    public function cleanupOwnedStaleUsages(array $usageIds): void;
}
