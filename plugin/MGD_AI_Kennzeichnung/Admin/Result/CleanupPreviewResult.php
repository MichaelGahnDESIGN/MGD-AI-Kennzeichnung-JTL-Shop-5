<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Result;

/** Vorschau ausschließlich für explizit gewählte veraltete Fundstellen. */
final class CleanupPreviewResult
{
    public function __construct(public readonly int $count, public readonly string $confirmationToken) {}
}
