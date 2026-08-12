<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Port;

/** Kapselt die CSRF-Prüfung des jeweiligen JTL-Shop-Kontexts. */
interface CsrfPortInterface
{
    public function assertValid(string $token): void;
}
