<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Presentation;

use Plugin\MGD_AI_Kennzeichnung\Domain\AssetSource;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelPosition;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelStatus;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelTheme;

/**
 * Übersetzt geschlossene Domainwerte in feste verständliche Admintexte.
 *
 * Kein Text wird aus Datenbank- oder Formulareingaben zusammengesetzt. Damit
 * bleibt die Oberfläche auch bei einem unbekannten Altwert sicher und lesbar.
 */
final class AssetDisplayMapper
{
    public function statusLabel(LabelStatus $status): string
    {
        return match ($status) {
            LabelStatus::Unreviewed => 'Ungeprüft',
            LabelStatus::None => 'Keine Kennzeichnung',
            LabelStatus::Generated => 'KI-generiert',
            LabelStatus::PartiallyGenerated => 'Teilweise KI-generiert',
            LabelStatus::Modified => 'KI-bearbeitet',
            LabelStatus::Deepfake => 'Deepfake',
        };
    }

    public function sourceLabel(AssetSource $source): string
    {
        return match ($source) {
            AssetSource::Product => 'Produkt',
            AssetSource::Category => 'Kategorie',
            AssetSource::Manufacturer => 'Hersteller',
            AssetSource::Banner => 'Banner',
            AssetSource::Opc => 'OPC',
            AssetSource::CustomLocalManual => 'Manuell',
            AssetSource::Unknown => 'Unbekannt',
        };
    }

    public function positionLabel(LabelPosition $position): string
    {
        return match ($position) {
            LabelPosition::TopLeft => 'Oben links',
            LabelPosition::TopRight => 'Oben rechts',
            LabelPosition::BottomLeft => 'Unten links',
            LabelPosition::BottomRight => 'Unten rechts',
        };
    }

    public function themeLabel(LabelTheme $theme): string
    {
        return match ($theme) {
            LabelTheme::Auto => 'Automatisch',
            LabelTheme::Light => 'Hell',
            LabelTheme::Dark => 'Dunkel',
        };
    }
}
