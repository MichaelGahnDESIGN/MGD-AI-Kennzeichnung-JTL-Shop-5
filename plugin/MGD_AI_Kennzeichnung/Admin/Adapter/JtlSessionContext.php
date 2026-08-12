<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Adapter;

/** Kapselt den Zugriff auf die von JTL bereits gestartete Admin-Session. */
final class JtlSessionContext
{
    /** @return array<mixed> */
    public static function &current(): array
    {
        $session = &$GLOBALS['_SESSION'];
        if (!is_array($session)) {
            $_SESSION = [];
        }

        return $_SESSION;
    }
}
