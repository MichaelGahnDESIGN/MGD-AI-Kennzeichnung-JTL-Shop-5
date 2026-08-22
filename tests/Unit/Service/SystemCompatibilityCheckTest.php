<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Service\SystemCompatibilityCheck;

final class SystemCompatibilityCheckTest extends TestCase
{
    #[Test]
    public function mindestversionen_werden_exakt_eingehalten(): void
    {
        $check = new SystemCompatibilityCheck();

        self::assertFalse($check->supports('5.7.1', '8.1.0'));
        self::assertFalse($check->supports('5.7.2', '8.0.30'));
        self::assertTrue($check->supports('5.7.2', '8.1.0'));
        self::assertTrue($check->supports('5.8.0', '8.4.2'));
    }

    #[Test]
    public function php_version_mit_serveranbieter_suffix_wird_korrekt_geprueft(): void
    {
        $check = new SystemCompatibilityCheck();

        self::assertTrue($check->supports('5.7.2', '8.5.3-nmm1'));
        self::assertFalse($check->supports('5.7.2', '8.0.30-nmm1'));
    }

    /** @return iterable<string, array{string, string}> */
    public static function unklareVersionen(): iterable
    {
        yield 'JTL-Präfix' => ['v5.7.2', '8.1.0'];
        yield 'JTL-Vorabversion' => ['5.7.2-rc1', '8.1.0'];
        yield 'führende Null' => ['05.7.2', '8.1.0'];
        yield 'PHP-Präfix' => ['5.7.2', 'PHP 8.1.0'];
        yield 'PHP unvollständig' => ['5.7.2', '8.1'];
    }

    #[Test]
    #[DataProvider('unklareVersionen')]
    public function unklare_versionsangaben_werden_fail_safe_abgelehnt(string $shop, string $php): void
    {
        self::assertFalse((new SystemCompatibilityCheck())->supports($shop, $php));
    }
}
