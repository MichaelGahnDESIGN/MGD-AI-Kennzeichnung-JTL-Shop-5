<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Port;

/** Liefert eine kontrollierbare Unix-Zeit für Ablaufentscheidungen. */
interface ClockInterface
{
    public function now(): int;
}
