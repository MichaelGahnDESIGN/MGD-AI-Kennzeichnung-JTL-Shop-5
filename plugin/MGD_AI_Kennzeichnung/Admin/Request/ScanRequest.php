<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Request;

/** Typisierte Eingabe für einen manuellen Scan. */
final class ScanRequest
{
    public function __construct(public readonly string $csrfToken) {}
}
