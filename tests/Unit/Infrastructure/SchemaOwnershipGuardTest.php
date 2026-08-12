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
        self::assertSame(['table_name' => self::ASSET_TABLE], $db->statements[0]['params']);
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
        self::assertCount(6, $createSql, 'Zwei Läufe müssen dieselben drei Tabellen idempotent prüfen/erstellen.');
        self::assertSame(
            [
                'xplugin_mgd_ai_asset',
                'xplugin_mgd_ai_usage',
                'xplugin_mgd_ai_philosophy',
            ],
            array_map(static function (string $sql): string {
                preg_match('/CREATE TABLE IF NOT EXISTS `([^`]+)`/', $sql, $treffer);

                return $treffer[1] ?? '';
            }, array_slice($createSql, 0, 3)),
        );
        foreach ($createSql as $sql) {
            self::assertStringContainsString("COMMENT='" . self::MARKER . "'", $sql);
        }
        $gesamtSql = implode("\n", array_slice($createSql, 0, 3));
        self::assertStringContainsString('UNIQUE KEY `uq_mgd_ai_asset_key` (`asset_key`)', $gesamtSql);
        self::assertStringContainsString(
            'UNIQUE KEY `uq_mgd_ai_usage_source` (`asset_id`, `source_type`, `source_reference`)',
            $gesamtSql,
        );
        self::assertStringContainsString('UNIQUE KEY `uq_mgd_ai_philosophy_language` (`language`)', $gesamtSql);
        self::assertStringContainsString('FOREIGN KEY (`asset_id`)', $gesamtSql);
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
