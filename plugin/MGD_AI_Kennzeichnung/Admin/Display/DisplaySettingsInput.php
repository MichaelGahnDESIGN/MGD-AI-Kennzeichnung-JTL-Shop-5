<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Display;

use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;

/**
 * Prüft die kleine, feste POST-Struktur für visuelle Anzeigeoptionen strikt.
 *
 * Dadurch gelangen weder CSS-Fragmente noch implizite PHP-Typumwandlungen in
 * die Plugin-Konfiguration. Das Objekt ist nach der Prüfung unveränderlich.
 */
final class DisplaySettingsInput
{
    /** @var list<string> */
    private const FIELDS = [
        'language',
        'font_size',
        'outer_margin',
        'inner_padding',
        'border_radius',
        'blur',
        'transparency',
    ];

    private function __construct(
        public readonly string $language,
        public readonly int $fontSize,
        public readonly int $outerMargin,
        public readonly int $innerPadding,
        public readonly int $borderRadius,
        public readonly int $blur,
        public readonly int $transparency,
    ) {}

    /** @param mixed $post Nicht vertrauenswürdiger HTTP-POST-Payload */
    public static function fromPost(mixed $post): self
    {
        if (!is_array($post) || count($post) !== count(self::FIELDS)
            || array_diff(array_keys($post), self::FIELDS) !== []
            || array_diff(self::FIELDS, array_keys($post)) !== []
        ) {
            throw new ValidationException('Die Darstellungseinstellungen enthalten ungültige Felder.');
        }

        $language = $post['language'];
        if (!is_string($language) || !in_array($language, ['auto', 'de', 'en'], true)) {
            throw new ValidationException('language ist ungültig.');
        }

        return new self(
            $language,
            self::integer('font_size', $post['font_size'], 8, 48),
            self::integer('outer_margin', $post['outer_margin'], 0, 64),
            self::integer('inner_padding', $post['inner_padding'], 0, 32),
            self::integer('border_radius', $post['border_radius'], 0, 32),
            self::integer('blur', $post['blur'], 0, 24),
            self::integer('transparency', $post['transparency'], 0, 90),
        );
    }

    /** @return array<string, string> */
    public function toJtlConfig(): array
    {
        return [
            'language' => $this->language,
            'font_size' => (string) $this->fontSize,
            'outer_margin' => (string) $this->outerMargin,
            'inner_padding' => (string) $this->innerPadding,
            'border_radius' => (string) $this->borderRadius,
            'blur' => (string) $this->blur,
            'transparency' => (string) $this->transparency,
        ];
    }

    private static function integer(string $name, mixed $value, int $minimum, int $maximum): int
    {
        if (!is_string($value) || preg_match('/^(?:0|[1-9]\\d*)$/D', $value) !== 1) {
            throw new ValidationException(sprintf('%s muss eine Ganzzahl sein.', $name));
        }
        $integer = filter_var($value, FILTER_VALIDATE_INT);
        if (!is_int($integer) || $integer < $minimum || $integer > $maximum) {
            throw new ValidationException(sprintf('%s liegt außerhalb des sicheren Bereichs.', $name));
        }
        return $integer;
    }
}
