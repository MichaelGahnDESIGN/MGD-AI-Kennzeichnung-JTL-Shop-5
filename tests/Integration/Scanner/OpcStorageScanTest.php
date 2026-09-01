<?php

declare(strict_types=1);

namespace Tests\Integration\Scanner;

require_once __DIR__ . '/../../Stubs/JtlDatabaseStubs.php';

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\AssetRepository;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\UsageRepository;
use Plugin\MGD_AI_Kennzeichnung\Scanner\Adapter\OpcSourceAdapter;
use Plugin\MGD_AI_Kennzeichnung\Scanner\Adapter\OpcStorageSourceAdapter;
use Plugin\MGD_AI_Kennzeichnung\Scanner\Filesystem\OpcStorageFileLister;
use Plugin\MGD_AI_Kennzeichnung\Scanner\Filesystem\OpcStorageRoot;
use Plugin\MGD_AI_Kennzeichnung\Scanner\Filesystem\OpcStorageScanException;
use Plugin\MGD_AI_Kennzeichnung\Scanner\LocalPathNormalizer;
use Plugin\MGD_AI_Kennzeichnung\Scanner\SourceAdapterInterface;
use Plugin\MGD_AI_Kennzeichnung\Service\ImageScanService;
use RuntimeException;
use Tests\Support\OpcStorageFixture;
use Tests\Support\TransactionalDatabaseFake;

/** Verbindet echte Dateiauflistung, OPC-JSON und Repositories mit der transaktionalen Testdatenbank. */
final class OpcStorageScanTest extends TestCase
{
    private OpcStorageFixture $fixture;
    private TransactionalDatabaseFake $db;
    private LocalPathNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->fixture = new OpcStorageFixture();
        $this->normalizer = new LocalPathNormalizer();
        $this->db = new TransactionalDatabaseFake();
        foreach (['xplugin_mgd_ai_asset', 'xplugin_mgd_ai_usage'] as $table) {
            $this->db->setMarker($table, 'mgd-ai-kennzeichnung-jtl-v1');
        }
    }

    protected function tearDown(): void
    {
        $this->fixture->cleanup();
    }

    #[Test]
    public function seiten_und_dateifundstellen_ergeben_eine_karte_und_erhalten_kennzeichnungen(): void
    {
        $this->fixture->file('banner/2026/bild.jpg');
        $this->fixture->file('bilder/2026/bild.jpg');
        $this->opcPage('banner/2026/bild.jpg');
        $path = 'media/image/storage/opc/banner/2026/bild.jpg';
        $key = hash('sha256', $path);
        $assets = new AssetRepository($this->db);
        $assets->upsert($key, $path, 'generated', 'top-left', 'dark');
        $before = $this->db->presentationForAsset($key);
        $pageReference = (new OpcSourceAdapter($this->db, $this->normalizer))->scanPage(0, 100)->references[0];
        $service = $this->service();

        $result = $service->scan();
        self::assertSame(1, $result->createdAssets);
        self::assertSame(3, $result->recordedUsages);
        self::assertSame(2, $this->db->assetCount());
        self::assertSame($before, $this->db->presentationForAsset($key));
        self::assertTrue($this->db->usageIsPresent($pageReference->sourceReference));
        self::assertTrue($this->db->usageIsPresent('opc-datei:' . $key));
        self::assertSame(0, $service->scan()->createdAssets);
        self::assertSame(3, $this->db->usageCount());

        // Der echte Listen-Repository liefert pro Bild einen Datensatz und bindet die bestehenden Filter.
        $rows = $assets->listPage(0, 25, ['source' => 'opc', 'present' => true], 'id', 'asc');
        self::assertCount(2, $rows);
        self::assertSame([2, 1], array_column($rows, 'usage_count'));
        self::assertSame(['opc', 'opc'], array_column($rows, 'source'));
        $second = $assets->listPage(1, 1, ['source' => 'opc'], 'id', 'asc');
        self::assertSame($rows[1]['local_path'], $second[0]['local_path']);

        // Die Testdatenbank ist kein SQL-Parser: Filtervertrag und DISTINCT-Zählung separat prüfen.
        $filters = ['source' => 'opc', 'status' => 'unreviewed', 'present' => true];
        $assets->listPage(25, 25, $filters, 'status', 'desc');
        $listQuery = end($this->db->statements);
        self::assertIsArray($listQuery);
        $assets->countForList($filters);
        $countQuery = end($this->db->statements);
        self::assertIsArray($countQuery);
        self::assertStringContainsString('COUNT(DISTINCT `asset`.`id`)', $countQuery['sql']);
        self::assertStringContainsString('ORDER BY `asset`.`status` DESC', $listQuery['sql']);
        self::assertSame($countQuery['params'], array_diff_key($listQuery['params'], ['limit' => 0, 'offset' => 0]));
        self::assertContains('opc', $countQuery['params']);
        self::assertContains('unreviewed', $countQuery['params']);
        self::assertSame(25, $listQuery['params']['offset']);
    }

    #[Test]
    public function dateiloeschung_laesst_die_weiterhin_gespeicherte_seitenreferenz_vorhanden(): void
    {
        $file = $this->fixture->file('banner/2026/bild.jpg');
        $this->opcPage('banner/2026/bild.jpg');
        $service = $this->service();
        $service->scan();
        $pageReference = (new OpcSourceAdapter($this->db, $this->normalizer))->scanPage(0, 100)->references[0];
        unlink($file);
        $service->scan();
        self::assertFalse($this->db->usageIsPresent('opc-datei:' . $pageReference->assetKey));
        self::assertTrue($this->db->usageIsPresent($pageReference->sourceReference));
        self::assertSame(1, $this->db->assetCount());
    }

    #[Test]
    public function dateiscanfehler_rollt_auch_vorherige_seitenwrites_zurueck(): void
    {
        $path = 'media/image/storage/opc/alt.jpg';
        $key = hash('sha256', $path);
        $this->db->seedScanUsage($key, $path, 'opc-datei:' . $key, 'opc');
        $this->opcPage('neu.jpg');
        $this->fixture->file(str_repeat('a/', 33) . 'bild.jpg');
        try {
            $this->service()->scan();
            self::fail('Ein überschrittener Dateiscan darf keine Teilmenge bestätigen.');
        } catch (OpcStorageScanException $error) {
            self::assertStringNotContainsString($this->fixture->shopRoot, $error->getMessage());
            self::assertNull($error->getPrevious());
        }
        self::assertSame(1, $this->db->rollbacks);
        self::assertSame(0, $this->db->commits);
        self::assertSame(1, $this->db->assetCount());
        self::assertTrue($this->db->usageIsPresent('opc-datei:' . $key));
        self::assertSame(1, $this->db->usageCount());
    }

    #[Test]
    public function doppelte_registrierung_desselben_opc_beitrags_bleibt_verboten(): void
    {
        $page = new OpcSourceAdapter($this->db, $this->normalizer);
        $storage = $this->storage();
        foreach ([[$storage, $storage], [$page, $storage, $page], [$storage, $page, $storage]] as $adapters) {
            try {
                $this->service($adapters)->scan();
                self::fail('Doppelte Beiträge müssen vor dem ersten Datenbankzugriff scheitern.');
            } catch (RuntimeException $error) {
                self::assertStringContainsString('doppelt', $error->getMessage());
            }
        }
        self::assertSame(0, $this->db->begins);
    }

    #[Test]
    public function beide_opc_beitraege_funktionieren_auch_in_umgekehrter_reihenfolge(): void
    {
        $this->fixture->file('bild.jpg');
        $this->opcPage('bild.jpg');
        $result = $this->service([$this->storage(), new OpcSourceAdapter($this->db, $this->normalizer)])->scan();
        self::assertSame(1, $result->createdAssets);
        self::assertSame(2, $result->recordedUsages);
        self::assertSame(1, $this->db->commits);
    }

    private function opcPage(string $path): void
    {
        $this->db->scannerRows['topcpage'] = [(object) [
            'page_id' => 1, 'context' => 'Testseite',
            'areas_json' => json_encode(['properties' => ['src' => $path]], JSON_THROW_ON_ERROR),
        ]];
    }

    private function storage(): OpcStorageSourceAdapter
    {
        return new OpcStorageSourceAdapter(
            new OpcStorageFileLister(new OpcStorageRoot($this->fixture->shopRoot), $this->normalizer),
            $this->normalizer,
        );
    }

    /** @param list<SourceAdapterInterface>|null $adapters */
    private function service(?array $adapters = null): ImageScanService
    {
        return new ImageScanService(
            $adapters ?? [new OpcSourceAdapter($this->db, $this->normalizer), $this->storage()],
            new AssetRepository($this->db),
            new UsageRepository($this->db),
        );
    }
}
