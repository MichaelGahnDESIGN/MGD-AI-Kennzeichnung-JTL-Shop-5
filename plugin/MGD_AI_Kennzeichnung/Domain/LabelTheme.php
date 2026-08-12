<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Domain;

/**
 * Geschlossene Auswahl der optischen Grundthemen einer Kennzeichnung.
 */
enum LabelTheme: string
{
    case Auto = 'auto';
    case Light = 'light';
    case Dark = 'dark';

    /**
     * Fällt bei unbekannten Werten auf die automatische Darstellung zurück.
     */
    public static function fromInput(mixed $input): self
    {
        if ($input instanceof self) {
            return $input;
        }

        if (!is_string($input)) {
            return self::Auto;
        }

        return self::tryFrom(strtolower(trim($input))) ?? self::Auto;
    }

    /**
     * Liefert die fest zugeordnete CSS-Klasse des gewählten Grundthemas.
     */
    public function cssClass(): string
    {
        return match ($this) {
            self::Auto => 'mgd-ai-label--theme-auto',
            self::Light => 'mgd-ai-label--theme-light',
            self::Dark => 'mgd-ai-label--theme-dark',
        };
    }
}
