<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Request;

/** Typisierte Auswahl veralteter technischer Fundstellen. */
final class CleanupPreviewRequest
{
    /** @param list<int> $usageIds */
    public function __construct(public readonly string $csrfToken, public readonly array $usageIds) {}
}
