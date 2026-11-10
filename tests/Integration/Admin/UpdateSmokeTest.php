<?php

declare(strict_types=1);

namespace Tests\Integration\Admin;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Führt den Smoke Test des Updates Bereichs in einem eigenen PHP Prozess aus. */
final class UpdateSmokeTest extends TestCase
{
    #[Test]
    public function updates_bereich_smoke_test_laeuft_ohne_fehler(): void
    {
        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, dirname(__DIR__, 2) . '/update-smoke.php'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        self::assertIsResource($process);
        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        self::assertSame(0, proc_close($process), $stdout . $stderr);
        self::assertStringNotContainsString('FAIL', $stdout);
    }
}
