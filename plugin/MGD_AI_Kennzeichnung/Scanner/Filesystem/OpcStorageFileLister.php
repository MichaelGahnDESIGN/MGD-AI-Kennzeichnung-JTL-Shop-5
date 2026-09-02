<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Scanner\Filesystem;

use DirectoryIterator;
use Plugin\MGD_AI_Kennzeichnung\Scanner\LocalPathNormalizer;
use Throwable;

/**
 * Liest nur Namen und Metadaten innerhalb des OPC-Speichers, niemals Bildinhalte.
 * Grenzen gelten für den gesamten Lauf. Kein Teilresultat verlässt diese Klasse.
 */
final class OpcStorageFileLister
{
    private const MAXIMUM_IMAGES = 9999;
    private const MAXIMUM_ENTRIES = 20000;
    private const MAXIMUM_DEPTH = 32;

    public function __construct(
        private readonly OpcStorageRoot $root,
        private readonly LocalPathNormalizer $normalizer,
    ) {}

    /** @return list<string> Vollständige, sortierte relative Shop-Bildpfade. */
    public function listPaths(): array
    {
        try {
            $root = $this->root->resolve();
            $paths = [];
            $entries = 0;
            $this->walk($root, $root, '', 0, $entries, $paths);
            if ($this->root->resolve() !== $root) {
                throw new OpcStorageScanException(OpcStorageScanFailure::UnsafePath);
            }
            sort($paths, SORT_STRING);

            return $paths;
        } catch (OpcStorageScanException $error) {
            throw $error;
        } catch (Throwable) {
            // Dateisystemausnahmen können absolute Pfade enthalten: nicht weiterreichen oder verketten.
            throw new OpcStorageScanException(OpcStorageScanFailure::TraversalFailed);
        }
    }

    /**
     * Iteriert Ordner eintragsweise, statt unbeschränkt alle Namen vorab einzulesen.
     *
     * @param list<string> $paths
     */
    private function walk(string $root, string $directory, string $relative, int $depth, int &$entries, array &$paths): void
    {
        if ($depth > self::MAXIMUM_DEPTH) {
            throw new OpcStorageScanException(OpcStorageScanFailure::DepthLimit);
        }
        $before = $this->directoryIdentity($root, $directory);
        foreach (new DirectoryIterator($directory) as $entry) {
            if ($entry->isDot()) {
                continue;
            }
            if (++$entries > self::MAXIMUM_ENTRIES) {
                throw new OpcStorageScanException(OpcStorageScanFailure::EntryLimit);
            }
            $name = $entry->getFilename();
            $path = $directory . DIRECTORY_SEPARATOR . $name;
            $local = $relative === '' ? $name : $relative . '/' . $name;
            clearstatcache(true, $path);
            $stat = @lstat($path);
            if ($stat === false) {
                throw new OpcStorageScanException(OpcStorageScanFailure::TraversalFailed);
            }
            $type = $stat['mode'] & 0170000;
            if ($type === 0120000) {
                continue; // Auch defekte Links und Schleifen werden niemals verfolgt.
            }
            if ($type === 0040000) {
                // elFinder speichert hier ausschließlich automatisch erzeugte Vorschauen.
                // Den Cache vor dem Betreten auslassen, auch in Unterordnern. Echte
                // Uploads und ihre strenge Pfadprüfung bleiben davon unberührt.
                if ($name === '.tmb') {
                    continue;
                }
                $this->walk($root, $path, $local, $depth + 1, $entries, $paths);
            } elseif ($type === 0100000 && preg_match('/\.(?:jpe?g|png|webp|gif|avif)$/iD', $name) === 1) {
                $this->assertContained($root, $path);
                $raw = OpcStorageRoot::RELATIVE_PATH . '/' . $local;
                $normalized = $this->normalizer->normalize($raw);
                if ($normalized === null || $normalized !== $raw) {
                    // Ein realer Name wie "bild%20eins.jpg" darf nicht zur anderen Datei "bild eins.jpg" werden.
                    throw new OpcStorageScanException(OpcStorageScanFailure::InvalidFilePath);
                }
                $paths[] = $normalized;
                if (count($paths) > self::MAXIMUM_IMAGES) {
                    throw new OpcStorageScanException(OpcStorageScanFailure::ImageLimit);
                }
            }
        }
        if ($this->directoryIdentity($root, $directory) !== $before) {
            throw new OpcStorageScanException(OpcStorageScanFailure::TraversalFailed);
        }
    }

    /** Prüft vor und nach dem Lesen, dass der Ordner nicht ersetzt oder umgeleitet wurde. */
    private function directoryIdentity(string $root, string $directory): string
    {
        clearstatcache(true, $directory);
        $this->assertContained($root, $directory);
        $stat = @lstat($directory);
        if ($stat === false || ($stat['mode'] & 0170000) !== 0040000) {
            throw new OpcStorageScanException(OpcStorageScanFailure::TraversalFailed);
        }
        if (!is_readable($directory) || !is_executable($directory)) {
            throw new OpcStorageScanException(OpcStorageScanFailure::UnreadableDirectory);
        }

        return $stat['dev'] . ':' . $stat['ino'] . ':' . $stat['mtime'] . ':' . $stat['ctime'];
    }

    /** Verzeichnisgrenze und kanonischer Pfad müssen zugleich passen; kein Präfixvergleich allein. */
    private function assertContained(string $root, string $path): void
    {
        $canonical = @realpath($path);
        if ($canonical === false || $canonical !== $path || is_link($path)
            || ($canonical !== $root && !str_starts_with($canonical, $root . DIRECTORY_SEPARATOR))
        ) {
            throw new OpcStorageScanException(OpcStorageScanFailure::UnsafePath);
        }
    }
}
