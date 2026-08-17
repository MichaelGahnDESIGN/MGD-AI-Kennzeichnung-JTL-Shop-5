<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Domain;

/**
 * Unveränderliches, bereits normalisiertes Darstellungsmodell einer Kennzeichnung.
 *
 * PHP 8.1 unterstützt noch keine readonly-Klassen. Deshalb ist die Klasse final
 * und jede einzelne Eigenschaft ausdrücklich readonly. Das erreicht unter der
 * vereinbarten Mindestversion dieselbe Unveränderlichkeit. Das Modell enthält
 * weder HTML noch frei erzeugte Styles. Der private Konstruktor verhindert, dass
 * Aufrufer ungeprüfte Werte einschleusen. Öffentliche Fabriken akzeptieren nur
 * geschlossene Domainwerte und begrenzen alle Zahlen nochmals im Modell selbst.
 */
final class LabelView
{
    /**
     * Nur die sicheren Fabriken dieser Klasse dürfen ein View-Modell erzeugen.
     */
    private function __construct(
        public readonly bool $visible,
        public readonly string $visibleText,
        public readonly string $assistiveText,
        public readonly string $statusClass,
        public readonly string $positionClass,
        public readonly string $themeClass,
        public readonly string $sourceClass,
        public readonly int $fontSize,
        public readonly int $outerMargin,
        public readonly int $innerPadding,
        public readonly int $borderRadius,
        public readonly int $blur,
    ) {}

    /**
     * Erzeugt eine sichtbare Kennzeichnung aus geschlossenen Domainwerten.
     *
     * Texte und Klassen werden ausschließlich aus internen Positivlisten
     * abgeleitet. Ein unsichtbarer Status führt immer zur vollständig leeren
     * Ausgabe, selbst wenn ein Aufrufer diese Fabrik versehentlich verwendet.
     */
    public static function forVisibleLabel(
        LabelStatus $status,
        LabelLanguage $language,
        LabelPosition $position,
        LabelTheme $theme,
        AssetSource $assetSource,
        int $fontSize,
        int $outerMargin,
        int $innerPadding,
        int $borderRadius,
        int $blur,
    ): self {
        if (!$status->isVisible()) {
            return self::hidden();
        }

        [$sichtbarerText, $assistiverText] = self::textsFor($status, $language);

        return new self(
            visible: true,
            visibleText: $sichtbarerText,
            assistiveText: $assistiverText,
            statusClass: $status->cssClass(),
            positionClass: $position->cssClass(),
            themeClass: $theme->cssClass(),
            sourceClass: $assetSource->cssClass(),
            fontSize: self::boundedInteger($fontSize, 8, 48),
            outerMargin: self::boundedInteger($outerMargin, 0, 64),
            innerPadding: self::boundedInteger($innerPadding, 0, 32),
            borderRadius: self::boundedInteger($borderRadius, 0, 32),
            blur: self::boundedInteger($blur, 0, 24),
        );
    }

    /**
     * Erzeugt die einzige zulässige Repräsentation eines unsichtbaren Labels.
     */
    public static function hidden(): self
    {
        return new self(
            visible: false,
            visibleText: '',
            assistiveText: '',
            statusClass: '',
            positionClass: '',
            themeClass: '',
            sourceClass: '',
            fontSize: 0,
            outerMargin: 0,
            innerPadding: 0,
            borderRadius: 0,
            blur: 0,
        );
    }

    /**
     * Liefert ausschließlich kontrollierte sichtbare und assistive Texte.
     *
     * Der automatische Sprachwert darf auf dieser internen Modellgrenze nicht
     * zu freiem Verhalten führen und verwendet deshalb wie Englisch die
     * englische Positivliste. Der Resolver löst AUTO regulär vorher auf.
     *
     * @return array{string, string}
     */
    private static function textsFor(LabelStatus $status, LabelLanguage $language): array
    {
        if ($language === LabelLanguage::De) {
            return match ($status) {
                LabelStatus::Generated => [
                    'KI-GENERIERT',
                    'Dieser Inhalt wurde vollständig mit künstlicher Intelligenz erzeugt.',
                ],
                LabelStatus::PartiallyGenerated => [
                    'TEILWEISE KI-GENERIERT',
                    'Dieser Inhalt wurde teilweise mit künstlicher Intelligenz erzeugt.',
                ],
                LabelStatus::Modified => [
                    'MIT KI BEARBEITET',
                    'Dieser Inhalt wurde mit künstlicher Intelligenz bearbeitet.',
                ],
                LabelStatus::Deepfake => [
                    'KI-DEEPFAKE',
                    'Dieser Inhalt ist ein mit künstlicher Intelligenz erzeugter oder manipulierter Deepfake.',
                ],
                LabelStatus::Unreviewed, LabelStatus::None => ['', ''],
            };
        }

        return match ($status) {
            LabelStatus::Generated => [
                'AI-GENERATED',
                'This content was generated entirely using artificial intelligence.',
            ],
            LabelStatus::PartiallyGenerated => [
                'PARTIALLY AI-GENERATED',
                'This content was partially generated using artificial intelligence.',
            ],
            LabelStatus::Modified => [
                'AI-MODIFIED',
                'This content was modified using artificial intelligence.',
            ],
            LabelStatus::Deepfake => [
                'AI DEEPFAKE',
                'This content is a deepfake generated or manipulated using artificial intelligence.',
            ],
            LabelStatus::Unreviewed, LabelStatus::None => ['', ''],
        };
    }

    /**
     * Begrenzt einen bereits ganzzahligen Darstellungswert auf seinen sicheren
     * fachlichen Bereich. Diese zweite Prüfung schützt auch direkte Factory-Aufrufe.
     */
    private static function boundedInteger(int $value, int $minimum, int $maximum): int
    {
        return max($minimum, min($maximum, $value));
    }
}
