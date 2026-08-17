<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Service;

/** Prüft die beiden verbindlichen Mindestversionen ohne lose Versionsformen. */
final class SystemCompatibilityCheck
{
    private const VERSION_PATTERN = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)$/D';

    public function supports(string $shopVersion, string $phpVersion): bool
    {
        if (preg_match(self::VERSION_PATTERN, $shopVersion) !== 1
            || preg_match(self::VERSION_PATTERN, $phpVersion) !== 1
        ) {
            return false;
        }

        return version_compare($shopVersion, '5.7.2', '>=')
            && version_compare($phpVersion, '8.1.0', '>=');
    }
}
