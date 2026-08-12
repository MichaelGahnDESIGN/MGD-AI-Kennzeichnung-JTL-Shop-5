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
    ): LabelView {
        $normalisierterStatus = LabelStatus::fromInput($status);

        if (!$normalisierterStatus->isVisible()) {
            return $this->emptyView();
        }

        $normalisierteSprache = LabelLanguage::fromInput($language)->resolve($locale);
        [$sichtbarerText, $assistiverText] = $this->textsFor($normalisierterStatus, $normalisierteSprache);

        return new LabelView(
            visible: true,
            visibleText: $sichtbarerText,
            assistiveText: $assistiverText,
            positionClass: LabelPosition::fromInput($position)->cssClass(),
            themeClass: LabelTheme::fromInput($theme)->cssClass(),
            sourceClass: AssetSource::fromInput($assetSource)->cssClass(),
            fontSize: $this->boundedInteger($fontSize, self::DEFAULT_FONT_SIZE, 8, 48),
            outerMargin: $this->boundedInteger($outerMargin, self::DEFAULT_OUTER_MARGIN, 0, 64),
            innerPadding: $this->boundedInteger($innerPadding, self::DEFAULT_INNER_PADDING, 0, 32),
            borderRadius: $this->boundedInteger($borderRadius, self::DEFAULT_BORDER_RADIUS, 0, 32),
            blur: $this->boundedInteger($blur, self::DEFAULT_BLUR, 0, 24),
        );
    }

    /**
     * Unsichtbare Zustände enthalten absichtlich keinerlei darstellbare Daten.
     */
    private function emptyView(): LabelView
    {
        return new LabelView(
            visible: false,
            visibleText: '',
            assistiveText: '',
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
     * Liefert die fest geprüften sichtbaren und assistiven Texte eines Status.
     *
     * @return array{string, string}
     */
    private function textsFor(LabelStatus $status, LabelLanguage $language): array
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
