<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update;

use JsonException;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Port\ClockInterface;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Port\HttpClientInterface;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Port\ReleaseCacheInterface;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Port\UpdateCheckerInterface;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Value\CachedRelease;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Value\HttpRequest;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Value\ReleaseCheckState;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Value\UpdateNotice;
use Throwable;

/**
 * Prüft nach ausdrücklicher Einwilligung auf ein neueres GitHub-Release.
 *
 * Der Dienst lädt oder installiert niemals Dateien. Er akzeptiert nur eine
 * streng begrenzte JSON-Antwort vom fest eingebauten API-Endpunkt und gibt als
 * Ergebnis ausschließlich einen geprüften Tag sowie dessen feste Release-URL
 * zurück. Transport, Uhr und lokaler Cache sind getrennte Ports.
 */
final class GitHubReleaseChecker implements UpdateCheckerInterface
{
    private const ENDPOINT = 'https://api.github.com/repos/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/releases/latest';
    private const RELEASE_URL_PREFIX = 'https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/releases/tag/';
    private const CACHE_SECONDS = 43_200;
    private const MAXIMUM_RESPONSE_BYTES = 65_536;
    private const VERSION_PATTERN = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)$/D';
    private const TAG_PATTERN = '/^v(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)$/D';

    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly ReleaseCacheInterface $cache,
        private readonly ClockInterface $clock,
    ) {}

    /**
     * Liefert einen sicheren Hinweis oder null; Fehlerdetails werden nicht nach
     * außen weitergereicht und eine Rate-Limit-Antwort wird nicht wiederholt.
     */
    public function check(bool $enabled, string $currentVersion): ?UpdateNotice
    {
        if (!$enabled || preg_match(self::VERSION_PATTERN, $currentVersion) !== 1) {
            return null;
        }

        try {
            if (!$this->cache->acquire()) {
                return null;
            }
        } catch (Throwable) {
            return null;
        }

        try {
            return $this->checkExclusively($currentVersion);
        } catch (Throwable) {
            return null;
        } finally {
            try {
                $this->cache->release();
            } catch (Throwable) {
                // Ein Freigabefehler darf weder sensible Details noch den Shopbetrieb beeinflussen.
            }
        }
    }

    /** Führt Cacheprüfung und höchstens einen Netzwerkabruf unter derselben Sperre aus. */
    private function checkExclusively(string $currentVersion): ?UpdateNotice
    {
        $now = $this->clock->now();
        if ($now < 0) {
            return null;
        }

        $cached = $this->cache->load();

        if ($cached !== null
            && $cached->attemptedAt <= $now
            && $now - $cached->attemptedAt < self::CACHE_SECONDS
        ) {
            return $cached->release === null ? null : $this->noticeWhenNewer($cached->release, $currentVersion);
        }

        $release = null;
        try {
            $release = $this->fetchValidatedRelease($now);

            return $release === null ? null : $this->noticeWhenNewer($release, $currentVersion);
        } finally {
            try {
                $this->cache->save(new ReleaseCheckState($now, $release));
            } catch (Throwable) {
                // Ein lokaler Cachefehler darf den geschützten Adminbereich nicht beeinträchtigen.
            }
        }
    }

    /** Ruft ausschließlich den fest eingebauten GitHub-Endpunkt ab und validiert die Minimaldaten. */
    private function fetchValidatedRelease(int $now): ?CachedRelease
    {
        $response = $this->http->send(new HttpRequest(
            url: self::ENDPOINT,
            headers: [
                'Accept' => 'application/vnd.github+json',
                'User-Agent' => 'MGD-AI-Kennzeichnung-JTL-Shop-5/1.3.4',
            ],
            connectTimeoutSeconds: 2,
            totalTimeoutSeconds: 5,
            verifyTls: true,
            followRedirects: false,
            maximumResponseBytes: self::MAXIMUM_RESPONSE_BYTES,
        ));

        if ($response->statusCode !== 200 || strlen($response->body) > self::MAXIMUM_RESPONSE_BYTES) {
            return null;
        }

        try {
            $daten = json_decode($response->body, true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (!is_array($daten)) {
            return null;
        }

        $objektDaten = self::stringKeyedArray($daten);
        if ($objektDaten === null) {
            return null;
        }

        $release = self::validatedRelease($objektDaten, $now);
        if ($release === null) {
            return null;
        }

        return $release;
    }

    /**
     * JSON-Objekte dürfen an dieser Grenze nur Zeichenketten als Schlüssel
     * besitzen. Diese Prüfung macht den anschließend verwendeten Datentyp auch
     * für die statische Analyse eindeutig.
     *
     * @param array<array-key, mixed> $daten
     *
     * @return array<string, mixed>|null
     */
    private static function stringKeyedArray(array $daten): ?array
    {
        $objektDaten = [];
        foreach (array_keys($daten) as $schluessel) {
            if (!is_string($schluessel)) {
                return null;
            }

            $objektDaten[$schluessel] = $daten[$schluessel];
        }

        return $objektDaten;
    }

    /**
     * Kleine, nebenwirkungsfreie Vertragsprüfung für bereits dekodierte Daten.
     *
     * @param array<string, mixed> $releaseData
     */
    public static function isUpdate(array $releaseData, string $currentVersion): bool
    {
        if (preg_match(self::VERSION_PATTERN, $currentVersion) !== 1) {
            return false;
        }

        $release = self::validatedRelease($releaseData, 0);

        return $release !== null
            && version_compare(substr($release->tag, 1), $currentVersion, '>');
    }

    /** @param array<string, mixed> $daten */
    private static function validatedRelease(array $daten, int $fetchedAt): ?CachedRelease
    {
        $tag = $daten['tag_name'] ?? null;
        $url = $daten['html_url'] ?? null;
        $draft = $daten['draft'] ?? null;
        $prerelease = $daten['prerelease'] ?? null;

        if (!is_string($tag) || preg_match(self::TAG_PATTERN, $tag) !== 1
            || !is_string($url)
            || $url !== self::RELEASE_URL_PREFIX . $tag
            || $draft !== false
            || $prerelease !== false
        ) {
            return null;
        }

        return new CachedRelease($tag, $url, $fetchedAt);
    }

    private function noticeWhenNewer(CachedRelease $release, string $currentVersion): ?UpdateNotice
    {
        if (!version_compare(substr($release->tag, 1), $currentVersion, '>')) {
            return null;
        }

        return new UpdateNotice($release->tag, $release->url);
    }
}
