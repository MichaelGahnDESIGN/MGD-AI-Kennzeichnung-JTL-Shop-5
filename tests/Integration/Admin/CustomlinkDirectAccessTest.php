<?php

declare(strict_types=1);

namespace Tests\Integration\Admin;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Prüft den Direktschutz außerhalb des JTL-PluginControllers in isolierten PHP-Prozessen. */
final class CustomlinkDirectAccessTest extends TestCase
{
    #[Test]
    public function jeder_customlink_weist_den_direkten_aufruf_ohne_jtl_kontext_mit_403_ab(): void
    {
        foreach (['assets.php', 'philosophy.php', 'display.php', 'impressum.php'] as $file) {
            $result = $this->fuehreDirektenAufrufAus($file);

            self::assertSame(0, $result['exitCode'], $file);
            self::assertSame(403, $result['status'], $file);
            self::assertNotSame('', $result['output'], $file);
            self::assertStringContainsString('nur im JTL-Administrationsbereich verfügbar', $result['output'], $file);
            self::assertStringNotContainsString('Fatal error', $result['output'], $file);
            self::assertFalse($result['shopClassLoaded'], $file);
            self::assertFalse($result['databaseInterfaceLoaded'], $file);
            self::assertSame(PHP_SESSION_NONE, $result['sessionStatus'], $file);
        }
    }

    /**
     * @return array{databaseInterfaceLoaded: bool, exitCode: int, output: string, sessionStatus: int, shopClassLoaded: bool, status: int}
     */
    private function fuehreDirektenAufrufAus(string $file): array
    {
        $script = <<<'PHP'
ob_start();
include $argv[1];
$output = (string) ob_get_clean();
echo json_encode([
    'output' => $output,
    'sessionStatus' => session_status(),
    'shopClassLoaded' => class_exists('JTL\\Shop', false),
    'databaseInterfaceLoaded' => interface_exists('JTL\\DB\\DbInterface', false),
    'status' => http_response_code(),
], JSON_THROW_ON_ERROR);
PHP;
        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, '-d', 'display_errors=0', '-r', $script, dirname(__DIR__, 3) . '/plugin/MGD_AI_Kennzeichnung/adminmenu/' . $file],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        self::assertIsResource($process);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        self::assertSame('', $stderr, $file);
        /** @var array{databaseInterfaceLoaded: bool, output: string, sessionStatus: int, shopClassLoaded: bool, status: int} $result */
        $result = json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);

        return ['exitCode' => $exitCode] + $result;
    }
}
