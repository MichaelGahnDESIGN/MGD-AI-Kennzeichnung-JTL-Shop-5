<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Port;

/** Trennt die fachliche Eingabeprüfung von JTLs persistenter Plugin-Konfiguration. */
interface DisplayConfigPortInterface
{
    /** @return array<string, mixed> */
    public function load(): array;

    /** @param array<string, string> $values */
    public function save(array $values): void;
}
