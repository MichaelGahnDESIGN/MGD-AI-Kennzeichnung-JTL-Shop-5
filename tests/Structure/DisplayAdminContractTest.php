<?php

declare(strict_types=1);

namespace Tests\Structure;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Prüft die technische Eignung des lokalen Vorschaubildes im Administrationsbereich. */
final class DisplayAdminContractTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../';

    #[Test]
    public function lokales_vorschaubild_ist_ein_kompaktes_png_mit_ausreichender_aufloesung(): void
    {
        $image = self::ROOT . '/plugin/MGD_AI_Kennzeichnung/adminmenu/images/michael-gahn-design-schuh.png';
        self::assertFileExists($image);

        $dateigroesse = filesize($image);
        self::assertIsInt($dateigroesse);
        self::assertLessThanOrEqual(2_000_000, $dateigroesse);

        $size = getimagesize($image);
        self::assertIsArray($size);
        self::assertSame('image/png', $size['mime'] ?? null);
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

    /**
     * Liest ausschließlich die PNG-Chunk-Kopfzeilen, ohne Bilddaten zu durchsuchen oder zu dekodieren.
     *
     * @return list<string>
     */
    private function lesePngChunkTypen(string $image): array
    {
        $binary = file_get_contents($image);
        self::assertIsString($binary);
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
            $chunkTypes[] = $chunkType;

            $offset += 12 + $chunkLaenge['length'];
            self::assertLessThanOrEqual($laenge, $offset, 'Der PNG-Chunk ist unvollständig.');

            if ($chunkType === 'IEND') {
                break;
            }
        }

        self::assertContains('IEND', $chunkTypes, 'Die PNG-Datei muss mit einem IEND-Chunk enden.');

        return $chunkTypes;
    }
}
