<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Port;

/** Begrenzt Bereinigung auf fehlende, plugin-eigene Nutzungszeilen. */
interface CleanupRepositoryInterface
{
    /** @param list<int> $usageIds */
    public function countOwnedStaleUsageIds(array $usageIds): int;

    /** @param list<int> $usageIds */
    public function cleanupOwnedStaleUsages(array $usageIds): void;
}
