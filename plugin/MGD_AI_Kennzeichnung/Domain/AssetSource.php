<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Domain;

/**
 * Positivliste der unterstützten Herkunftsbereiche eines gekennzeichneten Assets.
 *
 * „custom-local-manual“ steht eindeutig für manuell im eigenen Shop verwaltete
 * Inhalte.
 * „unknown“ ist ein neutraler technischer Fallback und erzeugt niemals eine aus
 * einer freien Eingabe abgeleitete Klasse oder externe Quelle.
 */
enum AssetSource: string
{
    case Product = 'product';
    case Category = 'category';
    case Manufacturer = 'manufacturer';
    case Banner = 'banner';
    case Opc = 'opc';
    case CustomLocalManual = 'custom-local-manual';
    case Unknown = 'unknown';

    /**
     * Ordnet beliebige Eingaben ausschließlich einem bekannten Quellenwert zu.
     */
    public static function fromInput(mixed $input): self
    {
        if ($input instanceof self) {
            return $input;
        }

        if (!is_string($input)) {
            return self::Unknown;
        }

        return self::tryFrom(strtolower(trim($input))) ?? self::Unknown;
    }

    /**
     * Liefert eine feste, lokale CSS-Klasse für die normalisierte Quelle.
     */
    public function cssClass(): string
    {
        return match ($this) {
            self::Product => 'mgd-ai-label--source-product',
            self::Category => 'mgd-ai-label--source-category',
            self::Manufacturer => 'mgd-ai-label--source-manufacturer',
            self::Banner => 'mgd-ai-label--source-banner',
            self::Opc => 'mgd-ai-label--source-opc',
            self::CustomLocalManual => 'mgd-ai-label--source-custom-local-manual',
            self::Unknown => 'mgd-ai-label--source-unknown',
        };
    }
}
