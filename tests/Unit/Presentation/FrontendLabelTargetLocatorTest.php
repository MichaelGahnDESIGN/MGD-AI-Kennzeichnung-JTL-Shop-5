<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Presentation\FrontendLabelTargetLocator;

final class FrontendLabelTargetLocatorTest extends TestCase
{
    #[Test]
    public function erzeugt_exakte_bild_und_hintergrundselektoren(): void
    {
        $locator = new FrontendLabelTargetLocator();

        self::assertSame(
            'img[src="bild.webp"], img[src$="/bild.webp"], '
            . 'img[src*="/bild.webp?"], img[src*="/bild.webp#"]',
            $locator->imageSelector('bild.webp'),
        );
        self::assertSame(
            '[style*="/bild.webp"], [data-image-src="bild.webp"], '
            . '[data-image-src$="/bild.webp"], [data-image-src*="/bild.webp?"], '
            . '[data-image-src*="/bild.webp#"]',
            $locator->backgroundSelector('bild.webp'),
        );
    }

    #[Test]
    public function weist_unsichere_dateinamen_vor_der_selektorerzeugung_zurueck(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Bilddateiname');

        (new FrontendLabelTargetLocator())->imageSelector('bild.webp"] script');
    }
}
