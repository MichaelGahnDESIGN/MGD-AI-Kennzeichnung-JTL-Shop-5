<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Adapter;

use JsonException;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Port\ReleaseCacheInterface;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Value\CachedRelease;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Value\ReleaseCheckState;
use RuntimeException;

/**
 * Speichert den zuletzt geprüften Release-Hinweis als kleine lokale JSON-Datei.
 *
 * Die Datei enthält weder Personen- noch Zugangsdaten. Eine nicht-blockierende
 * Exklusivsperre verhindert, dass mehrere Shop-Prozesse GitHub gleichzeitig
 * abfragen. Rechte 0600 begrenzen den Zugriff auf den Webserver-Benutzer.
 */
final class FileReleaseCache implements ReleaseCacheInterface
{
    /** @var resource|null Geöffnete und exklusiv gesperrte Cache-Datei */
    private $handle = null;

    public function __construct(private readonly string $path) {}

    public function acquire(): bool
    {
        if (is_resource($this->handle)) {
            return true;
        }

        $this->assertSafePath();
        $handle = @fopen($this->path, 'c+b');
        if ($handle === false) {
            throw new RuntimeException('Der lokale Release-Cache konnte nicht geöffnet werden.');
        }

        @chmod($this->path, 0600);
        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);

            return false;
        }

        $this->handle = $handle;

        return true;
    }

    public function release(): void
    {
        if (!is_resource($this->handle)) {
            return;
        }

        flock($this->handle, LOCK_UN);
        fclose($this->handle);
        $this->handle = null;
    }

    public function load(): ?ReleaseCheckState
    {
        if (!$this->isSafeExistingPath()) {
            return null;
        }

        if (is_resource($this->handle)) {
            rewind($this->handle);
            $inhalt = stream_get_contents($this->handle, 65_537);

            return is_string($inhalt) ? $this->decode($inhalt) : null;
        }

        $handle = @fopen($this->path, 'rb');
        if ($handle === false) {
            return null;
        }

        try {
            if (!flock($handle, LOCK_SH)) {
                return null;
            }

            $inhalt = stream_get_contents($handle, 65_537);

            return is_string($inhalt) ? $this->decode($inhalt) : null;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function save(ReleaseCheckState $state): void
    {
        if (!is_resource($this->handle)) {
            throw new RuntimeException('Der Release-Cache ist nicht exklusiv gesperrt.');
        }

        if (!$this->isValidState($state)) {
            throw new RuntimeException('Ungültige Release-Daten werden nicht gespeichert.');
        }

        try {
            $json = json_encode([
                'attemptedAt' => $state->attemptedAt,
                'release' => $state->release === null ? null : [
                    'tag' => $state->release->tag,
                    'url' => $state->release->url,
                    'fetchedAt' => $state->release->fetchedAt,
                ],
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException $exception) {
            throw new RuntimeException('Der Release-Cache konnte nicht kodiert werden.', 0, $exception);
        }

        rewind($this->handle);
        if (!ftruncate($this->handle, 0)
            || !$this->writeFully($json)
            || !fflush($this->handle)
        ) {
            throw new RuntimeException('Der Release-Cache konnte nicht vollständig gespeichert werden.');
        }

        @chmod($this->path, 0600);
    }

    private function decode(string $inhalt): ?ReleaseCheckState
    {
        if ($inhalt === '' || strlen($inhalt) > 65_536) {
            return null;
        }

        try {
            $daten = json_decode($inhalt, true, 4, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (!is_array($daten)
            || array_keys($daten) !== ['attemptedAt', 'release']
            || !is_int($daten['attemptedAt'])
        ) {
            return null;
        }

        $release = $this->decodeRelease($daten['release']);
        if ($daten['release'] !== null && $release === null) {
            return null;
        }

        try {
            return new ReleaseCheckState($daten['attemptedAt'], $release);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    private function decodeRelease(mixed $release): ?CachedRelease
    {
        if (!is_array($release)
            || array_keys($release) !== ['tag', 'url', 'fetchedAt']
            || !is_string($release['tag'])
            || !is_string($release['url'])
            || !is_int($release['fetchedAt'])
            || !$this->isValid($release['tag'], $release['url'], $release['fetchedAt'])
        ) {
            return null;
        }

        return new CachedRelease($release['tag'], $release['url'], $release['fetchedAt']);
    }

    private function isValidState(ReleaseCheckState $state): bool
    {
        return $state->attemptedAt >= 0
            && ($state->release === null || $this->isValid(
                $state->release->tag,
                $state->release->url,
                $state->release->fetchedAt,
            ));
    }

    private function isValid(string $tag, string $url, int $fetchedAt): bool
    {
        return preg_match('/^v(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)$/D', $tag) === 1
            && $url === 'https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/releases/tag/' . $tag
            && $fetchedAt >= 0;
    }

    /** Schreibt die bereits unter exklusiver Sperre vorbereiteten Minimaldaten vollständig. */
    private function writeFully(string $content): bool
    {
        if (!is_resource($this->handle)) {
            return false;
        }

        $offset = 0;
        $length = strlen($content);
        while ($offset < $length) {
            $written = fwrite($this->handle, substr($content, $offset));
            if (!is_int($written) || $written < 1) {
                return false;
            }
            $offset += $written;
        }

        return true;
    }

    /** Lehnt Symlinks und nicht reguläre Zieldateien vor jedem Öffnen ab. */
    private function assertSafePath(): void
    {
        $directory = dirname($this->path);
        if (is_link($this->path) || !is_dir($directory) || is_link($directory)) {
            throw new RuntimeException('Der Release-Cachepfad ist nicht sicher.');
        }

        /* Der System-Temp-Ordner gehört dem Host und darf nicht umkonfiguriert werden. */
        $systemTemp = realpath(sys_get_temp_dir());
        $cacheDirectory = realpath($directory);
        if ($systemTemp === false || $cacheDirectory === false) {
            throw new RuntimeException('Der Release-Cachepfad ist nicht sicher.');
        }
        if ($cacheDirectory !== $systemTemp) {
            @chmod($directory, 0700);
            if ((fileperms($directory) & 0777) !== 0700) {
                throw new RuntimeException('Das Release-Cacheverzeichnis konnte nicht geschützt werden.');
            }
        }
    }

    private function isSafeExistingPath(): bool
    {
        if (is_link($this->path)) {
            return false;
        }

        if (!file_exists($this->path)) {
            return true;
        }

        $stat = @lstat($this->path);

        return is_array($stat) && (($stat['mode'] & 0170000) === 0100000);
    }
}
