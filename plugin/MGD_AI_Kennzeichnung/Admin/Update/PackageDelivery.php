<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Update;

// Geprüftes ZIP als Download (nur aus dem Quarantäne-Ordner)
final class PackageDelivery
{
    public static function isDeliverable(string $file, string $dataDir): bool
    {
        $real = realpath($file);
        $root = realpath(rtrim($dataDir, '/') . '/quarantine');
        if ($real === false || $root === false || !is_file($real) || is_link($file)) {
            return false;
        }

        return str_starts_with($real, $root . DIRECTORY_SEPARATOR)
            && preg_match('/^[A-Za-z0-9._-]{1,120}\.zip$/D', basename($real)) === 1;
    }

    // Rückgabe false = nichts gesendet (Ausgabe lief schon oder Datei nicht erlaubt)
    public static function send(string $file, string $dataDir): bool
    {
        if (!self::isDeliverable($file, $dataDir) || headers_sent()) {
            return false;
        }
        $size = filesize($file);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        if (is_int($size)) {
            header('Content-Length: ' . $size);
        }
        readfile($file);

        return true;
    }
}
