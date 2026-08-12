<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Domain;

/**
 * Unterstützte Sprachen für sichtbare und assistive Kennzeichnungstexte.
 */
enum LabelLanguage: string
{
    case Auto = 'auto';
    case De = 'de';
    case En = 'en';

    /**
     * Akzeptiert nur die ausdrücklich unterstützten Sprachwerte.
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
     * Löst die automatische Sprache anhand des Shop- oder Seitenkontexts auf.
     *
     * Deutsche Varianten wie de, de-DE und de_CH werden erkannt. Jeder andere
     * oder technisch ungültige Kontext verwendet als stabilen Fallback Englisch.
     */
    public function resolve(mixed $localeContext): self
    {
        if ($this !== self::Auto) {
            return $this;
        }

        if (!is_string($localeContext)) {
            return self::En;
        }

        $normalisiert = str_replace('_', '-', strtolower(trim($localeContext)));

        return $normalisiert === 'de' || str_starts_with($normalisiert, 'de-')
            ? self::De
            : self::En;
    }
}
