<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Adapter;

use JsonException;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Port\ReleaseCacheInterface;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Value\CachedRelease;
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

    public function load(): ?CachedRelease
    {
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

    public function save(CachedRelease $release): void
    {
        if (!is_resource($this->handle)) {
            throw new RuntimeException('Der Release-Cache ist nicht exklusiv gesperrt.');
        }

        if (!$this->isValid($release->tag, $release->url, $release->fetchedAt)) {
            throw new RuntimeException('Ungültige Release-Daten werden nicht gespeichert.');
        }

        try {
            $json = json_encode([
                'tag' => $release->tag,
                'url' => $release->url,
                'fetchedAt' => $release->fetchedAt,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException $exception) {
            throw new RuntimeException('Der Release-Cache konnte nicht kodiert werden.', 0, $exception);
        }

        rewind($this->handle);
        if (!ftruncate($this->handle, 0)
            || fwrite($this->handle, $json) !== strlen($json)
            || !fflush($this->handle)
        ) {
            throw new RuntimeException('Der Release-Cache konnte nicht vollständig gespeichert werden.');
        }

        @chmod($this->path, 0600);
    }

    private function decode(string $inhalt): ?CachedRelease
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
            || array_keys($daten) !== ['tag', 'url', 'fetchedAt']
            || !is_string($daten['tag'])
            || !is_string($daten['url'])
            || !is_int($daten['fetchedAt'])
            || !$this->isValid($daten['tag'], $daten['url'], $daten['fetchedAt'])
        ) {
            return null;
        }

        return new CachedRelease($daten['tag'], $daten['url'], $daten['fetchedAt']);
    }

    private function isValid(string $tag, string $url, int $fetchedAt): bool
    {
        return preg_match('/^v(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)$/D', $tag) === 1
            && $url === 'https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/releases/tag/' . $tag
            && $fetchedAt >= 0;
    }
}
