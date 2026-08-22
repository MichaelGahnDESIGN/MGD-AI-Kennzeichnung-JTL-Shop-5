<?php

declare(strict_types=1);

namespace Tests\Unit\Admin\Presentation;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Admin\Presentation\AssetDisplayMapper;
use Plugin\MGD_AI_Kennzeichnung\Domain\AssetSource;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelPosition;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelStatus;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelTheme;

final class AssetDisplayMapperTest extends TestCase
{
    #[Test]
    public function uebersetzt_alle_statuswerte_in_feste_deutsche_texte(): void
    {
        $mapper = new AssetDisplayMapper();

        self::assertSame([
            'Ungeprüft',
            'Keine Kennzeichnung',
            'KI-generiert',
            'Teilweise KI-generiert',
            'KI-bearbeitet',
            'Deepfake',
        ], array_map($mapper->statusLabel(...), LabelStatus::cases()));
    }

    #[Test]
    public function uebersetzt_alle_quellen_und_verwendet_einen_neutralen_fallback(): void
    {
        $mapper = new AssetDisplayMapper();

        self::assertSame([
            'Produkt',
            'Kategorie',
            'Hersteller',
            'Banner',
            'OPC',
            'Manuell',
            'Unbekannt',
        ], array_map($mapper->sourceLabel(...), AssetSource::cases()));
        self::assertSame('Unbekannt', $mapper->sourceLabel(AssetSource::fromInput('<script>')));
    }

    #[Test]
    public function uebersetzt_positionen_und_darstellungen_ohne_freie_eingaben(): void
    {
        $mapper = new AssetDisplayMapper();

        self::assertSame(
            ['Oben links', 'Oben rechts', 'Unten links', 'Unten rechts'],
            array_map($mapper->positionLabel(...), LabelPosition::cases()),
        );
        self::assertSame(
            ['Automatisch', 'Hell', 'Dunkel'],
            array_map($mapper->themeLabel(...), LabelTheme::cases()),
        );
    }
}
