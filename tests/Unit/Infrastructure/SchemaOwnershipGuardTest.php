<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

require_once __DIR__ . '/../../Stubs/JtlDatabaseStubs.php';

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\SchemaOwnershipGuard;
use RuntimeException;
use Tests\Support\TransactionalDatabaseFake;

final class SchemaOwnershipGuardTest extends TestCase
{
    private const ASSET_TABLE = 'xplugin_mgd_ai_asset';
    private const MARKER = 'mgd-ai-kennzeichnung-jtl-v1';

    #[Test]
    public function fremde_vorhandene_tabelle_darf_nicht_mutiert_werden(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker(self::ASSET_TABLE, 'fremdes-plugin');
        $guard = new SchemaOwnershipGuard($db);

        self::assertFalse($guard->mayMutate(self::ASSET_TABLE));

        $this->expectException(RuntimeException::class);
        $guard->assertOwned(self::ASSET_TABLE);
    }

    #[Test]
    public function nur_exakter_marker_aus_realem_tabellenkommentar_gilt_als_eigentum(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker(self::ASSET_TABLE, self::MARKER);
        $guard = new SchemaOwnershipGuard($db);

        self::assertTrue($guard->mayMutate(self::ASSET_TABLE));
        $guard->assertOwned(self::ASSET_TABLE);
        self::assertStringContainsString('INFORMATION_SCHEMA', $db->statements[0]['sql']);
        self::assertStringContainsString('TABLES', $db->statements[0]['sql']);
        self::assertStringContainsString('COLUMNS', $db->statements[0]['sql']);
        self::assertStringContainsString('STATISTICS', $db->statements[0]['sql']);
        self::assertStringContainsString('KEY_COLUMN_USAGE', $db->statements[0]['sql']);
        self::assertStringContainsString('REFERENTIAL_CONSTRAINTS', $db->statements[0]['sql']);
        self::assertStringContainsString('TABLE_COLLATION', $db->statements[0]['sql']);
        self::assertSame(self::ASSET_TABLE, $db->statements[0]['params']['table_name']);
        self::assertSame(['table_name' => self::ASSET_TABLE], $db->statements[0]['params']);
    }

    #[Test]
    public function marker_ohne_exakten_schema_fingerprint_gilt_nicht_als_eigentum(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker(self::ASSET_TABLE, self::MARKER);
        $db->setFingerprint(self::ASSET_TABLE, 'manipuliertes-schema');
        $guard = new SchemaOwnershipGuard($db);

        self::assertFalse($guard->mayMutate(self::ASSET_TABLE));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Schema');
        $guard->assertOwned(self::ASSET_TABLE);
    }

    #[Test]
    public function fehlende_eigene_tabelle_darf_erstellt_aber_nicht_als_vorhandenes_eigentum_behauptet_werden(): void
    {
        $guard = new SchemaOwnershipGuard(new TransactionalDatabaseFake());

        self::assertTrue($guard->mayMutate(self::ASSET_TABLE));

        $this->expectException(RuntimeException::class);
        $guard->assertOwned(self::ASSET_TABLE);
    }

    #[Test]
    public function migration_erstellt_exakt_drei_idempotente_tabellen_mit_marker_und_unique_vertraegen(): void
    {
        $db = new TransactionalDatabaseFake();
        $migrationClass = \Plugin\MGD_AI_Kennzeichnung\Migrations\Migration20260812000100::class;
        $migration = new $migrationClass($db);

        $migration->up();
        $migration->up();

        $createSql = array_values(array_map(
            static fn(array $aufruf): string => $aufruf['sql'],
            array_filter(
                $db->statements,
                static fn(array $aufruf): bool => str_starts_with(ltrim($aufruf['sql']), 'CREATE TABLE'),
            ),
        ));
        self::assertCount(3, $createSql, 'Nur der erste Lauf darf die drei fehlenden Tabellen erstellen.');
        self::assertSame(
            [
                'xplugin_mgd_ai_asset',
                'xplugin_mgd_ai_usage',
                'xplugin_mgd_ai_philosophy',
            ],
            array_map(static function (string $sql): string {
                preg_match('/CREATE TABLE `([^`]+)`/', $sql, $treffer);

                return $treffer[1] ?? '';
            }, $createSql),
        );
        foreach ($createSql as $sql) {
            self::assertStringContainsString("COMMENT='" . self::MARKER . "'", $sql);
        }
        $gesamtSql = implode("\n", array_slice($createSql, 0, 3));
        self::assertStringContainsString('UNIQUE KEY `uq_mgd_ai_asset_key` (`asset_key`)', $gesamtSql);
        self::assertStringContainsString(
            'UNIQUE KEY `uq_mgd_ai_usage_source` (`asset_id`, `source_type`, `source_reference_hash`)',
            $gesamtSql,
        );
        self::assertStringContainsString('UNIQUE KEY `uq_mgd_ai_philosophy_language` (`language`)', $gesamtSql);
        self::assertStringContainsString('FOREIGN KEY (`asset_id`)', $gesamtSql);
        self::assertStringContainsString('`asset_key` CHAR(64)', $gesamtSql);
        self::assertStringContainsString('COLLATE ascii_bin', $gesamtSql);
        self::assertStringNotContainsString('IF NOT EXISTS', $gesamtSql);
        self::assertGreaterThanOrEqual(2, $db->lockRequests);
        self::assertSame($db->lockRequests, $db->lockReleases);
    }

    #[Test]
    public function migration_bricht_fail_closed_ab_wenn_schema_lock_nicht_erlangt_wird(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->lockAvailable = false;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Sperre');

        (new \Plugin\MGD_AI_Kennzeichnung\Migrations\Migration20260812000100($db))->up();
    }

    #[Test]
    public function migration_raeumt_bei_fehler_nur_in_diesem_lauf_neu_erstellte_tabellen_fk_sicher_auf(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->failCreateNumber = 3;

        try {
            (new \Plugin\MGD_AI_Kennzeichnung\Migrations\Migration20260812000100($db))->up();
            self::fail('Der dritte CREATE-Fehler muss eskalieren.');
        } catch (RuntimeException $fehler) {
            self::assertStringContainsString('CREATE', $fehler->getMessage());
        }

        self::assertSame(
            ['xplugin_mgd_ai_usage', 'xplugin_mgd_ai_asset'],
            $db->droppedTables,
            'Die FK-abhängige Usage-Tabelle muss vor Asset kompensierend entfernt werden.',
        );
        self::assertSame([], $db->existingTables());
    }

    #[Test]
    public function migration_raeumt_nach_create_zwei_fehler_auf_und_kann_sicher_wiederholt_werden(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->failCreateNumber = 2;
        try {
            (new \Plugin\MGD_AI_Kennzeichnung\Migrations\Migration20260812000100($db))->up();
            self::fail('CREATE #2 muss fehlschlagen.');
        } catch (RuntimeException) {
            self::assertSame(['xplugin_mgd_ai_asset'], $db->droppedTables);
            self::assertSame([], $db->existingTables());
        }

        $db->failCreateNumber = null;
        (new \Plugin\MGD_AI_Kennzeichnung\Migrations\Migration20260812000100($db))->up();
        self::assertSame(
            ['xplugin_mgd_ai_asset', 'xplugin_mgd_ai_usage', 'xplugin_mgd_ai_philosophy'],
            $db->existingTables(),
        );
    }

    #[Test]
    public function migration_loescht_bei_cleanup_keine_inzwischen_veraenderte_tabelle(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->failCreateNumber = 2;
        $db->alterFingerprintBeforeCleanup = 'xplugin_mgd_ai_asset';

        try {
            (new \Plugin\MGD_AI_Kennzeichnung\Migrations\Migration20260812000100($db))->up();
            self::fail('Migration und unsicheres Cleanup müssen eskalieren.');
        } catch (RuntimeException $fehler) {
            self::assertStringContainsString('Bereinigung', $fehler->getMessage());
            self::assertNotNull($fehler->getPrevious());
        }

        self::assertSame([], $db->droppedTables);
        self::assertSame(['xplugin_mgd_ai_asset'], $db->existingTables());
    }

    #[Test]
    public function migration_uebernimmt_keine_fremdtabelle_die_zwischen_preflight_und_create_erscheint(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->foreignTableBeforeCreate = self::ASSET_TABLE;

        try {
            (new \Plugin\MGD_AI_Kennzeichnung\Migrations\Migration20260812000100($db))->up();
            self::fail('Die TOCTOU-Kollision muss sichtbar eskalieren.');
        } catch (RuntimeException $fehler) {
            self::assertStringContainsString('zwischen Preflight und CREATE', $fehler->getMessage());
        }

        self::assertSame([], $db->droppedTables);
        self::assertSame([self::ASSET_TABLE], $db->existingTables());
    }

    #[Test]
    public function migration_prueft_alle_kollisionen_bevor_sie_irgendeine_tabelle_mutiert(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_philosophy', 'nicht-unser-marker');
        $migration = new \Plugin\MGD_AI_Kennzeichnung\Migrations\Migration20260812000100($db);

        try {
            $migration->up();
            self::fail('Die Kollision muss die Migration abbrechen.');
        } catch (RuntimeException) {
            $createSql = array_filter(
                $db->statements,
                static fn(array $aufruf): bool => str_starts_with(ltrim($aufruf['sql']), 'CREATE TABLE'),
            );
            self::assertSame([], array_values($createSql));
        }
    }
}
