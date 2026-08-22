<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\GitHubReleaseChecker;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Adapter\CurlHttpClient;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Adapter\FileReleaseCache;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Adapter\SystemClock;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Port\ClockInterface;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Port\HttpClientInterface;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Port\ReleaseCacheInterface;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Value\CachedRelease;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Value\HttpRequest;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Value\HttpResponse;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Value\UpdateNotice;
use RuntimeException;

final class GitHubReleaseCheckerTest extends TestCase
{
    #[Test]
    public function deaktivierte_updatehinweise_verursachen_keinen_netzwerkzugriff(): void
    {
        [$checker, $http, $cache] = $this->checker(now: 1_700_000_000);

        self::assertNull($checker->check(false, '1.0.0'));
        self::assertSame(0, $http->calls);
        self::assertSame(0, $cache->loadCalls);
    }

    #[Test]
    public function gueltiges_neueres_release_verwendet_einen_festen_datensparsamen_request(): void
    {
        [$checker, $http, $cache] = $this->checker(now: 1_700_000_000);
        $http->response = $this->releaseResponse('v1.2.0');

        $notice = $checker->check(true, '1.0.0');

        self::assertInstanceOf(UpdateNotice::class, $notice);
        self::assertSame('v1.2.0', $notice->tag);
        self::assertSame(
            'https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/releases/tag/v1.2.0',
            $notice->url,
        );
        self::assertSame(1, $http->calls);
        self::assertSame(1, $cache->acquireCalls);
        self::assertSame(1, $cache->releaseCalls);
        self::assertInstanceOf(HttpRequest::class, $http->lastRequest);
        self::assertSame(
            'https://api.github.com/repos/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/releases/latest',
            $http->lastRequest->url,
        );
        self::assertSame('application/vnd.github+json', $http->lastRequest->headers['Accept'] ?? null);
        self::assertSame('MGD-AI-Kennzeichnung-JTL-Shop-5/1.1.0', $http->lastRequest->headers['User-Agent'] ?? null);
        self::assertSame(2, $http->lastRequest->connectTimeoutSeconds);
        self::assertSame(5, $http->lastRequest->totalTimeoutSeconds);
        self::assertTrue($http->lastRequest->verifyTls);
        self::assertFalse($http->lastRequest->followRedirects);
        self::assertSame(65_536, $http->lastRequest->maximumResponseBytes);
        self::assertInstanceOf(CachedRelease::class, $cache->stored);
        self::assertSame(1_700_000_000, $cache->stored->fetchedAt);
    }

    #[Test]
    public function frischer_cache_verhindert_weitere_anfragen_und_wird_semantisch_verglichen(): void
    {
        [$checker, $http, $cache] = $this->checker(now: 1_700_043_199);
        $cache->stored = new CachedRelease(
            'v1.3.0',
            'https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/releases/tag/v1.3.0',
            1_700_000_000,
        );

        self::assertNotNull($checker->check(true, '1.2.9'));
        self::assertNull($checker->check(true, '1.3.0'));
        self::assertSame(0, $http->calls);
    }

    #[Test]
    public function exakt_nach_zwoelf_stunden_darf_ein_neuer_abruf_erfolgen(): void
    {
        [$checker, $http, $cache] = $this->checker(now: 1_700_043_200);
        $cache->stored = new CachedRelease(
            'v1.1.0',
            'https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/releases/tag/v1.1.0',
            1_700_000_000,
        );
        $http->response = $this->releaseResponse('v1.2.0');

        self::assertNotNull($checker->check(true, '1.0.0'));
        self::assertSame(1, $http->calls);
        self::assertSame('v1.2.0', $cache->stored->tag);
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function manipulierteReleases(): iterable
    {
        $basis = [
            'tag_name' => 'v9.0.0',
            'html_url' => 'https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/releases/tag/v9.0.0',
            'draft' => false,
            'prerelease' => false,
        ];

        yield 'Pfadmanipulation im Tag' => [array_replace($basis, ['tag_name' => '../v9'])];
        yield 'führende Null' => [array_replace($basis, ['tag_name' => 'v01.0.0'])];
        yield 'Vorabversion' => [array_replace($basis, ['tag_name' => 'v9.0.0-rc1'])];
        yield 'Draft' => [array_replace($basis, ['draft' => true])];
        yield 'Prerelease' => [array_replace($basis, ['prerelease' => true])];
        yield 'HTTP statt HTTPS' => [array_replace($basis, ['html_url' => 'http://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/releases/tag/v9.0.0'])];
        yield 'fremder Host' => [array_replace($basis, ['html_url' => 'https://evil.example/releases/tag/v9.0.0'])];
        yield 'GitHub Benutzerinformation' => [array_replace($basis, ['html_url' => 'https://evil@github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/releases/tag/v9.0.0'])];
        yield 'abweichender URL-Tag' => [array_replace($basis, ['html_url' => 'https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/releases/tag/v8.0.0'])];
        yield 'Query' => [array_replace($basis, ['html_url' => 'https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/releases/tag/v9.0.0?x=1'])];
        yield 'Fragment' => [array_replace($basis, ['html_url' => 'https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/releases/tag/v9.0.0#x'])];
    }

    /** @param array<string, mixed> $release */
    #[Test]
    #[DataProvider('manipulierteReleases')]
    public function manipulierte_release_daten_werden_verworfen_und_nicht_gecached(array $release): void
    {
        [$checker, $http, $cache] = $this->checker(now: 1_700_000_000);
        $http->response = new HttpResponse(200, json_encode($release, JSON_THROW_ON_ERROR), []);

        self::assertNull($checker->check(true, '1.0.0'));
        self::assertNull($cache->stored);
    }

    #[Test]
    public function fehler_zu_grosse_antworten_und_ungueltige_aktuelle_version_bleiben_generisch(): void
    {
        foreach ([
            new HttpResponse(429, '{"token":"geheim"}', ['X-RateLimit-Remaining' => '0']),
            new HttpResponse(302, '', ['Location' => 'https://evil.example']),
            new HttpResponse(200, str_repeat('x', 65_537), []),
            new HttpResponse(200, '{kein json', []),
        ] as $antwort) {
            [$checker, $http, $cache] = $this->checker(now: 1_700_000_000);
            $http->response = $antwort;

            self::assertNull($checker->check(true, '1.0.0'));
            self::assertNull($cache->stored);
            self::assertSame(1, $http->calls, 'Es darf keine Wiederholungsschleife geben.');
        }

        [$checker, $http] = $this->checker(now: 1_700_000_000);
        $http->response = $this->releaseResponse('v2.0.0');
        self::assertNull($checker->check(true, 'v1.0.0'));
    }

    #[Test]
    public function transportfehler_vergiften_einen_abgelaufenen_erfolgs_cache_nicht(): void
    {
        [$checker, $http, $cache] = $this->checker(now: 1_700_043_200);
        $vorher = new CachedRelease(
            'v1.1.0',
            'https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/releases/tag/v1.1.0',
            1_700_000_000,
        );
        $cache->stored = $vorher;
        $http->exception = new RuntimeException('Antwort enthält token=geheim');

        self::assertNull($checker->check(true, '1.0.0'));
        self::assertSame($vorher, $cache->stored);
        self::assertSame(1, $http->calls);
    }

    #[Test]
    public function is_update_akzeptiert_nur_exakt_neuere_sichere_release_daten(): void
    {
        self::assertTrue(GitHubReleaseChecker::isUpdate([
            'tag_name' => 'v1.10.0',
            'html_url' => 'https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/releases/tag/v1.10.0',
            'draft' => false,
            'prerelease' => false,
        ], '1.9.9'));
        self::assertFalse(GitHubReleaseChecker::isUpdate([
            'tag_name' => 'v1.0.0',
            'html_url' => 'https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/releases/tag/v1.0.0',
            'draft' => false,
            'prerelease' => false,
        ], '1.0.0'));
    }

    #[Test]
    public function dateicache_speichert_nur_gepruefte_minimaldaten_und_sperrt_exklusiv(): void
    {
        self::assertTrue(class_exists(FileReleaseCache::class));
        $pfad = tempnam(sys_get_temp_dir(), 'mgd-release-cache-');
        self::assertIsString($pfad);

        try {
            $cache = new FileReleaseCache($pfad);
            self::assertTrue($cache->acquire());
            self::assertFalse((new FileReleaseCache($pfad))->acquire());
            $cache->save(new CachedRelease(
                'v2.0.0',
                'https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/releases/tag/v2.0.0',
                1_700_000_000,
            ));
            $cache->release();

            $geladen = (new FileReleaseCache($pfad))->load();
            self::assertInstanceOf(CachedRelease::class, $geladen);
            self::assertSame('v2.0.0', $geladen->tag);
            self::assertSame(1_700_000_000, $geladen->fetchedAt);
            self::assertSame(0600, fileperms($pfad) & 0777);
        } finally {
            if (is_file($pfad)) {
                unlink($pfad);
            }
        }
    }

    #[Test]
    public function laufzeitadapter_verweigert_unsichere_transportparameter(): void
    {
        self::assertTrue(class_exists(CurlHttpClient::class));
        self::assertTrue(class_exists(SystemClock::class));
        self::assertGreaterThan(0, (new SystemClock())->now());

        $this->expectException(RuntimeException::class);
        (new CurlHttpClient())->send(new HttpRequest(
            url: 'http://evil.example',
            headers: [],
            connectTimeoutSeconds: 2,
            totalTimeoutSeconds: 5,
            verifyTls: false,
            followRedirects: true,
            maximumResponseBytes: 65_536,
        ));
    }

    /**
     * @return array{GitHubReleaseChecker, RecordingHttpClient, MemoryReleaseCache}
     */
    private function checker(int $now): array
    {
        $http = new RecordingHttpClient();
        $cache = new MemoryReleaseCache();
        $clock = new FixedClock($now);

        return [new GitHubReleaseChecker($http, $cache, $clock), $http, $cache];
    }

    private function releaseResponse(string $tag): HttpResponse
    {
        return new HttpResponse(200, json_encode([
            'tag_name' => $tag,
            'html_url' => 'https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/releases/tag/' . $tag,
            'draft' => false,
            'prerelease' => false,
        ], JSON_THROW_ON_ERROR), []);
    }
}

final class FixedClock implements ClockInterface
{
    public function __construct(private readonly int $timestamp) {}

    public function now(): int
    {
        return $this->timestamp;
    }
}

final class RecordingHttpClient implements HttpClientInterface
{
    public int $calls = 0;
    public ?HttpRequest $lastRequest = null;
    public ?HttpResponse $response = null;
    public ?RuntimeException $exception = null;

    public function send(HttpRequest $request): HttpResponse
    {
        ++$this->calls;
        $this->lastRequest = $request;

        if ($this->exception !== null) {
            throw $this->exception;
        }

        return $this->response ?? new HttpResponse(500, '', []);
    }
}

final class MemoryReleaseCache implements ReleaseCacheInterface
{
    public int $loadCalls = 0;
    public int $acquireCalls = 0;
    public int $releaseCalls = 0;
    public ?CachedRelease $stored = null;

    public function acquire(): bool
    {
        ++$this->acquireCalls;

        return true;
    }

    public function release(): void
    {
        ++$this->releaseCalls;
    }

    public function load(): ?CachedRelease
    {
        ++$this->loadCalls;

        return $this->stored;
    }

    public function save(CachedRelease $release): void
    {
        $this->stored = $release;
    }
}
