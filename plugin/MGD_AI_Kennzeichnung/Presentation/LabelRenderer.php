<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Presentation;

use Plugin\MGD_AI_Kennzeichnung\Domain\LabelView;

/**
 * Rendert ein bereits vollständig geprüftes Label-Modell als sicheres HTML.
 *
 * Die Klasse führt keine Datenbankabfrage und keine freie Klassenerzeugung aus.
 * Sämtliche Texte und Klassen stammen aus dem geschlossenen Domainmodell. Auch
 * diese kontrollierten Werte werden an der HTML-Grenze nochmals maskiert.
 */
final class LabelRenderer
{
    public function render(LabelView $view): string
    {
        if (!$view->visible) {
            return '';
        }

        $klassen = implode(' ', [
            'mgd-ai-label',
            'mgd-ai-label--native',
            $view->statusClass,
            $view->positionClass,
            $view->themeClass,
            $view->sourceClass,
        ]);
        $stil = sprintf(
            '--mgd-ai-font-size:%dpx;--mgd-ai-outer-margin:%dpx;--mgd-ai-inner-padding:%dpx;--mgd-ai-border-radius:%dpx;--mgd-ai-blur:%dpx;--mgd-ai-background-opacity:%s',
            $view->fontSize,
            $view->outerMargin,
            $view->innerPadding,
            $view->borderRadius,
            $view->blur,
            $view->backgroundOpacity,
        );

        return sprintf(
            '<span class="%s" role="note" aria-label="%s" style="%s">%s</span>',
            $this->escape($klassen),
            $this->escape($view->assistiveText),
            $this->escape($stil),
            $this->escape($view->visibleText),
        );
    }

    private function escape(string $wert): string
    {
        return htmlspecialchars($wert, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
