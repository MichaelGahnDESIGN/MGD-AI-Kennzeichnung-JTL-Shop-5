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
            'tartikelpict' => [(object) ['local_path' => '/media/image/storage/a.jpg', 'source_reference' => 'artikel:1:bild:1', 'context' => 'Artikel']],
            'tkategoriepict' => [(object) ['local_path' => '/media/storage/categories/k.png', 'source_reference' => 'kategorie:2', 'context' => 'Kategorie']],
            'thersteller' => [(object) ['local_path' => '/bilder/h.webp', 'source_reference' => 'hersteller:3', 'context' => 'Hersteller']],
            'tbanner' => [(object) ['local_path' => '/bilder/b.gif', 'source_reference' => 'banner:4', 'context' => 'Banner']],
            'topcarea' => [(object) ['local_path' => '/bilder/o.avif', 'source_reference' => 'opc:5', 'context' => 'OPC']],
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
            $rows = self::collect($adapter->scan(0, 100));
            self::assertCount(1, $rows);
            self::assertInstanceOf(LocalImageReference::class, $rows[0]);
        }

        foreach (array_slice($db->statements, -5) as $statement) {
            self::assertSame(['offset' => 0, 'limit' => 100], $statement['params']);
            self::assertStringContainsString(':offset', $statement['sql']);
            self::assertStringContainsString(':limit', $statement['sql']);
            self::assertStringNotContainsString('fremd.example', $statement['sql']);
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
        self::assertSame([[0, 100], [100, 100], [200, 100], [0, 100], [100, 100], [200, 100]], $adapter->calls);
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
        self::assertTrue($db->usageIsPresent('artikel:1'));
        self::assertTrue($db->usageIsPresent('manuell:1'));
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

    /**
     * @param iterable<mixed> $values
     * @return list<mixed>
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
final class RecordingAdapter implements SourceAdapterInterface
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

    public function source(): AssetSource
    {
        return $this->assetSource;
    }
}

final class ThrowingAdapter implements SourceAdapterInterface
{
    public function scan(int $offset, int $limit): iterable
    {
        throw new RuntimeException('Erzwungener Scannerfehler.');
    }

    public function source(): AssetSource
    {
        return AssetSource::Banner;
    }
}

final class RepeatingPageAdapter implements SourceAdapterInterface
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
}
