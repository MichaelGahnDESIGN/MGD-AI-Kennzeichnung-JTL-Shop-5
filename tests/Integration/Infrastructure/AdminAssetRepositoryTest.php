<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

require_once __DIR__ . '/../../Stubs/JtlDatabaseStubs.php';

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\AssetNotFoundException;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\AssetRepository;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\SchemaOwnershipGuard;
use Tests\Support\TransactionalDatabaseFake;

final class AdminAssetRepositoryTest extends TestCase
{
    #[Test]
    public function maskiertes_update_bewahrt_alle_nicht_gewaehlten_felder_und_bindet_werte(): void
    {
        $db = $this->database();
        $db->seedAssets(['bild-a' => 'unreviewed']);
        $repository = new AssetRepository($db);

        $repository->updateManyByIds([1], ['status' => 'generated']);

        self::assertSame(
            ['status' => 'generated', 'position' => 'bottom-right', 'theme' => 'auto'],
            $db->presentationForAsset(hash('sha256', 'bild-a')),
        );
        $updates = array_values(array_filter(
            $db->statements,
            static fn(array $statement): bool => str_contains($statement['sql'], 'UPDATE `xplugin_mgd_ai_asset`'),
        ));
        self::assertCount(1, $updates);
        self::assertSame(['status' => 'generated', 'id' => 1], $updates[0]['params']);
        self::assertStringNotContainsString('generated', $updates[0]['sql']);
    }

    #[Test]
    public function fehlende_id_rollt_die_gesamte_auswahl_vor_dem_ersten_schreiben_zurueck(): void
    {
        $db = $this->database();
        $db->seedAssets(['bild-a' => 'unreviewed']);
        $repository = new AssetRepository($db);

        try {
            $repository->updateManyByIds([1, 99], ['theme' => 'dark']);
            self::fail('Eine fehlende ID muss die Transaktion abbrechen.');
        } catch (AssetNotFoundException) {
            self::assertSame(
                ['status' => 'unreviewed', 'position' => 'bottom-right', 'theme' => 'auto'],
                $db->presentationForAsset(hash('sha256', 'bild-a')),
            );
            self::assertSame(1, $db->begins);
            self::assertSame(0, $db->commits);
            self::assertSame(1, $db->rollbacks);
        }
    }

    private function database(): TransactionalDatabaseFake
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_asset', SchemaOwnershipGuard::OWNERSHIP_MARKER);

        return $db;
    }
}
