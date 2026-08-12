<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Result;

/** Minimiertes Ergebnis einer vollständig bestätigten Stapeländerung. */
final class BulkUpdateResult
{
    public function __construct(public readonly int $updatedCount) {}
}
