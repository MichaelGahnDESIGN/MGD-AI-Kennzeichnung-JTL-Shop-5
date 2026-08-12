<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel;

/** Beschreibt ausschließlich ein festes Template und dessen vorbereitete Variablen. */
final class AdminPage
{
    /** @param array<string, mixed> $variables */
    public function __construct(public readonly string $template, public readonly array $variables) {}
}
