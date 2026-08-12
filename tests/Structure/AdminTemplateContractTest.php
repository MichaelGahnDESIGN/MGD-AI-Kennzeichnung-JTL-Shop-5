<?php

declare(strict_types=1);

namespace Tests\Structure;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AdminTemplateContractTest extends TestCase
{
    #[Test]
    public function getrennte_admin_templates_sind_escaped_post_csrf_und_barrierearm(): void
    {
        $root = dirname(__DIR__, 2) . '/plugin/MGD_AI_Kennzeichnung';
        $files = [
            $root . '/adminmenu/templates/assets-list.php',
            $root . '/adminmenu/templates/asset-detail.php',
            $root . '/adminmenu/templates/bulk-preview.php',
            $root . '/adminmenu/templates/messages.php',
        ];

        foreach ($files as $file) {
            self::assertFileExists($file);
            $source = (string) file_get_contents($file);
            self::assertStringContainsString('htmlspecialchars', $source);
            self::assertDoesNotMatchRegularExpression('/<script|<style|\$_(?:GET|POST|REQUEST)|echo\s+\$(?!escaped)/i', $source);
        }

        $combined = implode("\n", array_map(static fn(string $file): string => (string) file_get_contents($file), $files));
        self::assertStringContainsString('method="post"', strtolower($combined));
        self::assertStringContainsString('name="csrf_token"', $combined);
        self::assertStringContainsString('<th scope="col"', $combined);
        self::assertStringContainsString('aria-label', $combined);
        self::assertStringContainsString('confirmation_token', $combined);
        self::assertStringContainsString('Betroffene Datensätze', $combined);
    }

    #[Test]
    public function bootstrap_bleibt_duenn_und_mutiert_nicht_per_get(): void
    {
        $file = dirname(__DIR__, 2) . '/plugin/MGD_AI_Kennzeichnung/adminmenu/assets.php';
        self::assertFileExists($file);
        $source = (string) file_get_contents($file);

        self::assertStringContainsString('defined(', $source);
        self::assertStringNotContainsString('$_GET', $source);
        self::assertStringNotContainsString('UPDATE ', strtoupper($source));
        self::assertStringNotContainsString('DELETE ', strtoupper($source));
    }
}
