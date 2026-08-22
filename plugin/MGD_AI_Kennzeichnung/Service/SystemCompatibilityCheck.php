<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Service;

/** Prüft die beiden verbindlichen Mindestversionen ohne mehrdeutige Versionsformen. */
final class SystemCompatibilityCheck
{
    private const SHOP_VERSION_PATTERN = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)$/D';

    /**
     * PHP-Hoster ergänzen PHP_VERSION teilweise um einen technischen
     * Anbieterzusatz wie „-nmm1“. Der numerische Versionskern bleibt dabei
     * eindeutig und kann sicher mit der Mindestversion verglichen werden.
     */
    private const PHP_VERSION_PATTERN =
        '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-[0-9A-Za-z][0-9A-Za-z.+~-]*)?$/D';

    public function supports(string $shopVersion, string $phpVersion): bool
    {
        if (preg_match(self::SHOP_VERSION_PATTERN, $shopVersion) !== 1
            || preg_match(self::PHP_VERSION_PATTERN, $phpVersion) !== 1
        ) {
            return false;
        }

        return version_compare($shopVersion, '5.7.2', '>=')
            && version_compare($phpVersion, '8.1.0', '>=');
    }
}
