<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Port;

/** Protokolliert nur feste Ereigniscodes und unkritische Mengenwerte. */
interface LogPortInterface
{
    public function event(string $code, int $count): void;
}
