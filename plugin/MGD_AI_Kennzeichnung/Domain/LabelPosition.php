<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Domain;

/**
 * Erlaubte Positionen einer Kennzeichnung innerhalb eines Inhaltselements.
 *
 * Die CSS-Klasse wird niemals aus Benutzereingaben zusammengesetzt. Jede
 * Position besitzt stattdessen eine fest im Quellcode definierte Klasse.
 */
enum LabelPosition: string
{
    case TopLeft = 'top-left';
    case TopRight = 'top-right';
    case BottomLeft = 'bottom-left';
    case BottomRight = 'bottom-right';

    /**
     * Nutzt unten rechts als unauffälligen und sicheren Standardwert.
     */
    public static function fromInput(mixed $input): self
    {
        if ($input instanceof self) {
            return $input;
        }

        if (!is_string($input)) {
            return self::BottomRight;
        }

        return self::tryFrom(strtolower(trim($input))) ?? self::BottomRight;
    }

    /**
     * Liefert ausschließlich eine fest erlaubte CSS-Klasse.
     */
    public function cssClass(): string
    {
        return match ($this) {
            self::TopLeft => 'mgd-ai-label--position-top-left',
            self::TopRight => 'mgd-ai-label--position-top-right',
            self::BottomLeft => 'mgd-ai-label--position-bottom-left',
            self::BottomRight => 'mgd-ai-label--position-bottom-right',
        };
    }
}
