<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Port;

use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Value\CachedRelease;

/** Speichert ausschließlich die letzte erfolgreich geprüfte Release-Antwort. */
interface ReleaseCacheInterface
{
    /** Reserviert die Prüfung nicht-blockierend für genau einen Prozess. */
    public function acquire(): bool;

    /** Gibt eine zuvor erworbene Reservierung wieder frei. */
    public function release(): void;

    public function load(): ?CachedRelease;

    public function save(CachedRelease $release): void;
}
