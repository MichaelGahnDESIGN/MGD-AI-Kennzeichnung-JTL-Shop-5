<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Result;

/** Bestätigt genau die technische ID des geänderten Assets. */
final class SingleUpdateResult
{
    public function __construct(public readonly int $updatedId) {}
}
