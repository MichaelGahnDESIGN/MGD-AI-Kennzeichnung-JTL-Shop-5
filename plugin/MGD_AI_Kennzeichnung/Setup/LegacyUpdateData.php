<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Setup;

use Throwable;

// alter Update-Ordner bis 1.5.0 > templates_c/mgd-update-client/MGD_AI_Kennzeichnung
// Übernahme macht der Baukasten (adoptLegacy), hier nur beim Deinstallieren wegräumen
// sonst holt adoptLegacy nach Neuinstallation die alte, schon abgemeldete ID zurück
final class LegacyUpdateData
{
    public const OLD_FOLDER = 'mgd-update-client/MGD_AI_Kennzeichnung';
    public const FILES = ['install.json', 'state.json'];

    public static function forget(string $compiledDir): void
    {
        try {
            $old = rtrim($compiledDir, '/\\') . '/' . self::OLD_FOLDER;
            if (!is_dir($old) || is_link($old)) {
                return;
            }
            foreach (self::FILES as $file) {
                $path = $old . '/' . $file;
                if (is_file($path) && !is_link($path)) {
                    @unlink($path);
                }
            }
        } catch (Throwable) {
            return;
        }
    }

    /** Ordner vom Shop, null = kein JTL */
    public static function compiledDir(): ?string
    {
        if (!defined('PFAD_ROOT') || !defined('PFAD_COMPILEDIR')) {
            return null;
        }
        $root = constant('PFAD_ROOT');
        $dir = constant('PFAD_COMPILEDIR');

        return is_string($root) && is_string($dir) ? $root . $dir : null;
    }
}
