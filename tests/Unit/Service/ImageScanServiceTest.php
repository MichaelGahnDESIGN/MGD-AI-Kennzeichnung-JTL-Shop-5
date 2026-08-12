<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

require_once __DIR__ . '/../../Stubs/JtlDatabaseStubs.php';

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Domain\AssetSource;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\AssetRepository;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\UsageRepository;
use Plugin\MGD_AI_Kennzeichnung\Scanner\Adapter\BannerSourceAdapter;
use Plugin\MGD_AI_Kennzeichnung\Scanner\Adapter\CategorySourceAdapter;
use Plugin\MGD_AI_Kennzeichnung\Scanner\Adapter\ManufacturerSourceAdapter;
use Plugin\MGD_AI_Kennzeichnung\Scanner\Adapter\OpcSourceAdapter;
use Plugin\MGD_AI_Kennzeichnung\Scanner\Adapter\ProductSourceAdapter;
use Plugin\MGD_AI_Kennzeichnung\Scanner\LocalImageReference;
use Plugin\MGD_AI_Kennzeichnung\Scanner\LocalPathNormalizer;
use Plugin\MGD_AI_Kennzeichnung\Scanner\SourceAdapterInterface;
use Plugin\MGD_AI_Kennzeichnung\Scanner\SourceAdapterPageInterface;
use Plugin\MGD_AI_Kennzeichnung\Scanner\SourceScanPage;
use Plugin\MGD_AI_Kennzeichnung\Service\ImageScanService;
use RuntimeException;
use Tests\Support\TransactionalDatabaseFake;

final class ImageScanServiceTest extends TestCase
{
    private const MARKER = 'mgd-ai-kennzeichnung-jtl-v1';

    #[Test]
    public function alle_fuenf_adapter_liefern_nur_normalisierte_lokale_referenzen_mit_bindings(): void
    {
        $db = $this->scannerDatabase();
        $db->scannerRows = [
            'tartikelpict' => [(object) ['local_path' => '/media/image/storage/a.jpg', 'source_reference' => 'artikelbild:1:artikel:1', 'context' => 'Artikel']],
            'tkategoriepict' => [(object) ['local_path' => '/media/image/storage/categories/k.png', 'source_reference' => 'kategoriebild:2:kategorie:2', 'context' => 'Kategorie 2']],
            'thersteller' => [(object) ['local_path' => '/media/image/storage/manufacturers/h.webp', 'source_reference' => 'hersteller:3', 'context' => 'Hersteller']],
            'timagemap' => [(object) ['local_path' => '/bilder/banner/b.gif', 'source_reference' => 'banner:4', 'context' => 'Banner']],
            'topcpage' => [(object) [
                'page_id' => 5,
                'areas_json' => '{"properties":{"src":"o.avif"}}',
                'context' => 'OPC',
            ]],
        ];
        $normalizer = new LocalPathNormalizer();
        $adapters = [
            new ProductSourceAdapter($db, $normalizer),
            new CategorySourceAdapter($db, $normalizer),
            new ManufacturerSourceAdapter($db, $normalizer),
            new BannerSourceAdapter($db, $normalizer),
            new OpcSourceAdapter($db, $normalizer),
        ];

        self::assertSame([
            AssetSource::Product,
            AssetSource::Category,
            AssetSource::Manufacturer,
            AssetSource::Banner,
            AssetSource::Opc,
        ], array_map(static fn(SourceAdapterInterface $adapter): AssetSource => $adapter->source(), $adapters));

        foreach ($adapters as $adapter) {
            self::assertInstanceOf(SourceAdapterPageInterface::class, $adapter);
            $page = $adapter->scanPage(0, 100);
            $rows = $page->references;
            self::assertCount(1, $rows);
            self::assertInstanceOf(LocalImageReference::class, $rows[0]);
            self::assertSame(1, $page->rowsRead);
        }

        foreach (array_slice($db->statements, -5) as $statement) {
            self::assertSame(['offset' => 0, 'limit' => 100], $statement['params']);
            self::assertStringContainsString(':offset', $statement['sql']);
            self::assertStringContainsString(':limit', $statement['sql']);
            self::assertStringNotContainsString('fremd.example', $statement['sql']);
        }
    }

    #[Test]
    public function adapter_sql_verwendet_offizielle_jtl_572_tabellen_primaerschluessel_und_referenzen(): void
    {
        $db = $this->scannerDatabase();
        $normalizer = new LocalPathNormalizer();
        $adapters = [
            new ProductSourceAdapter($db, $normalizer),
            new CategorySourceAdapter($db, $normalizer),
            new ManufacturerSourceAdapter($db, $normalizer),
            new BannerSourceAdapter($db, $normalizer),
            new OpcSourceAdapter($db, $normalizer),
        ];

        foreach ($adapters as $adapter) {
            self::assertSame([], self::collect($adapter->scan(7, 11)));
        }
        $sql = array_column(array_slice($db->statements, -5), 'sql');

        self::assertStringContainsString('FROM `tartikelpict`', $sql[0]);
        self::assertStringContainsString('`p`.`kArtikelPict`', $sql[0]);
        self::assertStringContainsString("CONCAT('media/image/storage/', `p`.`cPfad`)", $sql[0]);
        self::assertStringContainsString("CONCAT('artikelbild:', `p`.`kArtikelPict`, ':artikel:', `p`.`kArtikel`)", $sql[0]);
        self::assertStringContainsString('ORDER BY `p`.`kArtikelPict`', $sql[0]);
        self::assertStringContainsString('FROM `tkategoriepict`', $sql[1]);
        self::assertStringContainsString('`p`.`kKategoriePict`', $sql[1]);
        self::assertStringContainsString("CONCAT('media/image/storage/categories/', `p`.`cPfad`)", $sql[1]);
        self::assertStringContainsString("CONCAT('kategoriebild:', `p`.`kKategoriePict`, ':kategorie:', `p`.`kKategorie`)", $sql[1]);
        self::assertStringContainsString('ORDER BY `p`.`kKategoriePict`', $sql[1]);
        self::assertStringContainsString('FROM `thersteller`', $sql[2]);
        self::assertStringContainsString("CONCAT('media/image/storage/manufacturers/', `h`.`cBildpfad`)", $sql[2]);
        self::assertStringContainsString("CONCAT('hersteller:', `h`.`kHersteller`)", $sql[2]);
        self::assertStringContainsString('ORDER BY `h`.`kHersteller`', $sql[2]);
        self::assertStringContainsString('FROM `timagemap`', $sql[3]);
        self::assertStringContainsString('`b`.`cBildPfad`', $sql[3]);
        self::assertStringContainsString("CONCAT('banner:', `b`.`kImageMap`)", $sql[3]);
        self::assertStringContainsString('ORDER BY `b`.`kImageMap`', $sql[3]);
        self::assertStringContainsString('FROM `topcpage`', $sql[4]);
        self::assertStringContainsString('`p`.`cAreasJson`', $sql[4]);
        self::assertStringContainsString('ORDER BY `p`.`kPage`', $sql[4]);
        foreach (array_slice($db->statements, -5) as $statement) {
            self::assertSame(['offset' => 7, 'limit' => 11], $statement['params']);
        }
    }

    #[Test]
    public function adapter_begrenzen_jede_seite_und_ueberspringen_ungueltige_referenzen(): void
    {
        $db = $this->scannerDatabase();
        $db->scannerRows['tartikelpict'] = [
            (object) ['local_path' => 'https://fremd.example/person.jpg', 'source_reference' => 'artikel:1', 'context' => null],
            (object) ['local_path' => '/bilder/gut.jpg', 'source_reference' => 'artikel:2', 'context' => null],
        ];
        $adapter = new ProductSourceAdapter($db, new LocalPathNormalizer());

        self::assertCount(1, self::collect($adapter->scan(0, 100)));

        foreach ([[-1, 1], [0, 0], [0, 101]] as [$offset, $limit]) {
            try {
                $unexpectedRows = self::collect($adapter->scan($offset, $limit));
                self::fail('Ungültige Seitengrenzen müssen abgelehnt werden.');
            } catch (\InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }

    #[Test]
    public function scan_paginiert_mit_hoechstens_hundert_und_ist_fuer_duplikate_wiederholbar(): void
    {
        $db = $this->scannerDatabase();
        $normalizer = new LocalPathNormalizer();
        $references = [];
        for ($index = 0; $index < 101; ++$index) {
            $references[] = self::reference($normalizer, '/bilder/a.jpg', 'artikel:' . $index);
        }
        $adapter = new RecordingAdapter(AssetSource::Product, $references);
        $service = new ImageScanService(
            [$adapter],
            new AssetRepository($db),
            new UsageRepository($db),
        );

        $first = $service->scan();
        $second = $service->scan();

        self::assertSame(1, $first->createdAssets);
        self::assertSame(101, $first->recordedUsages);
        self::assertSame(0, $second->createdAssets);
        self::assertSame(1, $db->assetCount());
        self::assertSame(101, $db->usageCount());
        self::assertSame('bilder/a.jpg', $db->localPathForAsset(hash('sha256', 'bilder/a.jpg')));
        self::assertSame([[0, 100], [100, 100], [0, 100], [100, 100]], $adapter->calls);
    }

    #[Test]
    public function scan_erhaelt_bestehende_kennzeichnung_und_markiert_fehlend_erst_nach_erfolg(): void
    {
        $db = $this->scannerDatabase();
        $normalizer = new LocalPathNormalizer();
        $existing = self::reference($normalizer, '/bilder/bestehend.jpg', 'artikel:1');
        $missing = self::reference($normalizer, '/bilder/alt.jpg', 'artikel:alt');
        $db->seedScanAsset($existing->assetKey, $existing->localPath, 'generated');
        $db->seedScanUsage($missing->assetKey, $missing->localPath, 'artikel:alt');
        $db->seedScanUsage(
            hash('sha256', 'bilder/kategorie-alt.jpg'),
            'bilder/kategorie-alt.jpg',
            'kategorie:alt',
            'category',
        );
        $db->seedScanUsage(
            hash('sha256', 'bilder/manuell.jpg'),
            'bilder/manuell.jpg',
            'manuell:1',
            'custom-local-manual',
        );
        $service = new ImageScanService(
            [new RecordingAdapter(AssetSource::Product, [$existing])],
            new AssetRepository($db),
            new UsageRepository($db),
        );

        $service->scan();

        self::assertSame('generated', $db->statusForAsset($existing->assetKey));
        self::assertFalse($db->usageIsPresent('artikel:alt'));
        self::assertTrue($db->usageIsPresent('kategorie:alt'));
        self::assertTrue($db->usageIsPresent('artikel:1'));
        self::assertTrue($db->usageIsPresent('manuell:1'));
    }

    #[Test]
    public function erfolgreicher_scan_markiert_nur_die_explizit_vollstaendig_gelesenen_quellen_fehlend(): void
    {
        $db = $this->scannerDatabase();
        $automaticSources = [
            AssetSource::Product,
            AssetSource::Category,
            AssetSource::Manufacturer,
            AssetSource::Banner,
            AssetSource::Opc,
        ];
        foreach ($automaticSources as $source) {
            $db->seedScanUsage(
                hash('sha256', 'bilder/' . $source->value . '.jpg'),
                'bilder/' . $source->value . '.jpg',
                $source->value . ':alt',
                $source->value,
            );
        }
        $adapters = array_map(
            static fn(AssetSource $source): SourceAdapterInterface => new RecordingAdapter($source, []),
            $automaticSources,
        );

        (new ImageScanService($adapters, new AssetRepository($db), new UsageRepository($db)))->scan();

        foreach ($automaticSources as $source) {
            self::assertFalse($db->usageIsPresent($source->value . ':alt'));
        }
        $missingUpdates = array_filter(
            $db->statements,
            static fn(array $statement): bool => str_contains($statement['sql'], 'SET `usage`.`is_present` = 0'),
        );
        self::assertCount(5, $missingUpdates);
        self::assertSame(
            array_map(static fn(AssetSource $source): string => $source->value, $automaticSources),
            array_column(array_column($missingUpdates, 'params'), 'source_type'),
        );
    }

    #[Test]
    public function leerer_scan_markiert_keine_bestehende_nutzung_fehlend(): void
    {
        $db = $this->scannerDatabase();
        $db->seedScanUsage(hash('sha256', 'bilder/a.jpg'), 'bilder/a.jpg', 'artikel:alt');

        $result = (new ImageScanService([], new AssetRepository($db), new UsageRepository($db)))->scan();

        self::assertSame(0, $result->createdAssets);
        self::assertSame(0, $result->recordedUsages);
        self::assertTrue($db->usageIsPresent('artikel:alt'));
    }

    #[Test]
    public function doppelte_quellenadapter_werden_vor_transaktion_und_schreibzugriff_abgewiesen(): void
    {
        $db = $this->scannerDatabase();
        $service = new ImageScanService(
            [
                new RecordingAdapter(AssetSource::Product, []),
                new RecordingAdapter(AssetSource::Product, []),
            ],
            new AssetRepository($db),
            new UsageRepository($db),
        );

        $error = null;
        try {
            $service->scan();
        } catch (RuntimeException $exception) {
            $error = $exception;
        }

        self::assertNotNull($error, 'Doppelte Quellentypen müssen vor dem Scan abgewiesen werden.');
        self::assertStringContainsString('doppelt', $error->getMessage());
        self::assertSame(0, $db->begins);
        self::assertSame([], $db->statements);
    }

    #[Test]
    public function adapterfehler_rollt_schreibvorgaenge_zurueck_und_markiert_nichts_fehlend(): void
    {
        $db = $this->scannerDatabase();
        $normalizer = new LocalPathNormalizer();
        $old = self::reference($normalizer, '/bilder/alt.jpg', 'artikel:alt');
        $new = self::reference($normalizer, '/bilder/neu.jpg', 'artikel:neu');
        $db->seedScanUsage($old->assetKey, $old->localPath, $old->sourceReference);
        $service = new ImageScanService(
            [
                new RecordingAdapter(AssetSource::Product, [$new]),
                new ThrowingAdapter(),
            ],
            new AssetRepository($db),
            new UsageRepository($db),
        );

        try {
            $service->scan();
            self::fail('Der Adapterfehler muss sichtbar bleiben.');
        } catch (RuntimeException $exception) {
            self::assertSame('Erzwungener Scannerfehler.', $exception->getMessage());
        }

        self::assertTrue($db->usageIsPresent('artikel:alt'));
        self::assertFalse($db->hasUsage('artikel:neu'));
        self::assertSame(1, $db->rollbacks);
    }

    #[Test]
    public function aktive_aeussere_nicedb_transaktion_wird_vor_scanwrites_fail_fast_abgewiesen(): void
    {
        $db = $this->scannerDatabase();
        $db->beginOuterTransactionForTest();
        $db->seedScanUsage(hash('sha256', 'bilder/alt.jpg'), 'bilder/alt.jpg', 'artikel:alt');
        $service = new ImageScanService(
            [new RecordingAdapter(AssetSource::Product, [])],
            new AssetRepository($db),
            new UsageRepository($db),
        );

        try {
            $service->scan();
            self::fail('Eine äußere Transaktion darf nicht durch NiceDBs inneren Rollback gefährdet werden.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('Transaktion', $exception->getMessage());
        }

        self::assertSame(0, $db->begins);
        self::assertSame(0, $db->commits);
        self::assertSame(0, $db->rollbacks);
        self::assertTrue($db->usageIsPresent('artikel:alt'));
    }

    #[Test]
    public function temporaere_scantabelle_wird_trotz_werfendem_rollback_entfernt_und_naechster_lauf_funktioniert(): void
    {
        $db = $this->scannerDatabase();
        $db->failRollback = true;
        $service = new ImageScanService(
            [new ThrowingAdapter()],
            new AssetRepository($db),
            new UsageRepository($db),
        );

        try {
            $service->scan();
            self::fail('Scanner- und Rollbackfehler müssen sichtbar bleiben.');
        } catch (RuntimeException $exception) {
            self::assertSame('Erzwungener Scannerfehler.', $exception->getPrevious()?->getMessage());
        }
        self::assertSame(1, $db->temporaryTableDrops);

        $db->failRollback = false;
        (new ImageScanService(
            [new RecordingAdapter(AssetSource::Product, [])],
            new AssetRepository($db),
            new UsageRepository($db),
        ))->scan();
        self::assertSame(2, $db->temporaryTableDrops);
    }

    #[Test]
    public function temporaere_scantabelle_wird_auch_bei_rollback_false_entfernt(): void
    {
        $db = $this->scannerDatabase();
        $db->returnFalseOnRollback = true;

        try {
            (new ImageScanService(
                [new ThrowingAdapter()],
                new AssetRepository($db),
                new UsageRepository($db),
            ))->scan();
            self::fail('Rollback false muss sichtbar bleiben.');
        } catch (RuntimeException $exception) {
            self::assertSame('Erzwungener Scannerfehler.', $exception->getPrevious()?->getMessage());
        }

        self::assertSame(1, $db->temporaryTableDrops);
    }

    #[Test]
    public function temporaere_scantabelle_wird_bei_commit_false_oder_exception_entfernt(): void
    {
        foreach (['false', 'exception'] as $modus) {
            $db = $this->scannerDatabase();
            $db->returnFalseOnCommit = $modus === 'false';
            $db->failCommit = $modus === 'exception';

            try {
                (new ImageScanService(
                    [new RecordingAdapter(AssetSource::Product, [])],
                    new AssetRepository($db),
                    new UsageRepository($db),
                ))->scan();
                self::fail('Ein fehlgeschlagener Commit muss eskalieren.');
            } catch (RuntimeException $exception) {
                self::assertStringContainsString($modus === 'false' ? 'bestätigt' : 'Commit', $exception->getMessage());
            }

            self::assertGreaterThanOrEqual(1, $db->temporaryTableDrops);
            self::assertSame(1, $db->rollbacks);
        }
    }

    #[Test]
    public function cleanupfehler_bleibt_sichtbar_und_bewahrt_den_scannerfehler_als_previous(): void
    {
        $db = $this->scannerDatabase();
        $db->failTemporaryDropOnce = true;

        try {
            (new ImageScanService(
                [new ThrowingAdapter()],
                new AssetRepository($db),
                new UsageRepository($db),
            ))->scan();
            self::fail('Der Cleanupfehler muss sichtbar eskalieren.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('temporären Scantabelle', $exception->getMessage());
            self::assertSame('Erzwungener Scannerfehler.', $exception->getPrevious()?->getMessage());
        }
    }

    #[Test]
    public function identische_volle_seiten_werden_als_endlosadapter_abgebrochen(): void
    {
        $db = $this->scannerDatabase();
        $normalizer = new LocalPathNormalizer();
        $page = array_fill(0, 100, self::reference($normalizer, '/bilder/a.jpg', 'artikel:1'));
        $service = new ImageScanService(
            [new RepeatingPageAdapter($page)],
            new AssetRepository($db),
            new UsageRepository($db),
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('identische Seite');
        $service->scan();
    }

    #[Test]
    public function hundert_wechselnde_volle_db_seiten_brechen_atomar_ohne_missing_ab(): void
    {
        $db = $this->scannerDatabase();
        $normalizer = new LocalPathNormalizer();
        $old = self::reference($normalizer, '/bilder/alt.jpg', 'artikel:alt');
        $db->seedScanUsage($old->assetKey, $old->localPath, $old->sourceReference);
        $service = new ImageScanService(
            [new EndlessChangingPageAdapter($normalizer)],
            new AssetRepository($db),
            new UsageRepository($db),
        );

        try {
            $service->scan();
            self::fail('Die harte Grenze von 100 vollen DB-Seiten muss abbrechen.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('100', $exception->getMessage());
        }

        self::assertTrue($db->usageIsPresent('artikel:alt'));
        self::assertSame(1, $db->rollbacks);
    }

    #[Test]
    public function volle_ungueltige_db_seite_beendet_den_scan_nicht_vor_einer_spaeteren_gueltigen_seite(): void
    {
        $db = $this->scannerDatabase();
        $normalizer = new LocalPathNormalizer();
        $service = new ImageScanService(
            [new InvalidThenValidPageAdapter($normalizer)],
            new AssetRepository($db),
            new UsageRepository($db),
        );

        $result = $service->scan();

        self::assertSame(1, $result->createdAssets);
        self::assertSame(1, $result->recordedUsages);
        self::assertTrue($db->usageIsPresent('artikel:spät'));
    }

    #[Test]
    public function scannerseite_verwirft_mehr_als_fuenfhundert_referenzen_hart(): void
    {
        $normalizer = new LocalPathNormalizer();
        $reference = self::reference($normalizer, '/bilder/a.jpg', 'artikel:1');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('500');
        new SourceScanPage(array_fill(0, 501, $reference), 100);
    }

    #[Test]
    public function opc_ueberschreitung_des_zeilenlimits_bricht_atomar_ohne_missing_ab(): void
    {
        $db = $this->scannerDatabase();
        $db->seedScanUsage(hash('sha256', 'bilder/alt.jpg'), 'bilder/alt.jpg', 'opc:alt', 'opc');
        $images = [];
        for ($index = 0; $index < 101; ++$index) {
            $images[] = ['url' => 'bild-' . $index . '.jpg'];
        }
        $db->scannerRows['topcpage'] = [(object) [
            'page_id' => 1,
            'context' => 'Zu viele Bilder',
            'areas_json' => json_encode(['properties' => ['images' => $images]], JSON_THROW_ON_ERROR),
        ]];
        $service = new ImageScanService(
            [new OpcSourceAdapter($db, new LocalPathNormalizer())],
            new AssetRepository($db),
            new UsageRepository($db),
        );

        try {
            $service->scan();
            self::fail('Mehr als 100 OPC-Referenzen pro DB-Zeile müssen den Lauf abbrechen.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('100', $exception->getMessage());
        }

        self::assertTrue($db->usageIsPresent('opc:alt'));
        self::assertSame(1, $db->rollbacks);
    }

    #[Test]
    public function ownership_wird_pro_repository_und_scan_nur_einmal_geprueft(): void
    {
        $db = $this->scannerDatabase();
        $normalizer = new LocalPathNormalizer();
        $references = [];
        for ($index = 0; $index < 100; ++$index) {
            $references[] = self::reference($normalizer, '/bilder/' . $index . '.jpg', 'artikel:' . $index);
        }

        (new ImageScanService(
            [new RecordingAdapter(AssetSource::Product, $references)],
            new AssetRepository($db),
            new UsageRepository($db),
        ))->scan();

        $metadataStatements = array_filter(
            $db->statements,
            static fn(array $statement): bool => str_contains($statement['sql'], 'INFORMATION_SCHEMA'),
        );
        self::assertCount(8, $metadataStatements, 'Asset- und Usage-Eigentum benötigen je genau vier Metadatenabfragen.');
    }

    #[Test]
    public function opc_json_extrahiert_nur_offizielle_bildfelder_bounded_und_eindeutig(): void
    {
        $db = $this->scannerDatabase();
        $db->scannerRows['topcpage'] = [(object) [
            'page_id' => 17,
            'context' => '<b>Startseite</b>',
            'areas_json' => json_encode([
                'area' => ['content' => [[
                    'properties' => [
                        'src' => 'bilder/eins.jpg',
                        'still-src' => 'bilder/zwei.png',
                        'video-poster' => 'bilder/drei.webp',
                        'url' => 'https://fremd.example/nicht-bild.jpg',
                        'text' => '<img src="bilder/versteckt.jpg">',
                        'images' => [['url' => 'bilder/vier.gif'], ['url' => 'javascript%3Aangriff.jpg']],
                        'slides' => [['url' => 'bilder/fuenf.avif']],
                    ],
                ]]],
            ], JSON_THROW_ON_ERROR),
        ]];
        $adapter = new OpcSourceAdapter($db, new LocalPathNormalizer());

        $references = self::collect($adapter->scan(0, 100));

        self::assertCount(5, $references);
        self::assertSame([
            'media/image/storage/opc/bilder/eins.jpg',
            'media/image/storage/opc/bilder/zwei.png',
            'media/image/storage/opc/bilder/drei.webp',
            'media/image/storage/opc/bilder/vier.gif',
            'media/image/storage/opc/bilder/fuenf.avif',
        ], array_map(static fn(LocalImageReference $reference): string => $reference->localPath, $references));
        self::assertCount(5, array_unique(array_map(
            static fn(LocalImageReference $reference): string => $reference->sourceReference,
            $references,
        )));
        foreach ($references as $reference) {
            self::assertStringStartsWith('opc-seite:17:json:', $reference->sourceReference);
            self::assertSame('Startseite', $reference->context);
        }
    }

    #[Test]
    public function opc_json_verwirft_malformed_tief_oder_uebergross_fail_closed(): void
    {
        $db = $this->scannerDatabase();
        $db->scannerRows['topcpage'] = [
            (object) ['page_id' => 1, 'context' => 'kaputt', 'areas_json' => '{'],
            (object) ['page_id' => 2, 'context' => 'tief', 'areas_json' => str_repeat('[', 70) . '"bilder/a.jpg"' . str_repeat(']', 70)],
            (object) ['page_id' => 3, 'context' => 'groß', 'areas_json' => str_repeat(' ', 1048577)],
        ];

        self::assertSame([], self::collect((new OpcSourceAdapter($db, new LocalPathNormalizer()))->scan(0, 100)));
    }

    #[Test]
    public function opc_json_verwirft_bereits_eine_gueltige_zeile_ueber_hundert_kibibyte(): void
    {
        $db = $this->scannerDatabase();
        $db->scannerRows['topcpage'] = [(object) [
            'page_id' => 9,
            'context' => 'Groß',
            'areas_json' => json_encode([
                'padding' => str_repeat('x', 102400),
                'properties' => ['src' => 'muss-verworfen-werden.jpg'],
            ], JSON_THROW_ON_ERROR),
        ]];

        self::assertSame([], self::collect((new OpcSourceAdapter($db, new LocalPathNormalizer()))->scan(0, 100)));
    }

    private function scannerDatabase(): TransactionalDatabaseFake
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_asset', self::MARKER);
        $db->setMarker('xplugin_mgd_ai_usage', self::MARKER);

        return $db;
    }

    private static function reference(LocalPathNormalizer $normalizer, string $path, string $reference): LocalImageReference
    {
        $result = LocalImageReference::fromRaw($path, AssetSource::Product, $reference, null, $normalizer);
        self::assertNotNull($result);

        return $result;
    }

    public static function referenceForAdapter(
        LocalPathNormalizer $normalizer,
        string $path,
        string $reference,
    ): LocalImageReference {
        return self::reference($normalizer, $path, $reference);
    }

    /**
     * @template T
     * @param iterable<T> $values
     * @return list<T>
     */
    private static function collect(iterable $values): array
    {
        $result = [];
        foreach ($values as $value) {
            $result[] = $value;
        }

        return $result;
    }
}

/** Begrenzter Testadapter, der echte Referenzobjekte ohne Datenbank-Mock liefert. */
final class RecordingAdapter implements SourceAdapterInterface, SourceAdapterPageInterface
{
    /** @var list<array{int, int}> */
    public array $calls = [];

    /** @param list<LocalImageReference> $references */
    public function __construct(private readonly AssetSource $assetSource, private readonly array $references) {}

    public function scan(int $offset, int $limit): iterable
    {
        $this->calls[] = [$offset, $limit];
        yield from array_slice($this->references, $offset, $limit);
    }

    public function scanPage(int $offset, int $limit): SourceScanPage
    {
        $this->calls[] = [$offset, $limit];
        $references = array_slice($this->references, $offset, $limit);

        return new SourceScanPage($references, count($references));
    }

    public function source(): AssetSource
    {
        return $this->assetSource;
    }
}

final class ThrowingAdapter implements SourceAdapterInterface, SourceAdapterPageInterface
{
    public function scan(int $offset, int $limit): iterable
    {
        throw new RuntimeException('Erzwungener Scannerfehler.');
    }

    public function source(): AssetSource
    {
        return AssetSource::Banner;
    }

    public function scanPage(int $offset, int $limit): SourceScanPage
    {
        throw new RuntimeException('Erzwungener Scannerfehler.');
    }
}

final class RepeatingPageAdapter implements SourceAdapterInterface, SourceAdapterPageInterface
{
    /** @param list<LocalImageReference> $page */
    public function __construct(private readonly array $page) {}

    public function scan(int $offset, int $limit): iterable
    {
        yield from $this->page;
    }

    public function source(): AssetSource
    {
        return AssetSource::Product;
    }

    public function scanPage(int $offset, int $limit): SourceScanPage
    {
        return new SourceScanPage($this->page, 100);
    }
}

final class EndlessChangingPageAdapter implements SourceAdapterInterface, SourceAdapterPageInterface
{
    public function __construct(private readonly LocalPathNormalizer $normalizer) {}

    public function scan(int $offset, int $limit): iterable
    {
        yield from $this->scanPage($offset, $limit)->references;
    }

    public function scanPage(int $offset, int $limit): SourceScanPage
    {
        $references = [];
        for ($index = 0; $index < 100; ++$index) {
            $references[] = ImageScanServiceTest::referenceForAdapter(
                $this->normalizer,
                '/bilder/' . $offset . '-' . $index . '.jpg',
                'artikel:' . $offset . ':' . $index,
            );
        }

        return new SourceScanPage($references, 100);
    }

    public function source(): AssetSource
    {
        return AssetSource::Product;
    }
}

final class InvalidThenValidPageAdapter implements SourceAdapterInterface, SourceAdapterPageInterface
{
    public function __construct(private readonly LocalPathNormalizer $normalizer) {}

    public function scan(int $offset, int $limit): iterable
    {
        yield from $this->scanPage($offset, $limit)->references;
    }

    public function scanPage(int $offset, int $limit): SourceScanPage
    {
        if ($offset === 0) {
            return new SourceScanPage([], 100);
        }
        if ($offset === 100) {
            return new SourceScanPage([
                ImageScanServiceTest::referenceForAdapter(
                    $this->normalizer,
                    '/bilder/spaet.jpg',
                    'artikel:spät',
                ),
            ], 1);
        }

        return new SourceScanPage([], 0);
    }

    public function source(): AssetSource
    {
        return AssetSource::Product;
    }
}
