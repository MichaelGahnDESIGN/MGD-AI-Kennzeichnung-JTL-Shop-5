<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Result;

/** Ergebnis einer rein technischen Fundstellenbereinigung. */
final class CleanupResult
{
    public function __construct(public readonly int $cleanedCount) {}
}
