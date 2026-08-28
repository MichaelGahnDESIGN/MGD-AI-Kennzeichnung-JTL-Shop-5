<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Service;

use Plugin\MGD_AI_Kennzeichnung\Domain\AssetSource;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelLanguage;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelPosition;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelStatus;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelTheme;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelView;

/**
 * Überführt nicht vertrauenswürdige Einstellungen in ein sicheres Label-Modell.
 *
 * Alle Texte und CSS-Klassen stammen aus geschlossenen Zuordnungen im Quellcode.
 * Freie Eingaben werden ausschließlich normalisiert oder auf sichere Standardwerte
 * zurückgesetzt. Der Resolver erzeugt bewusst kein HTML und lädt keine Quellen.
 */
final class LabelViewResolver
{
    private const DEFAULT_FONT_SIZE = 12;
    private const DEFAULT_OUTER_MARGIN = 8;
    private const DEFAULT_INNER_PADDING = 6;
    private const DEFAULT_BORDER_RADIUS = 4;
    private const DEFAULT_BLUR = 0;
    private const DEFAULT_TRANSPARENCY = 8;

    /**
     * Erstellt das vollständig normalisierte Darstellungsmodell.
     *
     * Die Parameter sind absichtlich mixed, weil Einstellungen aus Formularen,
     * Datenbanken oder Erweiterungspunkten zur Laufzeit untypisiert eintreffen
     * können. Jeder Wert passiert vor der Ausgabe eine geschlossene Prüfung.
     */
    public function resolve(
        mixed $status,
        mixed $position = 'bottom-right',
        mixed $theme = 'auto',
        mixed $language = 'auto',
        mixed $locale = 'en',
        mixed $assetSource = 'unknown',
        mixed $fontSize = self::DEFAULT_FONT_SIZE,
        mixed $outerMargin = self::DEFAULT_OUTER_MARGIN,
        mixed $innerPadding = self::DEFAULT_INNER_PADDING,
        mixed $borderRadius = self::DEFAULT_BORDER_RADIUS,
        mixed $blur = self::DEFAULT_BLUR,
        mixed $transparency = self::DEFAULT_TRANSPARENCY,
    ): LabelView {
        $normalisierterStatus = LabelStatus::fromInput($status);

        if (!$normalisierterStatus->isVisible()) {
            return LabelView::hidden();
        }

        $normalisierteSprache = LabelLanguage::fromInput($language)->resolve($locale);

        return LabelView::forVisibleLabel(
            status: $normalisierterStatus,
            language: $normalisierteSprache,
            position: LabelPosition::fromInput($position),
            theme: LabelTheme::fromInput($theme),
            assetSource: AssetSource::fromInput($assetSource),
            fontSize: $this->boundedInteger($fontSize, self::DEFAULT_FONT_SIZE, 8, 48),
            outerMargin: $this->boundedInteger($outerMargin, self::DEFAULT_OUTER_MARGIN, 0, 64),
            innerPadding: $this->boundedInteger($innerPadding, self::DEFAULT_INNER_PADDING, 0, 32),
            borderRadius: $this->boundedInteger($borderRadius, self::DEFAULT_BORDER_RADIUS, 0, 32),
            blur: $this->boundedInteger($blur, self::DEFAULT_BLUR, 0, 24),
            transparency: $this->boundedInteger($transparency, self::DEFAULT_TRANSPARENCY, 0, 90),
        );
    }

    /**
     * Akzeptiert echte Ganzzahlen und reine Ganzzahl-Strings aus Formularen.
     * Alles andere verwendet den dokumentierten Standardwert. Anschließend wird
     * der Wert auf den fachlich erlaubten Mindest- und Höchstwert begrenzt.
     */
    private function boundedInteger(mixed $input, int $default, int $minimum, int $maximum): int
    {
        if (is_int($input)) {
            $wert = $input;
        } elseif (is_string($input) && preg_match('/^-?\d+$/D', trim($input)) === 1) {
            $wert = (int) trim($input);
        } else {
            $wert = $default;
        }

        return max($minimum, min($maximum, $wert));
    }
}
