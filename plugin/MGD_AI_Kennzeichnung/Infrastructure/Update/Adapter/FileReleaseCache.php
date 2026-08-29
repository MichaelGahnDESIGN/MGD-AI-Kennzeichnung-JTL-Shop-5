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
    /** @var resource|null Geöffnete und exklusiv gesperrte Begleitdatei */
    private $handle = null;

    public function __construct(private readonly string $path) {}

    public function acquire(): bool
    {
        if (is_resource($this->handle)) {
            return true;
        }

        $this->assertSafePath();
        if (!$this->ensurePrivateLockFile()) {
            throw new RuntimeException('Die Release-Cache-Sperre konnte nicht geschützt werden.');
        }
        $handle = $this->openVerifiedFile($this->lockPath(), 'c+b');
        if ($handle === false) {
            throw new RuntimeException('Der lokale Release-Cache konnte nicht geöffnet werden.');
        }

        if (!$this->hasPrivateRegularPermissions($handle, $this->lockPath())) {
            fclose($handle);
            throw new RuntimeException('Die Release-Cache-Sperre konnte nicht geschützt werden.');
        }
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
        if (is_resource($this->handle)) {
            return $this->readVerifiedState();
        }

        try {
            $this->assertSafeDirectory();
        } catch (RuntimeException) {
            return null;
        }
        if (!$this->ensurePrivateLockFile()) {
            return null;
        }

        $handle = $this->openVerifiedFile($this->lockPath(), 'c+b');
        if ($handle === false) {
            return null;
        }

        try {
            if (!$this->hasPrivateRegularPermissions($handle, $this->lockPath()) || !flock($handle, LOCK_SH)) {
                return null;
            }

            return $this->readVerifiedState();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function save(ReleaseCheckState $state): void
    {
        if (!is_resource($this->handle) || !$this->hasPrivateRegularPermissions($this->handle, $this->lockPath())) {
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

        $this->saveAtomically($json);
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

    /** Schreibt zuerst in eine neue Datei desselben Dateisystems und benennt sie erst dann atomar um. */
    private function saveAtomically(string $content): void
    {
        $temporaryPath = @tempnam(dirname($this->path), '.' . basename($this->path) . '.');
        if (!is_string($temporaryPath)) {
            throw new RuntimeException('Der Release-Cache konnte nicht atomar vorbereitet werden.');
        }

        $temporaryHandle = $this->openVerifiedFile($temporaryPath, 'r+b');
        if ($temporaryHandle === false) {
            @unlink($temporaryPath);
            throw new RuntimeException('Der Release-Cache konnte nicht atomar geöffnet werden.');
        }

        try {
            if (!$this->hasPrivateRegularPermissions($temporaryHandle, $temporaryPath)
                || !ftruncate($temporaryHandle, 0)
                || !$this->writeFully($temporaryHandle, $content)
                || !fflush($temporaryHandle)
            ) {
                throw new RuntimeException('Der Release-Cache konnte nicht vollständig gespeichert werden.');
            }

            /* rename() bleibt im selben Verzeichnis und damit im selben Dateisystem atomar. */
            if (!@rename($temporaryPath, $this->path)) {
                throw new RuntimeException('Der Release-Cache konnte nicht atomar ersetzt werden.');
            }
            $temporaryPath = null;
        } finally {
            fclose($temporaryHandle);
            if (is_string($temporaryPath)) {
                @unlink($temporaryPath);
            }
        }
    }

    /** @param resource $handle Schreibt die bereits unter exklusiver Sperre vorbereiteten Minimaldaten vollständig. */
    private function writeFully($handle, string $content): bool
    {
        $offset = 0;
        $length = strlen($content);
        while ($offset < $length) {
            $written = fwrite($handle, substr($content, $offset));
            if (!is_int($written) || $written < 1) {
                return false;
            }
            $offset += $written;
        }

        return true;
    }

    /** Liest die Cachedatei ausschließlich über einen gegen Austausch geprüften Dateihandle. */
    private function readVerifiedState(): ?ReleaseCheckState
    {
        $handle = $this->openVerifiedFile($this->path, 'rb');
        if ($handle === false) {
            return null;
        }

        try {
            if (!$this->hasPrivateRegularPermissions($handle, $this->path) || !flock($handle, LOCK_SH)) {
                return null;
            }
            $content = stream_get_contents($handle, 65_537);

            return is_string($content) ? $this->decode($content) : null;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * Öffnet nur reguläre Dateien. lstat() und fstat() müssen auf dasselbe
     * Gerät/Inode zeigen; so werden Symlinks und ein Austausch zwischen Prüfen
     * und Öffnen fail-closed behandelt.
     *
     * @return resource|false
     */
    private function openVerifiedFile(string $path, string $mode)
    {
        $before = @lstat($path);
        if ($before !== false && !$this->isRegularStat($before)) {
            return false;
        }

        $handle = @fopen($path, $mode);
        if ($handle === false) {
            return false;
        }
        if (!$this->matchesPathIdentity($handle, $path)) {
            fclose($handle);

            return false;
        }

        return $handle;
    }

    /** @param resource $handle */
    private function hasPrivateRegularPermissions($handle, string $path): bool
    {
        $stat = fstat($handle);

        return is_array($stat)
            && $this->isRegularStat($stat)
            && (($stat['mode'] & 0777) === 0600)
            && $this->matchesPathIdentity($handle, $path);
    }

    /** @param resource $handle */
    private function matchesPathIdentity($handle, string $path): bool
    {
        $handleStat = fstat($handle);
        $pathStat = @lstat($path);

        return is_array($handleStat)
            && is_array($pathStat)
            && $this->isRegularStat($handleStat)
            && $this->isRegularStat($pathStat)
            && $handleStat['dev'] === $pathStat['dev']
            && $handleStat['ino'] === $pathStat['ino'];
    }

    /** @param array<int|string, int> $stat */
    private function isRegularStat(array $stat): bool
    {
        $mode = $stat['mode'] ?? null;

        return is_int($mode) && (($mode & 0170000) === 0100000);
    }

    /** Lehnt Symlinks, besondere Dateien und gelockerte Rechte der öffentlichen Cachedatei ab. */
    private function assertSafePath(): void
    {
        $this->assertSafeDirectory();
        $stat = @lstat($this->path);
        if ($stat !== false && (!$this->isRegularStat($stat) || (($stat['mode'] & 0777) !== 0600))) {
            throw new RuntimeException('Der Release-Cachepfad ist nicht sicher.');
        }
    }

    private function assertSafeDirectory(): void
    {
        $directory = dirname($this->path);
        $stat = @lstat($directory);
        if (!is_array($stat) || (($stat['mode'] & 0170000) !== 0040000)) {
            throw new RuntimeException('Der Release-Cachepfad ist nicht sicher.');
        }

        /* Der System-Temp-Ordner gehört dem Host und darf nicht umkonfiguriert werden. */
        $systemTemp = realpath(sys_get_temp_dir());
        $cacheDirectory = realpath($directory);
        if ($systemTemp === false || $cacheDirectory === false) {
            throw new RuntimeException('Der Release-Cachepfad ist nicht sicher.');
        }
        if ($cacheDirectory !== $systemTemp) {
            if (!@chmod($directory, 0700) || ((fileperms($directory) & 0777) !== 0700)) {
                throw new RuntimeException('Das Release-Cacheverzeichnis konnte nicht geschützt werden.');
            }
        }
    }

    /**
     * Erstellt die Begleit-Sperrdatei mit einer privaten Tempdatei und link().
     * link() überschreibt kein bereits konkurrierend angelegtes Ziel und hält
     * die Dateierstellung deshalb ohne Pfad-Chmod atomar.
     */
    private function ensurePrivateLockFile(): bool
    {
        $lockPath = $this->lockPath();
        if (@lstat($lockPath) !== false) {
            return $this->hasPrivateRegularPath($lockPath);
        }

        $temporaryPath = @tempnam(dirname($lockPath), '.' . basename($lockPath) . '.');
        if (!is_string($temporaryPath)) {
            return false;
        }

        try {
            $temporaryHandle = $this->openVerifiedFile($temporaryPath, 'r+b');
            if ($temporaryHandle === false) {
                return false;
            }
            try {
                if (!$this->hasPrivateRegularPermissions($temporaryHandle, $temporaryPath)) {
                    return false;
                }
            } finally {
                fclose($temporaryHandle);
            }

            if (!@link($temporaryPath, $lockPath)) {
                return $this->hasPrivateRegularPath($lockPath);
            }

            return $this->hasPrivateRegularPath($lockPath);
        } finally {
            @unlink($temporaryPath);
        }
    }

    private function hasPrivateRegularPath(string $path): bool
    {
        $stat = @lstat($path);
        $mode = is_array($stat) ? $stat['mode'] : null;

        return is_array($stat)
            && $this->isRegularStat($stat)
            && is_int($mode)
            && (($mode & 0777) === 0600);
    }

    private function lockPath(): string
    {
        return $this->path . '.lock';
    }
}
