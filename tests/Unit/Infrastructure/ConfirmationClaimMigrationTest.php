<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\ConfirmationClaimRepository;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\ConfirmationClaimSchemaGuard;
use Plugin\MGD_AI_Kennzeichnung\Migrations\Migration20260812000200CreateConfirmationClaimTable;
use RuntimeException;
use Tests\Support\TransactionalDatabaseFake;

final class ConfirmationClaimMigrationTest extends TestCase
{
    #[Test]
    public function migration_erstellt_eine_separate_eigentumsmarkierte_sicherheitstabelle_idempotent(): void
    {
        $db = new TransactionalDatabaseFake();
        $migration = new Migration20260812000200CreateConfirmationClaimTable($db);

        $migration->up();
        $migration->up();

        self::assertSame([ConfirmationClaimRepository::TABLE], $db->existingTables());
        (new ConfirmationClaimSchemaGuard($db))->assertOwned();
        self::assertSame(2, $db->lockRequests);
        self::assertSame(2, $db->lockReleases);
    }

    #[Test]
    public function migration_besitzt_zusaetzlich_den_von_jtl_572_erkannten_kanonischen_dateinamen(): void
    {
        $path = dirname(__DIR__, 3) . '/plugin/MGD_AI_Kennzeichnung/Migrations/Migration20260812000200.php';

        self::assertFileExists($path);
        require_once $path;
        self::assertTrue(class_exists('Plugin\\MGD_AI_Kennzeichnung\\Migrations\\Migration20260812000200'));
    }

    #[Test]
    public function fremde_namenskollision_wird_ohne_loeschung_abgewiesen(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker(ConfirmationClaimRepository::TABLE, 'fremder-marker');

        try {
            (new Migration20260812000200CreateConfirmationClaimTable($db))->up();
            self::fail('Eine fremde Tabelle darf nie übernommen werden.');
        } catch (RuntimeException) {
            self::assertSame([ConfirmationClaimRepository::TABLE], $db->existingTables());
            self::assertSame([], $db->droppedTables);
        }
    }

    #[Test]
    public function semantisch_veraenderte_eigene_tabelle_wird_abgewiesen(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker(ConfirmationClaimRepository::TABLE, ConfirmationClaimSchemaGuard::OWNERSHIP_MARKER);
        $db->setColumnType(ConfirmationClaimRepository::TABLE, 'token_hash', 'varchar(64)');

        $this->expectException(RuntimeException::class);
        (new Migration20260812000200CreateConfirmationClaimTable($db))->up();
    }

    #[Test]
    public function maria_db_darf_default_generated_im_extra_feld_auslassen(): void
    {
        $this->expectNotToPerformAssertions();
        $db = new TransactionalDatabaseFake();
        $db->setMarker(ConfirmationClaimRepository::TABLE, ConfirmationClaimSchemaGuard::OWNERSHIP_MARKER);
        $db->setColumnExtra(ConfirmationClaimRepository::TABLE, 'claimed_at', '');

        (new ConfirmationClaimSchemaGuard($db))->assertOwned();
    }
}
