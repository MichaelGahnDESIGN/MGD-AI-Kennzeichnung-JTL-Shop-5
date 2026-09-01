<?php

declare(strict_types=1);

namespace Tests\Unit\Scanner;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Domain\AssetSource;
use Plugin\MGD_AI_Kennzeichnung\Scanner\Adapter\OpcStorageSourceAdapter;
use Plugin\MGD_AI_Kennzeichnung\Scanner\Filesystem\OpcStorageFileLister;
use Plugin\MGD_AI_Kennzeichnung\Scanner\Filesystem\OpcStorageRoot;
use Plugin\MGD_AI_Kennzeichnung\Scanner\Filesystem\OpcStorageScanException;
use Plugin\MGD_AI_Kennzeichnung\Scanner\LocalPathNormalizer;
use Tests\Support\OpcStorageFixture;

/** Prüft reale temporäre Ordner statt nachgebauter Dateisystem-Mocks. */
final class OpcStorageSourceAdapterTest extends TestCase
{
    private OpcStorageFixture $fixture;

    protected function setUp(): void
    {
        $this->fixture = new OpcStorageFixture();
    }

    protected function tearDown(): void
    {
        $this->fixture->cleanup();
    }

    #[Test]
    public function findet_root_unbenutzte_bilder_und_tiefe_ordner_deterministisch(): void
    {
        $paths = ['z.jpg', 'banner/2026/bild.JPEG', 'bilder/Äußere Ecke/eins/zwei/drei/bild.png',
            'bilder/a.webp', 'bilder/b.gif', 'bilder/c.avif', 'a.jpg'];
        foreach (array_merge($paths, ['a.svg', 'a.mp4', 'a.php', 'bild.jpg.php', 'info.txt']) as $path) {
            $this->fixture->file($path);
        }
        $page = $this->adapter()->scanPage(0, 100);
        sort($paths, SORT_STRING);
        self::assertSame(count($paths), $page->rowsRead);
        self::assertSame(
            array_map(static fn(string $p): string => 'media/image/storage/opc/' . $p, $paths),
            array_column($page->references, 'localPath'),
        );
        foreach ($page->references as $reference) {
            self::assertSame(AssetSource::Opc, $reference->source);
            self::assertSame('OPC-Dateispeicher', $reference->context);
            self::assertSame('opc-datei:' . hash('sha256', $reference->localPath), $reference->sourceReference);
            self::assertSame(hash('sha256', $reference->localPath), $reference->assetKey);
        }
    }

    #[Test]
    public function paginiert_eine_auflistung_und_baut_sie_erst_beim_naechsten_scan_neu_auf(): void
    {
        for ($i = 0; $i < 205; ++$i) {
            $this->fixture->file(sprintf('bild-%03d.jpg', $i));
        }
        $adapter = $this->adapter();
        self::assertSame(100, $adapter->scanPage(0, 100)->rowsRead);
        $new = $this->fixture->file('neu.jpg');
        self::assertSame(100, $adapter->scanPage(100, 100)->rowsRead);
        self::assertSame(5, $adapter->scanPage(200, 100)->rowsRead);
        self::assertSame(0, $adapter->scanPage(205, 100)->rowsRead);
        $adapter->scanPage(0, 100);
        self::assertSame(6, $adapter->scanPage(200, 100)->rowsRead);
        unlink($new);
        $adapter->scanPage(0, 100);
        self::assertSame(5, $adapter->scanPage(200, 100)->rowsRead);
    }

    #[Test]
    public function ueberspringt_symlinks_auch_schleifen_und_links_ausserhalb(): void
    {
        $file = $this->fixture->file('echt/bild.jpg');
        symlink($file, $this->fixture->storageRoot . '/dateilink.jpg');
        symlink($this->fixture->storageRoot, $this->fixture->storageRoot . '/echt/schleife');
        symlink($this->fixture->shopRoot, $this->fixture->storageRoot . '/aussen');
        symlink($this->fixture->shopRoot . '/fehlt.jpg', $this->fixture->storageRoot . '/kaputt.jpg');
        self::assertSame(1, $this->adapter()->scanPage(0, 100)->rowsRead);
    }

    #[Test]
    public function leerer_speicher_ist_gueltig_fehlender_speicher_aber_ein_fehler(): void
    {
        $adapter = $this->adapter();
        self::assertSame(0, $adapter->scanPage(0, 100)->rowsRead);
        rmdir($this->fixture->storageRoot);
        $this->expectException(OpcStorageScanException::class);
        $adapter->scanPage(0, 100);
    }

    #[Test]
    public function verweigert_symlink_wurzel_auch_zu_aehnlich_benanntem_nachbarordner(): void
    {
        rmdir($this->fixture->storageRoot);
        mkdir($this->fixture->storageRoot . '-extern');
        symlink($this->fixture->storageRoot . '-extern', $this->fixture->storageRoot);
        $this->expectException(OpcStorageScanException::class);
        $this->adapter()->scanPage(0, 100);
    }

    #[Test]
    public function verweigert_symlink_in_einem_festen_uebergeordneten_speichersegment(): void
    {
        rename($this->fixture->shopRoot . '/media', $this->fixture->shopRoot . '/verschoben');
        symlink($this->fixture->shopRoot . '/verschoben', $this->fixture->shopRoot . '/media');
        $this->expectException(OpcStorageScanException::class);
        $this->adapter()->scanPage(0, 100);
    }

    #[Test]
    public function unlesbarer_unterordner_ist_kein_erfolgreicher_teilscan(): void
    {
        $file = $this->fixture->file('gesperrt/bild.jpg');
        $directory = dirname($file);
        chmod($directory, 0000);
        try {
            clearstatcache();
            if (is_readable($directory)) {
                self::markTestSkipped('Der ausführende Benutzer umgeht Dateiberechtigungen.');
            }
            $this->expectException(OpcStorageScanException::class);
            $this->adapter()->scanPage(0, 100);
        } finally {
            chmod($directory, 0700);
        }
    }

    #[Test]
    public function erlaubt_32_ebenen_und_verweigert_33(): void
    {
        $this->fixture->file(str_repeat('a/', 32) . 'bild.jpg');
        self::assertSame(1, $this->adapter()->scanPage(0, 100)->rowsRead);
        $this->fixture->file(str_repeat('a/', 33) . 'bild.jpg');
        $this->expectException(OpcStorageScanException::class);
        $this->expectExceptionMessage('32');
        $this->adapter()->scanPage(0, 100);
    }

    #[Test]
    public function erlaubt_genau_9999_bilder_und_verweigert_10000_vor_der_ersten_seite(): void
    {
        for ($i = 0; $i < 9999; ++$i) {
            $this->fixture->file(sprintf('%05d.jpg', $i));
        }
        $adapter = $this->adapter();
        $paths = [];
        for ($offset = 0; $offset < 10000; $offset += 100) {
            $page = $adapter->scanPage($offset, 100);
            array_push($paths, ...array_column($page->references, 'localPath'));
        }
        self::assertCount(9999, array_unique($paths));
        self::assertSame(99, $page->rowsRead);
        $this->fixture->file('mehr.jpg');
        $this->expectException(OpcStorageScanException::class);
        $this->expectExceptionMessage('9.999');
        $adapter->scanPage(0, 100);
    }

    #[Test]
    public function auch_nichtbilder_zaehlen_zur_grenze_von_20000_eintraegen(): void
    {
        for ($i = 0; $i < 20000; ++$i) {
            $this->fixture->file(sprintf('%05d.txt', $i));
        }
        $adapter = $this->adapter();
        self::assertSame(0, $adapter->scanPage(0, 100)->rowsRead);
        $this->fixture->file('mehr.txt');
        $this->expectException(OpcStorageScanException::class);
        $this->expectExceptionMessage('20.000');
        $adapter->scanPage(0, 100);
    }

    #[Test]
    public function normalisierung_darf_keine_andere_datei_als_den_tatsaechlichen_upload_adressieren(): void
    {
        $this->fixture->file('bild%20eins.jpg');
        $this->fixture->file('bild eins.jpg');
        $this->expectException(OpcStorageScanException::class);
        $this->adapter()->scanPage(0, 100);
    }

    #[Test]
    #[DataProvider('ungueltigeSeitengrenzen')]
    public function verweigert_ungueltige_seiten_ohne_dateisystemzugriff(int $offset, int $limit): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->adapter()->scanPage($offset, $limit);
    }

    /** @return iterable<array{int, int}> */
    public static function ungueltigeSeitengrenzen(): iterable
    {
        yield [-1, 100];
        yield [0, 0];
        yield [0, 101];
        yield [100, 100];
    }

    private function adapter(): OpcStorageSourceAdapter
    {
        $normalizer = new LocalPathNormalizer();

        return new OpcStorageSourceAdapter(
            new OpcStorageFileLister(new OpcStorageRoot($this->fixture->shopRoot), $normalizer),
            $normalizer,
        );
    }
}
