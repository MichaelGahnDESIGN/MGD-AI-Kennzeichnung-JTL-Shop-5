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
}
