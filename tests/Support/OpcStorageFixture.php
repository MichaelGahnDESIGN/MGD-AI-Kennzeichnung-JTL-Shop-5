<?php

declare(strict_types=1);

namespace Tests\Support;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

/** Eigene, zufällige Test-Shopwurzel; Originalbilder und Shopdaten bleiben unberührt. */
final class OpcStorageFixture
{
    public readonly string $shopRoot;
    public readonly string $storageRoot;

    public function __construct()
    {
        $this->shopRoot = sys_get_temp_dir() . '/mgd-opc-scan-' . bin2hex(random_bytes(10));
        $this->storageRoot = $this->shopRoot . '/media/image/storage/opc';
        if (!mkdir($this->storageRoot, 0700, true)) {
            throw new RuntimeException('Testordner konnte nicht erstellt werden.');
        }
    }

    /** Leere Dateien genügen: Der Scanner darf keine Bildinhalte lesen. */
    public function file(string $relativePath): string
    {
        $path = $this->storageRoot . '/' . $relativePath;
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0700, true);
        }
        if (!touch($path)) {
            throw new RuntimeException('Testdatei konnte nicht erstellt werden.');
        }

        return $path;
    }

    /** Entfernt nur die selbst angelegte Testwurzel, ohne Symlinks zu verfolgen. */
    public function cleanup(): void
    {
        $entries = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->shopRoot, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($entries as $entry) {
            if (!$entry instanceof SplFileInfo) {
                throw new RuntimeException('Unerwarteter Eintrag beim Aufräumen der Testwurzel.');
            }
            if ($entry->isDir() && !$entry->isLink()) {
                rmdir($entry->getPathname());
            } else {
                unlink($entry->getPathname());
            }
        }
        rmdir($this->shopRoot);
    }
}
