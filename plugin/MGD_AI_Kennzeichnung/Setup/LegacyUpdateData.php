<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Setup;

use Throwable;

// Brückenversion: Anmeldung + Update-Stand aus dem alten Ordner (bis 1.5.0) in den Baukasten-Ordner schieben
// alt > templates_c/mgd-update-client/MGD_AI_Kennzeichnung, neu > templates_c/mgd_ai_kennzeichnung_update
final class LegacyUpdateData
{
    public const OLD_FOLDER = 'mgd-update-client/MGD_AI_Kennzeichnung';
    public const NEW_FOLDER = 'mgd_ai_kennzeichnung_update';
    public const FILES = ['install.json', 'state.json'];

    /** verschiebt nur, wenns im neuen Ordner noch nix gibt. Fehler nie nach außen */
    public static function migrate(string $compiledDir): void
    {
        try {
            $base = rtrim($compiledDir, '/\\');
            $old = $base . '/' . self::OLD_FOLDER;
            if ($base === '' || !is_dir($old)) {
                return;
            }
            $new = $base . '/' . self::NEW_FOLDER;
            if (!is_dir($new) && !@mkdir($new, 0700, true) && !is_dir($new)) {
                return;
            }
            foreach (self::FILES as $file) {
                $from = $old . '/' . $file;
                $to = $new . '/' . $file;
                if (!is_file($from) || is_link($from)) {
                    continue;
                }
                if (is_file($to)) {
                    // neuer Stand gewinnt, alte Kopie weg (sonst kommt nach Neuinstallation die alte ID zurück)
                    @unlink($from);
                    continue;
                }
                if (!@rename($from, $to) && @copy($from, $to)) {
                    @unlink($from);
                }
                @chmod($to, 0600);
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
