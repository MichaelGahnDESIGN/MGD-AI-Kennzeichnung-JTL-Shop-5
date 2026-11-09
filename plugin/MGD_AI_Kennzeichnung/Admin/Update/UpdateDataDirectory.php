<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Update;

use RuntimeException;

// Datenordner ausserhalb Web: Unterordner von templates_c/ im Shop, zusätzlich mit Zugriffssperre
final class UpdateDataDirectory
{
    public const FOLDER = 'mgd-update-client/MGD_AI_Kennzeichnung';

    private const HTACCESS = "# MGD Update Client: kein Webzugriff\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n";

    /** @param string $compiledDir Pfad templates_c/ des Shops (mit abschliessendem Slash) */
    public static function prepare(string $compiledDir): string
    {
        $base = rtrim($compiledDir, '/');
        if ($base === '' || !is_dir($base)) {
            throw new RuntimeException('Shop-Ordner templates_c nicht gefunden.');
        }
        $dir = $base . '/' . self::FOLDER;
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new RuntimeException('Datenordner nicht anlegbar.');
        }
        // Sperre in Elternordner und Datenordner
        foreach ([dirname($dir), $dir] as $d) {
            if (!is_file($d . '/.htaccess')) {
                @file_put_contents($d . '/.htaccess', self::HTACCESS);
            }
            if (!is_file($d . '/index.html')) {
                @file_put_contents($d . '/index.html', '');
            }
        }

        return $dir;
    }
}
