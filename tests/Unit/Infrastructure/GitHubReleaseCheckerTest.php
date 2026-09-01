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
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Value\ReleaseCheckState;
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
        self::assertSame(0, $cache->saveCalls);
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
        self::assertSame([
            'Accept' => 'application/vnd.github+json',
            'User-Agent' => 'MGD-AI-Kennzeichnung-JTL-Shop-5/1.3.3',
        ], $http->lastRequest->headers);
        self::assertSame(2, $http->lastRequest->connectTimeoutSeconds);
        self::assertSame(5, $http->lastRequest->totalTimeoutSeconds);
        self::assertTrue($http->lastRequest->verifyTls);
        self::assertFalse($http->lastRequest->followRedirects);
        self::assertSame(65_536, $http->lastRequest->maximumResponseBytes);
        self::assertInstanceOf(ReleaseCheckState::class, $cache->stored);
        self::assertSame(1_700_000_000, $cache->stored->attemptedAt);
        self::assertInstanceOf(CachedRelease::class, $cache->stored->release);
        self::assertSame(1_700_000_000, $cache->stored->release->fetchedAt);
    }

    #[Test]
    public function frischer_cache_verhindert_weitere_anfragen_und_wird_semantisch_verglichen(): void
    {
        [$checker, $http, $cache] = $this->checker(now: 1_700_043_199);
        $cache->stored = new ReleaseCheckState(1_700_000_000, new CachedRelease(
            'v1.3.0',
            'https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/releases/tag/v1.3.0',
            1_700_000_000,
        ));

        self::assertNotNull($checker->check(true, '1.2.9'));
        self::assertNull($checker->check(true, '1.3.0'));
        self::assertSame(0, $http->calls);
    }

    #[Test]
    public function exakt_nach_zwoelf_stunden_darf_ein_neuer_abruf_erfolgen(): void
    {
        [$checker, $http, $cache] = $this->checker(now: 1_700_043_200);
        $cache->stored = new ReleaseCheckState(1_700_000_000, new CachedRelease(
            'v1.1.0',
            'https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/releases/tag/v1.1.0',
            1_700_000_000,
        ));
        $http->response = $this->releaseResponse('v1.2.0');

        self::assertNotNull($checker->check(true, '1.0.0'));
        self::assertSame(1, $http->calls);
        self::assertSame('v1.2.0', $cache->stored->release?->tag);
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
        self::assertInstanceOf(ReleaseCheckState::class, $cache->stored);
        self::assertSame(1_700_000_000, $cache->stored->attemptedAt);
        self::assertNull($cache->stored->release);
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
            self::assertInstanceOf(ReleaseCheckState::class, $cache->stored);
            self::assertSame(1_700_000_000, $cache->stored->attemptedAt);
            self::assertNull($cache->stored->release);
            self::assertSame(1, $http->calls, 'Es darf keine Wiederholungsschleife geben.');
        }

        [$checker, $http] = $this->checker(now: 1_700_000_000);
        $http->response = $this->releaseResponse('v2.0.0');
        self::assertNull($checker->check(true, 'v1.0.0'));
    }

    /** @return iterable<string, array{HttpResponse|null, RuntimeException|null}> */
    public static function negativeAntworten(): iterable
    {
        yield 'nicht gefunden' => [new HttpResponse(404, '', []), null];
        yield 'rate limit' => [new HttpResponse(429, '', []), null];
        yield 'serverfehler' => [new HttpResponse(500, '', []), null];
        yield 'ungültiges JSON' => [new HttpResponse(200, '{ungültig', []), null];
        yield 'zu große Antwort' => [new HttpResponse(200, str_repeat('x', 65_537), []), null];
        yield 'Transportfehler' => [null, new RuntimeException('Technischer Transportfehler.')];
    }

    #[Test]
    #[DataProvider('negativeAntworten')]
    public function fehlgeschlagener_abruf_wird_fuer_zwoelf_stunden_ohne_release_gecached(
        ?HttpResponse $response,
        ?RuntimeException $exception,
    ): void {
        [$checker, $http, $cache] = $this->checker(now: 1_700_000_000);
        $http->response = $response;
        $http->exception = $exception;

        $first = $checker->check(true, '1.3.0');
        $second = $checker->check(true, '1.3.0');

        self::assertNull($first);
        self::assertNull($second);
        self::assertSame(1, $http->calls);
        self::assertInstanceOf(ReleaseCheckState::class, $cache->stored);
        self::assertSame(1_700_000_000, $cache->stored->attemptedAt);
        self::assertNull($cache->stored->release);
    }

    #[Test]
    public function negativer_cache_bleibt_bis_exakt_vor_zwoelf_stunden_frisch_und_laueft_dann_ab(): void
    {
        $state = new ReleaseCheckState(1_700_000_000, null);

        [$vorAblauf, $httpVorAblauf, $cacheVorAblauf] = $this->checker(now: 1_700_043_199);
        $cacheVorAblauf->stored = $state;
        self::assertNull($vorAblauf->check(true, '1.3.0'));
        self::assertSame(0, $httpVorAblauf->calls);

        [$abAblauf, $httpAbAblauf, $cacheAbAblauf] = $this->checker(now: 1_700_043_200);
        $cacheAbAblauf->stored = $state;
        $httpAbAblauf->response = $this->releaseResponse('v1.3.1');
        self::assertNotNull($abAblauf->check(true, '1.3.0'));
        self::assertSame(1, $httpAbAblauf->calls);
    }

    #[Test]
    public function ein_cache_schreibfehler_verhindert_weder_den_hinweis_noch_den_adminabruf(): void
    {
        [$checker, $http, $cache] = $this->checker(now: 1_700_000_000);
        $http->response = $this->releaseResponse('v1.3.1');
        $cache->throwOnSave = true;

        self::assertInstanceOf(UpdateNotice::class, $checker->check(true, '1.3.0'));
        self::assertSame(1, $http->calls);
        self::assertSame(1, $cache->saveCalls);
    }

    #[Test]
    public function transportfehler_vergiften_einen_abgelaufenen_erfolgs_cache_nicht(): void
    {
        [$checker, $http, $cache] = $this->checker(now: 1_700_043_200);
        $vorher = new ReleaseCheckState(1_700_000_000, new CachedRelease(
            'v1.1.0',
            'https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/releases/tag/v1.1.0',
            1_700_000_000,
        ));
        $cache->stored = $vorher;
        $http->exception = new RuntimeException('Antwort enthält token=geheim');

        self::assertNull($checker->check(true, '1.0.0'));
        self::assertSame(1_700_043_200, $cache->stored->attemptedAt);
        self::assertNull($cache->stored->release);
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
            $cache->save(new ReleaseCheckState(1_700_000_000, new CachedRelease(
                'v2.0.0',
                'https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/releases/tag/v2.0.0',
                1_700_000_000,
            )));
            $cache->release();

            $geladen = (new FileReleaseCache($pfad))->load();
            self::assertInstanceOf(ReleaseCheckState::class, $geladen);
            self::assertSame('v2.0.0', $geladen->release?->tag);
            self::assertSame(1_700_000_000, $geladen->attemptedAt);
            self::assertSame(0600, fileperms($pfad) & 0777);
        } finally {
            if (is_file($pfad . '.lock')) {
                unlink($pfad . '.lock');
            }
            if (is_file($pfad)) {
                unlink($pfad);
            }
        }
    }

    #[Test]
    public function dateicache_speichert_exakt_den_negativen_und_positiven_pruefzustand_und_verwirft_abweichungen(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'mgd-release-cache-');
        self::assertIsString($path);

        try {
            $cache = new FileReleaseCache($path);
            self::assertTrue($cache->acquire());
            $cache->save(new ReleaseCheckState(1_700_000_000, null));
            $cache->release();
            self::assertSame(
                '{"attemptedAt":1700000000,"release":null}',
                file_get_contents($path),
            );
            self::assertSame(1_700_000_000, (new FileReleaseCache($path))->load()?->attemptedAt);

            file_put_contents($path, '{"attemptedAt":1700000000,"release":null,"unknown":true}');
            self::assertNull((new FileReleaseCache($path))->load());
            file_put_contents($path, '{"attemptedAt":1700000000,"release":{"tag":"v1.2.2","url":"https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/releases/tag/v1.2.2","fetchedAt":1700000001}}');
            self::assertNull((new FileReleaseCache($path))->load());
        } finally {
            if (is_file($path . '.lock')) {
                unlink($path . '.lock');
            }
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    #[Test]
    public function dateicache_haertet_ein_eigenes_cacheverzeichnis_und_lehnt_symbolische_links_ab(): void
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mgd-release-cache-test-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($directory, 0777));
        $path = $directory . DIRECTORY_SEPARATOR . 'release.json';

        try {
            $cache = new FileReleaseCache($path);
            self::assertTrue($cache->acquire());
            self::assertSame(0700, fileperms($directory) & 0777);
            $cache->release();

            $link = $directory . DIRECTORY_SEPARATOR . 'release-link.json';
            self::assertTrue(symlink($path, $link));
            $this->expectException(RuntimeException::class);
            (new FileReleaseCache($link))->acquire();
        } finally {
            if (is_file($path . '.lock')) {
                unlink($path . '.lock');
            }
            if (is_file($path)) {
                unlink($path);
            }
            if (is_link($directory . DIRECTORY_SEPARATOR . 'release-link.json')) {
                unlink($directory . DIRECTORY_SEPARATOR . 'release-link.json');
            }
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }

    #[Test]
    public function dateicache_verwirft_nach_chmod_veraltete_php_statdaten_vor_der_rechtepruefung(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../../plugin/MGD_AI_Kennzeichnung/Infrastructure/Update/Adapter/FileReleaseCache.php',
        );
        self::assertIsString($source);

        self::assertMatchesRegularExpression(
            '~chmod\(\$directory, 0700\).*?clearstatcache\(true, \$directory\).*?fileperms\(\$directory\)~s',
            $source,
            'PHP 8.1 darf nach chmod() keine zwischengespeicherten Verzeichnisrechte prüfen.',
        );
    }

    #[Test]
    public function dateicache_bewahrt_den_alten_zustand_wenn_das_atomare_schreiben_fehlsschlaegt(): void
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mgd-release-atomic-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($directory, 0700));
        $path = $directory . DIRECTORY_SEPARATOR . 'release.json';
        $oldState = new ReleaseCheckState(1_700_000_000, null);
        $newState = new ReleaseCheckState(1_700_043_200, null);
        $cache = new FileReleaseCache($path);

        try {
            self::assertTrue($cache->acquire());
            $cache->save($oldState);
            $cache->release();

            self::assertTrue($cache->acquire());
            self::assertTrue(chmod($directory, 0500));
            try {
                $cache->save($newState);
                self::fail('Der atomare Schreibversuch muss bei einem nicht beschreibbaren Verzeichnis fehlschlagen.');
            } catch (RuntimeException) {
                // Der alte Inhalt muss trotz fehlgeschlagenem neuen Versuch lesbar bleiben.
            } finally {
                chmod($directory, 0700);
                $cache->release();
            }

            self::assertSame($oldState->attemptedAt, (new FileReleaseCache($path))->load()?->attemptedAt);
        } finally {
            chmod($directory, 0700);
            if (is_file($path . '.lock')) {
                unlink($path . '.lock');
            }
            if (is_file($path)) {
                unlink($path);
            }
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }

    #[Test]
    public function dateicache_verwirft_bestehende_gruppen_oder_weltlesbare_dateien_fail_closed(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'mgd-release-permissions-');
        self::assertIsString($path);

        try {
            file_put_contents($path, '{"attemptedAt":1700000000,"release":null}');
            self::assertTrue(chmod($path, 0644));

            self::assertNull((new FileReleaseCache($path))->load());
            $this->expectException(RuntimeException::class);
            (new FileReleaseCache($path))->acquire();
        } finally {
            chmod($path, 0600);
            if (is_file($path . '.lock')) {
                unlink($path . '.lock');
            }
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    #[Test]
    public function dateicache_verwirft_einen_ersetzten_sperrdateipfad_vor_dem_schreiben(): void
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mgd-release-lock-replace-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($directory, 0700));
        $path = $directory . DIRECTORY_SEPARATOR . 'release.json';
        $lockPath = $path . '.lock';
        $cache = new FileReleaseCache($path);

        try {
            self::assertTrue($cache->acquire());
            self::assertTrue(unlink($lockPath));

            $this->expectException(RuntimeException::class);
            $cache->save(new ReleaseCheckState(1_700_000_000, null));
        } finally {
            $cache->release();
            if (is_file($lockPath)) {
                unlink($lockPath);
            }
            if (is_file($path)) {
                unlink($path);
            }
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }

    #[Test]
    public function dateicache_laden_kehre_bei_konkurrierender_exklusivsperre_sofort_fail_closed_zurueck(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'mgd-release-nonblocking-');
        self::assertIsString($path);
        $cache = new FileReleaseCache($path);
        $process = null;

        try {
            self::assertTrue($cache->acquire());
            $cache->save(new ReleaseCheckState(1_700_000_000, null));
            $cache->release();

            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];
            $process = proc_open([
                PHP_BINARY,
                '-r',
                '$handle = fopen($argv[1], "c+b"); flock($handle, LOCK_EX); fwrite(STDOUT, "ready\\n"); sleep(2);',
                $path,
            ], $descriptors, $pipes);
            self::assertIsResource($process);
            self::assertSame("ready\n", fgets($pipes[1]));

            $startedAt = microtime(true);
            $reader = new FileReleaseCache($path);
            self::assertTrue($reader->acquire());
            self::assertNull($reader->load());
            $reader->release();
            self::assertLessThan(0.5, microtime(true) - $startedAt);
        } finally {
            if (is_resource($process)) {
                proc_close($process);
            }
            if (is_file($path . '.lock')) {
                unlink($path . '.lock');
            }
            if (is_file($path)) {
                unlink($path);
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
    public int $saveCalls = 0;
    public bool $throwOnSave = false;
    public ?ReleaseCheckState $stored = null;

    public function acquire(): bool
    {
        ++$this->acquireCalls;

        return true;
    }

    public function release(): void
    {
        ++$this->releaseCalls;
    }

    public function load(): ?ReleaseCheckState
    {
        ++$this->loadCalls;

        return $this->stored;
    }

    public function save(ReleaseCheckState $state): void
    {
        ++$this->saveCalls;
        if ($this->throwOnSave) {
            throw new RuntimeException('Lokaler Cachefehler.');
        }
        $this->stored = $state;
    }
}
