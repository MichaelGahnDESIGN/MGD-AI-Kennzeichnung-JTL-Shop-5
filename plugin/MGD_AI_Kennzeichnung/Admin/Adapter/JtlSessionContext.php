<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Adapter;

/** Kapselt den Zugriff auf die von JTL bereits gestartete Admin-Session. */
final class JtlSessionContext
{
    /** @var array<string, mixed> */
    private static array $session = [];

    /** @return array<string, mixed> */
    public static function &current(): array
    {
        self::$session = [];
        foreach ($_SESSION as $key => $value) {
            if (is_string($key)) {
                self::$session[$key] = $value;
            }
        }
        $_SESSION = &self::$session;

        return self::$session;
    }
}
