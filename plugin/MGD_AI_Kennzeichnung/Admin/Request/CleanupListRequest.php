<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Request;

/** Begrenzte Seite veralteter technischer Fundstellen. */
final class CleanupListRequest
{
    public function __construct(public readonly int $page, public readonly int $pageSize) {}
}
