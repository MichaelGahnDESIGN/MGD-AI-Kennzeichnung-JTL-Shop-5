<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Scanner\Filesystem;

/** Löst ausschließlich den festen JTL-OPC-Speicher unter der serverseitigen Shopwurzel auf. */
final class OpcStorageRoot
{
    public const RELATIVE_PATH = 'media/image/storage/opc';

    public function __construct(private readonly string $shopRoot) {}

    /** Erst beim autorisierten Scan prüfen, nicht beim Anzeigen eines Admin-Tabs. */
    public function resolve(): string
    {
        clearstatcache(true);
        $root = @realpath($this->shopRoot);
        if ($root === false || $root === DIRECTORY_SEPARATOR || !is_dir($root)) {
            throw new OpcStorageScanException(OpcStorageScanFailure::RootUnavailable);
        }
        $path = $root;
        foreach (explode('/', self::RELATIVE_PATH) as $segment) {
            $path .= DIRECTORY_SEPARATOR . $segment;
            if (is_link($path)) {
                throw new OpcStorageScanException(OpcStorageScanFailure::UnsafePath);
            }
            if (!is_dir($path) || !is_readable($path) || !is_executable($path)) {
                throw new OpcStorageScanException(OpcStorageScanFailure::RootUnavailable);
            }
            // Exakte Übereinstimmung verhindert auch Umleitungen in ähnlich benannte Nachbarordner.
            if (@realpath($path) !== $path) {
                throw new OpcStorageScanException(OpcStorageScanFailure::UnsafePath);
            }
        }

        return $path;
    }
}
