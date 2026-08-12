<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Port;

/** Stellt das vorhandene JTL-CSRF-Token ausschließlich für Hidden-Felder bereit. */
interface CsrfTokenPortInterface
{
    public function token(): string;
}
