<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Adapter;

use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Port\ClockInterface;

/**
 * Liefert die aktuelle Unix-Zeit der Shop-Umgebung.
 *
 * Die kleine Adapterklasse hält den eigentlichen Update-Dienst unabhängig von
 * der Systemuhr. Dadurch bleiben Ablaufentscheidungen in Tests reproduzierbar.
 */
final class SystemClock implements ClockInterface
{
    public function now(): int
    {
        return time();
    }
}
