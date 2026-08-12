<?php

declare(strict_types=1);

namespace Tests\Structure;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Verhindert, dass ein unsicherer alternativer Bestätigungspfad zurückkehrt. */
final class AdminConfirmationCompositionContractTest extends TestCase
{
    #[Test]
    public function runtime_verwendet_ausschliesslich_den_persistenten_datenbank_claim(): void
    {
        $root = dirname(__DIR__, 2);
        $obsoleteClass = 'Jtl' . 'LockedConfirmationPort';
        $obsolete = $root . '/plugin/MGD_AI_Kennzeichnung/Admin/Adapter/' . $obsoleteClass . '.php';
        $factoryPath = $root . '/plugin/MGD_AI_Kennzeichnung/Admin/Factory/AdminRuntimeFactory.php';

        self::assertFileDoesNotExist($obsolete);
        $factory = file_get_contents($factoryPath);
        self::assertIsString($factory);
        self::assertSame(2, substr_count($factory, 'DatabaseClaimingConfirmationPort'));
        self::assertStringNotContainsString($obsoleteClass, $factory);
        self::assertStringContainsString('new ConfirmationClaimRepository($db)', $factory);
    }
}
