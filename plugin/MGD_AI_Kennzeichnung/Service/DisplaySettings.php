<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Service;

use Plugin\MGD_AI_Kennzeichnung\Domain\LabelLanguage;

/**
 * Enthält ausschließlich geprüfte und unveränderliche Anzeigeeinstellungen.
 *
 * Freie CSS-Klassen, HTML oder beliebige Texte sind absichtlich kein Teil
 * dieses Modells. Direkte Anwendungseingaben und die von JTL gelieferten
 * Konfigurationsstrings besitzen getrennte Fabriken, damit Zeichenketten wie
 * „yes“, „1“ oder „12px“ niemals versehentlich als gültig behandelt werden.
 */
final class DisplaySettings
{
    private const DEFAULT_FONT_SIZE = 12;
    private const DEFAULT_OUTER_MARGIN = 8;
    private const DEFAULT_INNER_PADDING = 6;
    private const DEFAULT_BORDER_RADIUS = 4;
    private const DEFAULT_BLUR = 0;
    private const DEFAULT_TRANSPARENCY = 8;

    /**
     * Nur die prüfenden Fabriken dürfen ein Einstellungsmodell erzeugen.
     */
    private function __construct(
        public readonly bool $showCredit,
        public readonly bool $updateNoticesEnabled,
        public readonly LabelLanguage $language,
        public readonly int $fontSize,
        public readonly int $outerMargin,
        public readonly int $innerPadding,
        public readonly int $borderRadius,
        public readonly int $blur,
        public readonly int $transparency,
    ) {}

    /**
     * Prüft bereits typisierte Anwendungseingaben ohne lose Typumwandlung.
     *
     * @param mixed $input Nicht vertrauenswürdige Eingabe, üblicherweise ein Array
     */
    public static function fromInput(mixed $input): self
    {
        $werte = is_array($input) ? $input : [];

        return new self(
            showCredit: self::strictBoolean($werte['showCredit'] ?? null, false),
            updateNoticesEnabled: self::strictBoolean($werte['updateNoticesEnabled'] ?? null, false),
            language: self::language($werte['language'] ?? null),
            fontSize: self::boundedInteger($werte['fontSize'] ?? null, self::DEFAULT_FONT_SIZE, 8, 48),
            outerMargin: self::boundedInteger($werte['outerMargin'] ?? null, self::DEFAULT_OUTER_MARGIN, 0, 64),
            innerPadding: self::boundedInteger($werte['innerPadding'] ?? null, self::DEFAULT_INNER_PADDING, 0, 32),
            borderRadius: self::boundedInteger($werte['borderRadius'] ?? null, self::DEFAULT_BORDER_RADIUS, 0, 32),
            blur: self::boundedInteger($werte['blur'] ?? null, self::DEFAULT_BLUR, 0, 24),
            transparency: self::boundedInteger($werte['transparency'] ?? null, self::DEFAULT_TRANSPARENCY, 0, 90),
        );
    }

    /**
     * Übersetzt ausschließlich die kanonischen, von der info.xml verwendeten
     * JTL-Zeichenketten in typisierte Werte. Andere Werte bleiben bei den
     * datenschutzfreundlichen Standards.
     *
     * @param mixed $input Nicht vertrauenswürdige Plugin-Konfiguration aus JTL
     */
    public static function fromJtlConfig(mixed $input): self
    {
        $werte = is_array($input) ? $input : [];

        return self::fromInput([
            'showCredit' => self::jtlBoolean($werte['show_credit'] ?? null),
            'updateNoticesEnabled' => self::jtlBoolean($werte['update_notices'] ?? null),
            'language' => self::jtlString($werte['language'] ?? null),
            'fontSize' => self::jtlInteger($werte['font_size'] ?? null),
            'outerMargin' => self::jtlInteger($werte['outer_margin'] ?? null),
            'innerPadding' => self::jtlInteger($werte['inner_padding'] ?? null),
            'borderRadius' => self::jtlInteger($werte['border_radius'] ?? null),
            'blur' => self::jtlInteger($werte['blur'] ?? null),
            'transparency' => self::jtlInteger($werte['transparency'] ?? null),
        ]);
    }

    private static function strictBoolean(mixed $value, bool $default): bool
    {
        return is_bool($value) ? $value : $default;
    }

    private static function jtlBoolean(mixed $value): bool
    {
        return $value === 'Y';
    }

    private static function jtlString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }

    private static function jtlInteger(mixed $value): ?int
    {
        if (!is_string($value) || preg_match('/^-?(?:0|[1-9]\d*)$/D', $value) !== 1) {
            return null;
        }

        $ganzzahl = filter_var($value, FILTER_VALIDATE_INT);

        return is_int($ganzzahl) ? $ganzzahl : null;
    }

    private static function language(mixed $value): LabelLanguage
    {
        return is_string($value) ? LabelLanguage::tryFrom($value) ?? LabelLanguage::Auto : LabelLanguage::Auto;
    }

    private static function boundedInteger(mixed $value, int $default, int $minimum, int $maximum): int
    {
        $ganzzahl = is_int($value) ? $value : $default;

        return max($minimum, min($maximum, $ganzzahl));
    }
}
