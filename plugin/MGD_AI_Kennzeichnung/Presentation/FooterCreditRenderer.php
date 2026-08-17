<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Presentation;

/**
 * Rendert die freiwillige Herstellernennung im Footer.
 *
 * Die Funktion nimmt bewusst nur einen booleschen Schalter entgegen. Inhalt,
 * Zieladresse, CSS-Klasse und barrierearme Beschriftung stammen vollständig aus
 * internen Konstanten und können daher nicht durch Shopdaten verändert werden.
 */
final class FooterCreditRenderer
{
    private const URL = 'https://Michael-Gahn.de';
    private const TEXT = 'Plugin von Michael Gahn DESIGN';
    private const ACCESSIBLE_LABEL = 'Plugin von Michael Gahn DESIGN – Herstellerseite in neuem Fenster öffnen';

    /**
     * Liefert bei deaktivierter Nennung garantiert eine vollständig leere Ausgabe.
     */
    public function render(bool $enabled): string
    {
        if (!$enabled) {
            return '';
        }

        $url = htmlspecialchars(self::URL, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        $text = htmlspecialchars(self::TEXT, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        $label = htmlspecialchars(self::ACCESSIBLE_LABEL, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');

        return sprintf(
            '<p class="mgd-ai-footer-credit"><a href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s">%s</a></p>',
            $url,
            $label,
            $text,
        );
    }
}
