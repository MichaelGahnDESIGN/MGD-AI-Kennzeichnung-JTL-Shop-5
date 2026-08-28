<?php

declare(strict_types=1);

namespace Tests\Structure;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

/** Prüft die technische Eignung des lokalen Vorschaubildes im Administrationsbereich. */
final class DisplayAdminContractTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../';
    private const MAXIMALE_DATEIGROESSE = 2_000_000;

    #[Test]
    public function lokales_vorschaubild_ist_ein_kompaktes_png_mit_ausreichender_aufloesung(): void
    {
        $image = self::ROOT . '/plugin/MGD_AI_Kennzeichnung/adminmenu/images/michael-gahn-design-schuh.png';
        self::assertFileExists($image);

        $dateigroesse = filesize($image);
        self::assertIsInt($dateigroesse);
        self::assertLessThanOrEqual(self::MAXIMALE_DATEIGROESSE, $dateigroesse);

        $size = getimagesize($image);
        self::assertIsArray($size);
        self::assertSame('image/png', $size['mime']);
        self::assertGreaterThanOrEqual(800, $size[0]);
        self::assertGreaterThanOrEqual(800, $size[1]);
    }

    #[Test]
    public function lokales_vorschaubild_enthaelt_keine_generierungs_oder_textmetadaten(): void
    {
        $image = self::ROOT . '/plugin/MGD_AI_Kennzeichnung/adminmenu/images/michael-gahn-design-schuh.png';
        self::assertFileExists($image);

        $chunkTypes = $this->lesePngChunkTypen($image);

        foreach (['caBX', 'eXIf', 'iTXt', 'tEXt', 'zTXt'] as $verbotenerChunkTyp) {
            self::assertNotContains(
                $verbotenerChunkTyp,
                $chunkTypes,
                sprintf('Das Vorschaubild darf keinen %s-Metadatenchunk enthalten.', $verbotenerChunkTyp),
            );
        }
    }

    #[Test]
    public function png_vertrag_lehnt_unbekannten_ancillary_chunk_mit_generator_url_ab(): void
    {
        $png = $this->erstelleSynthetischesPng([
            ['ruLE', 'https://generator.example.invalid/manifest'],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->pruefePngChunkStruktur($png);
    }

    #[Test]
    public function png_vertrag_lehnt_daten_hinter_dem_iend_chunk_ab(): void
    {
        $png = $this->erstelleSynthetischesPng([], 'angehaengte-daten');

        $this->expectException(AssertionFailedError::class);
        $this->pruefePngChunkStruktur($png);
    }

    /**
     * Liest eine maximal 2 MB große PNG-Datei und prüft ausschließlich ihre Chunk-Kopfzeilen.
     *
     * @return list<string>
     */
    private function lesePngChunkTypen(string $image): array
    {
        $dateigroesse = filesize($image);
        self::assertIsInt($dateigroesse);
        self::assertLessThanOrEqual(self::MAXIMALE_DATEIGROESSE, $dateigroesse);

        $binary = file_get_contents($image);
        self::assertIsString($binary);

        return $this->pruefePngChunkStruktur($binary);
    }

    /**
     * Prüft eine maximal 2 MB große PNG-Bytefolge ausschließlich anhand ihrer Chunk-Kopfzeilen.
     *
     * @return list<string>
     */
    private function pruefePngChunkStruktur(string $binary): array
    {
        self::assertLessThanOrEqual(self::MAXIMALE_DATEIGROESSE, strlen($binary));
        self::assertStringStartsWith("\x89PNG\r\n\x1a\n", $binary);

        $offset = 8;
        $laenge = strlen($binary);
        $chunkTypes = [];

        while ($offset + 12 <= $laenge) {
            $chunkLaenge = unpack('Nlength', substr($binary, $offset, 4));
            self::assertIsArray($chunkLaenge);
            self::assertIsInt($chunkLaenge['length'] ?? null);

            $chunkType = substr($binary, $offset + 4, 4);
            self::assertSame(4, strlen($chunkType));
            self::assertContains(
                $chunkType,
                ['IHDR', 'IDAT', 'IEND'],
                sprintf('Der PNG-Chunktyp %s ist im minimalen Vorschaubild nicht erlaubt.', $chunkType),
            );
            $chunkTypes[] = $chunkType;

            $offset += 12 + $chunkLaenge['length'];
            self::assertLessThanOrEqual($laenge, $offset, 'Der PNG-Chunk ist unvollständig.');

            if ($chunkType === 'IEND') {
                self::assertSame(0, $chunkLaenge['length'], 'Der IEND-Chunk darf keine Daten enthalten.');
                self::assertSame($laenge, $offset, 'Nach dem IEND-Chunk dürfen keine Daten folgen.');
                break;
            }
        }

        self::assertContains('IEND', $chunkTypes, 'Die PNG-Datei muss mit einem IEND-Chunk enden.');

        return $chunkTypes;
    }

    /**
     * Erstellt eine minimale, nur für die Strukturprüfung geeignete PNG-Bytefolge.
     * Die CRC-Werte sind für den getesteten Chunk-Vertrag absichtlich nicht relevant.
     *
     * @param list<array{0: string, 1: string}> $zusaetzlicheChunks
     */
    private function erstelleSynthetischesPng(array $zusaetzlicheChunks, string $datenNachIend = ''): string
    {
        $header = "\x89PNG\r\n\x1a\n";
        $ihdr = $this->erstellePngChunk('IHDR', pack('NNCCCCC', 1, 1, 8, 2, 0, 0, 0));
        $idat = $this->erstellePngChunk('IDAT', '');
        $iend = $this->erstellePngChunk('IEND', '');
        $chunks = '';

        foreach ($zusaetzlicheChunks as [$typ, $daten]) {
            $chunks .= $this->erstellePngChunk($typ, $daten);
        }

        return $header . $ihdr . $chunks . $idat . $iend . $datenNachIend;
    }

    /** Erstellt einen PNG-Chunk mit einem für diesen Strukturtest nicht ausgewerteten CRC-Platzhalter. */
    private function erstellePngChunk(string $typ, string $daten): string
    {
        self::assertSame(4, strlen($typ));

        return pack('N', strlen($daten)) . $typ . $daten . pack('N', 0);
    }
}
