<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Port;

use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Value\ReleaseCheckState;

/** Speichert den Zeitpunkt jedes Abrufs und optional dessen geprüfte Release-Antwort. */
interface ReleaseCacheInterface
{
    /** Reserviert die Prüfung nicht-blockierend für genau einen Prozess. */
    public function acquire(): bool;

    /** Gibt eine zuvor erworbene Reservierung wieder frei. */
    public function release(): void;

    public function load(): ?ReleaseCheckState;

    public function save(ReleaseCheckState $state): void;
}
