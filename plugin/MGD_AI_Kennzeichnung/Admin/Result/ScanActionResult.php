<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Result;

/** Sichere Rückmeldung ohne technische Fehlerdetails. */
final class ScanActionResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly string $message,
        public readonly int $createdAssets = 0,
        public readonly int $recordedUsages = 0,
    ) {}
}
