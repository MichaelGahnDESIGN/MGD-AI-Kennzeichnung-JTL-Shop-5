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
     * Ausschließlich die normalisierten Werte „de“ und „de-DE“ werden als
     * deutscher Kontext anerkannt. Andere regionale Varianten verwenden den
     * bewusst engen und stabilen englischen Fallback.
     */
    public function resolve(mixed $localeContext): self
    {
        if ($this !== self::Auto) {
            return $this;
        }

        if (!is_string($localeContext)) {
            return self::En;
        }

        $normalisiert = strtolower(trim($localeContext));

        return $normalisiert === 'de' || $normalisiert === 'de-de'
            ? self::De
            : self::En;
    }
}
