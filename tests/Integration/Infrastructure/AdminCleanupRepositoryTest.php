<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

require_once __DIR__ . '/../../Stubs/JtlDatabaseStubs.php';

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\AssetNotFoundException;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\SchemaOwnershipGuard;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\UsageRepository;
use RuntimeException;
use Tests\Support\TransactionalDatabaseFake;

final class AdminCleanupRepositoryTest extends TestCase
{
    #[Test]
    public function cleanup_eskaliert_rollback_false_mit_dem_urspruenglichen_fehler(): void
    {
        $db = $this->database();
        $db->returnFalseOnRollback = true;

        try {
            (new UsageRepository($db))->cleanupOwnedStaleUsages([99]);
            self::fail('Rollback false muss eskalieren.');
        } catch (RuntimeException $error) {
            self::assertSame('Die Bereinigung und ihre Rücknahme sind fehlgeschlagen.', $error->getMessage());
            self::assertInstanceOf(AssetNotFoundException::class, $error->getPrevious());
        }
    }

    #[Test]
    public function cleanup_eskaliert_rollback_throw_mit_dem_urspruenglichen_fehler(): void
    {
        $db = $this->database();
        $db->failRollback = true;

        try {
            (new UsageRepository($db))->cleanupOwnedStaleUsages([99]);
            self::fail('Rollback throw muss eskalieren.');
        } catch (RuntimeException $error) {
            self::assertSame('Die Bereinigung und ihre Rücknahme sind fehlgeschlagen.', $error->getMessage());
            self::assertInstanceOf(AssetNotFoundException::class, $error->getPrevious());
        }
    }

    #[Test]
    public function commit_false_stellt_die_geloeschte_fundstelle_wieder_her(): void
    {
        $db = $this->database();
        $usageId = $db->seedStaleUsage();
        $db->returnFalseOnCommit = true;

        try {
            (new UsageRepository($db))->cleanupOwnedStaleUsages([$usageId]);
            self::fail('Commit false muss die Bereinigung abbrechen.');
        } catch (RuntimeException $error) {
            self::assertSame('Die sichere Bereinigung konnte nicht bestätigt werden.', $error->getMessage());
            self::assertSame(1, $db->usageCount());
            self::assertSame(1, $db->rollbacks);
        }
    }

    #[Test]
    public function cleanup_preview_zaehlt_500_ids_in_hoechstens_fuenf_gebundenen_abfragen(): void
    {
        $db = $this->database();
        $ids = [];
        for ($index = 1; $index <= 500; ++$index) {
            $ids[] = $db->seedStaleUsage('veraltet-' . $index);
        }

        $count = (new UsageRepository($db))->countOwnedStaleUsageIds($ids);
        $queries = array_values(array_filter(
            $db->statements,
            static fn(array $statement): bool => str_contains($statement['sql'], 'FROM `xplugin_mgd_ai_usage`')
                && str_contains($statement['sql'], ' IN ('),
        ));

        self::assertSame(500, $count);
        self::assertLessThanOrEqual(5, count($queries));
        self::assertCount(100, $queries[0]['params']);
        self::assertStringNotContainsString('500', $queries[0]['sql']);
    }

    private function database(): TransactionalDatabaseFake
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_usage', SchemaOwnershipGuard::OWNERSHIP_MARKER);

        return $db;
    }
}
