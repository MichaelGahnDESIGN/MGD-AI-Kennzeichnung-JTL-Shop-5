<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

require_once __DIR__ . '/../../Stubs/JtlDatabaseStubs.php';

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\AssetNotFoundException;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\AssetRepository;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\SchemaOwnershipGuard;
use RuntimeException;
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

    #[Test]
    public function rollback_false_wird_mit_urspruenglichem_fehler_eskaliert(): void
    {
        $db = $this->database();
        $db->returnFalseOnRollback = true;
        $repository = new AssetRepository($db);

        try {
            $repository->updateManyByIds([99], ['theme' => 'dark']);
            self::fail('Rollback false muss als eigener Betriebsfehler eskalieren.');
        } catch (RuntimeException $error) {
            self::assertSame('Die Bildänderung und ihre Rücknahme sind fehlgeschlagen.', $error->getMessage());
            self::assertInstanceOf(AssetNotFoundException::class, $error->getPrevious());
        }
    }

    #[Test]
    public function rollback_throw_wird_mit_urspruenglichem_fehler_eskaliert(): void
    {
        $db = $this->database();
        $db->failRollback = true;
        $repository = new AssetRepository($db);

        try {
            $repository->updateManyByIds([99], ['theme' => 'dark']);
            self::fail('Rollback throw muss als eigener Betriebsfehler eskalieren.');
        } catch (RuntimeException $error) {
            self::assertSame('Die Bildänderung und ihre Rücknahme sind fehlgeschlagen.', $error->getMessage());
            self::assertInstanceOf(AssetNotFoundException::class, $error->getPrevious());
        }
    }

    #[Test]
    public function commit_false_rollt_die_bereits_geschriebene_aenderung_zurueck(): void
    {
        $db = $this->database();
        $db->seedAssets(['bild-a' => 'unreviewed']);
        $db->returnFalseOnCommit = true;

        try {
            (new AssetRepository($db))->updateManyByIds([1], ['status' => 'generated']);
            self::fail('Commit false muss die Mutation abbrechen.');
        } catch (RuntimeException $error) {
            self::assertSame('Die sichere Bildänderung konnte nicht bestätigt werden.', $error->getMessage());
            self::assertSame('unreviewed', $db->statusForAsset(hash('sha256', 'bild-a')));
            self::assertSame(1, $db->rollbacks);
        }
    }

    #[Test]
    public function preview_zaehlt_500_ids_in_hoechstens_fuenf_gebundenen_abfragen(): void
    {
        $db = $this->database();
        $assets = [];
        for ($index = 1; $index <= 500; ++$index) {
            $assets['bild-' . $index] = 'unreviewed';
        }
        $db->seedAssets($assets);

        $count = (new AssetRepository($db))->countExistingIds(range(1, 500));
        $queries = array_values(array_filter(
            $db->statements,
            static fn(array $statement): bool => str_contains($statement['sql'], 'FROM `xplugin_mgd_ai_asset`')
                && str_contains($statement['sql'], ' IN ('),
        ));

        self::assertSame(500, $count);
        self::assertLessThanOrEqual(5, count($queries));
        self::assertCount(100, $queries[0]['params']);
        self::assertStringNotContainsString('500', $queries[0]['sql']);
    }

    #[Test]
    public function liste_und_detail_liefern_quelle_und_aenderungszeit_fuer_die_galerie(): void
    {
        $db = $this->database();
        $assetKey = hash('sha256', 'media/image/storage/produkt.jpg');
        $db->seedScanAsset($assetKey, '/media/image/storage/produkt.jpg', 'generated');
        $db->seedScanUsage($assetKey, '/media/image/storage/produkt.jpg', 'artikel:7', 'product');
        $repository = new AssetRepository($db);

        $liste = $repository->listPage(0, 25, [], 'updated_at', 'desc');
        $detail = $repository->detailById(1);

        self::assertCount(1, $liste);
        self::assertSame('product', $liste[0]['source'] ?? null);
        self::assertSame('2026-08-22 12:00:00', $liste[0]['updated_at'] ?? null);
        self::assertSame('product', $detail['source'] ?? null);
        self::assertSame('2026-08-22 12:00:00', $detail['updated_at'] ?? null);
    }

    private function database(): TransactionalDatabaseFake
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_asset', SchemaOwnershipGuard::OWNERSHIP_MARKER);

        return $db;
    }
}
