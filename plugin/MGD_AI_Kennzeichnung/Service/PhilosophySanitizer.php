<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Service;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Bereinigt redaktionelle Philosophie-Texte anhand einer kleinen Positivliste.
 *
 * Aktive Inhalte werden vollständig entfernt. Unbekannte reine Formatierungen
 * verlieren ihr Element, behalten aber lesbaren Text. Links dürfen nur per
 * HTTPS, ohne eingebettete Zugangsdaten und ohne freie Attribute erscheinen.
 */
final class PhilosophySanitizer
{
    /** @var list<string> */
    private const ALLOWED_ELEMENTS = ['p', 'h2', 'h3', 'ul', 'ol', 'li', 'strong', 'em', 'a'];

    /** @var list<string> Elemente, deren Inhalt ebenfalls nicht vertrauenswürdig ist */
    private const ACTIVE_ELEMENTS = [
        'script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'template', 'noscript', 'form',
    ];

    /**
     * Benannte HTML5-Referenzen, deren Semikolon historisch fehlen darf.
     *
     * Der Sanitizer benötigt nur die vier HTML-Sonderzeichen, die seine
     * kontrollierte Text- und Attributserialisierung eindeutig abbildet. Die
     * Schreibweise bleibt case-sensitiv; beliebige weitere Namen oder
     * mehrfach kodiertes Markup werden dadurch nicht geöffnet.
     *
     * @var list<string>
     */
    private const HTML5_LEGACY_ENTITY_NAMES = [
        'AMP', 'GT', 'LT', 'QUOT', 'amp', 'gt', 'lt', 'quot',
    ];

    /** Feste Grenze, damit libxml Entities nicht zu aktiver Taggrammatik macht. */
    private const ACTIVE_ENTITY_BOUNDARY = 'x';

    public function sanitize(mixed $input): string
    {
        if (!is_string($input)) {
            return '';
        }

        $begrenzt = mb_substr(str_replace("\0", '', $input), 0, 10_000);
        if ($begrenzt === '') {
            return '';
        }
        $parserSicher = $this->prepareParserInput($begrenzt);

        $dokument = new DOMDocument('1.0', 'UTF-8');
        $vorher = libxml_use_internal_errors(true);
        try {
            $geladen = $dokument->loadHTML(
                '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>'
                . '<div id="mgd-ai-philosophy-root">' . $parserSicher . '</div></body></html>',
                LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING,
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($vorher);
        }

        if (!$geladen) {
            return '';
        }

        $wurzel = $dokument->getElementById('mgd-ai-philosophy-root');
        if (!$wurzel instanceof DOMElement) {
            return '';
        }

        $this->sanitizeChildren($wurzel);
        $ausgabe = '';
        foreach ($this->children($wurzel) as $kind) {
            $ausgabe .= $this->serializeNode($kind);
        }

        return trim($ausgabe);
    }

    private function sanitizeChildren(DOMNode $eltern): void
    {
        foreach ($this->children($eltern) as $kind) {
            if (!$kind instanceof DOMElement) {
                if ($kind->nodeType !== XML_TEXT_NODE) {
                    $eltern->removeChild($kind);
                }

                continue;
            }

            $name = strtolower($kind->tagName);
            if (in_array($name, self::ACTIVE_ELEMENTS, true)) {
                $eltern->removeChild($kind);

                continue;
            }

            $this->sanitizeChildren($kind);
            if (!in_array($name, self::ALLOWED_ELEMENTS, true)) {
                $this->unwrap($kind);

                continue;
            }

            if ($name === 'a') {
                $href = $this->decodeNativeHtmlValue($kind->getAttribute('href'), true);
                if (!$this->isSafeHttpsUrl($href)) {
                    $this->unwrap($kind);

                    continue;
                }

                $this->removeAttributes($kind);
                $kind->setAttribute('href', $href);
                $kind->setAttribute('rel', 'noopener noreferrer');
            } else {
                $this->removeAttributes($kind);
            }
        }
    }

    /** @return list<DOMNode> */
    private function children(DOMNode $eltern): array
    {
        $kinder = [];
        foreach ($eltern->childNodes as $kind) {
            $kinder[] = $kind;
        }

        return $kinder;
    }

    private function unwrap(DOMElement $element): void
    {
        $eltern = $element->parentNode;
        if ($eltern === null) {
            return;
        }

        while ($element->firstChild !== null) {
            $eltern->insertBefore($element->firstChild, $element);
        }
        $eltern->removeChild($element);
    }

    private function removeAttributes(DOMElement $element): void
    {
        while ($element->attributes->length > 0) {
            $attribut = $element->attributes->item(0);
            if ($attribut === null) {
                break;
            }
            $element->removeAttributeNode($attribut);
        }
    }

    private function isSafeHttpsUrl(string $url): bool
    {
        if (!str_starts_with($url, 'https://')
            || str_contains($url, '\\')
            || preg_match('/[\x00-\x20\x7f]/u', $url) === 1
        ) {
            return false;
        }

        $authority = preg_split('/[\/?#]/u', substr($url, strlen('https://')), 2)[0] ?? '';
        if ($authority === ''
            || str_contains($authority, '@')
            || str_contains($authority, '%')
            || !$this->hasSafeRawPortSyntax($authority)
        ) {
            return false;
        }

        $teile = parse_url($url);

        return is_array($teile)
            && ($teile['scheme'] ?? null) === 'https'
            && is_string($teile['host'] ?? null)
            && $teile['host'] !== ''
            && !isset($teile['user'])
            && !isset($teile['pass'])
            && (!isset($teile['port']) || $teile['port'] === 443);
    }

    /** Prüft die Portschreibweise vor jeder Normalisierung durch parse_url(). */
    private function hasSafeRawPortSyntax(string $authority): bool
    {
        if (str_starts_with($authority, '[')) {
            $klammerEnde = strpos($authority, ']');
            if ($klammerEnde === false) {
                return false;
            }

            $ipv6 = substr($authority, 1, $klammerEnde - 1);
            if (filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
                return false;
            }

            $suffix = substr($authority, $klammerEnde + 1);

            return $suffix === '' || $suffix === ':443';
        }

        $ersterDoppelpunkt = strpos($authority, ':');
        if ($ersterDoppelpunkt === false) {
            return true;
        }

        return $ersterDoppelpunkt === strrpos($authority, ':')
            && substr($authority, $ersterDoppelpunkt) === ':443';
    }

    /**
     * Serialisiert ausschließlich den bereits bereinigten Positivlisten-Baum.
     * Dadurch bleiben IPv6-Klammern gültig, ohne nachträgliche Stringersetzung.
     */
    private function serializeNode(DOMNode $node): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            $text = $this->decodeNativeHtmlValue(is_string($node->nodeValue) ? $node->nodeValue : '');

            return htmlspecialchars(
                $text,
                ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_HTML5,
                'UTF-8',
            );
        }

        if (!$node instanceof DOMElement) {
            return '';
        }

        $name = strtolower($node->tagName);
        if (!in_array($name, self::ALLOWED_ELEMENTS, true)) {
            return '';
        }

        $attribute = '';
        if ($name === 'a') {
            $href = $node->getAttribute('href');
            if (!$this->isSafeHttpsUrl($href)) {
                return $this->serializeChildren($node);
            }

            $attribute = ' href="' . htmlspecialchars(
                $href,
                ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5,
                'UTF-8',
            ) . '" rel="noopener noreferrer"';
        }

        return '<' . $name . $attribute . '>' . $this->serializeChildren($node) . '</' . $name . '>';
    }

    private function serializeChildren(DOMNode $parent): string
    {
        $html = '';
        foreach ($this->children($parent) as $child) {
            $html .= $this->serializeNode($child);
        }

        return $html;
    }

    /**
     * Verhindert libxml-spezifische Entity-Dekodierung in aktiver Taggrammatik.
     *
     * HTML5 wertet Entities innerhalb von Tag- und Attributnamen nicht als
     * Struktur aus. libxml tut dies teilweise dennoch. Nur Kandidaten der
     * aktiven Sperrliste werden deshalb vor dem Parser maskiert; ihr gesamter
     * Knoten wird anschließend ohnehin verworfen. Sichtbarer Text und erlaubte
     * Linkattribute behalten ihre native einmalige Entity-Dekodierung.
     */
    private function prepareParserInput(string $html): string
    {
        $ausgabe = '';
        $cursor = 0;
        $laenge = strlen($html);

        while ($cursor < $laenge) {
            $tagStart = strpos($html, '<', $cursor);
            if ($tagStart === false) {
                return $ausgabe . $this->protectNativeEntities(substr($html, $cursor));
            }

            $ausgabe .= $this->protectNativeEntities(substr($html, $cursor, $tagStart - $cursor));
            $tag = $this->readRawTagCandidate($html, $tagStart);
            if ($tag === null) {
                $ausgabe .= '<';
                $cursor = $tagStart + 1;

                continue;
            }

            $roh = substr($html, $tagStart, $tag['end'] - $tagStart);
            $ausgabe .= in_array($tag['name'], self::ACTIVE_ELEMENTS, true)
                ? str_replace('&', self::ACTIVE_ENTITY_BOUNDARY . '&', $roh)
                : $this->protectNativeEntities($roh);
            $cursor = $tag['end'];
        }

        return $ausgabe;
    }

    /** Hält Entities bis zur sicheren Dekodierung im fertigen DOM-Wert inert. */
    private function protectNativeEntities(string $text): string
    {
        return str_replace('&', '&amp;', $text);
    }

    /**
     * Dekodiert einen fertigen DOM-Wert genau einmal nach HTML5-Semantik.
     *
     * PHPs Decoder benötigt bei numerischen und benannten Legacy-Referenzen
     * ein Semikolon, der Browser nicht. Das kontrollierte Ergänzen geschieht
     * erst nach dem Parsen; daraus kann deshalb niemals neue Tag- oder
     * Attributstruktur entstehen.
     */
    private function decodeNativeHtmlValue(string $value, bool $attribut = false): string
    {
        $normalisiert = preg_replace_callback(
            '/&#(?:x[0-9a-f]+|[0-9]+);?/iu',
            static fn(array $treffer): string => str_ends_with($treffer[0], ';')
                ? $treffer[0]
                : $treffer[0] . ';',
            $value,
        ) ?? '';

        $legacyMuster = '/&(' . implode('|', self::HTML5_LEGACY_ENTITY_NAMES) . ')(?!;)/';
        $normalisiert = preg_replace_callback(
            pattern: $legacyMuster,
            callback: static function (array $treffer) use ($attribut, $normalisiert): string {
                $roh = $treffer[0][0];
                $position = $treffer[0][1];
                $folgezeichen = $normalisiert[$position + strlen($roh)] ?? '';

                /*
                 * Eine längere, gültige Referenz mit Semikolon hat Vorrang.
                 * Beispiel: `&ltimes;` darf nicht zu `&lt;imes;` werden.
                 */
                $rest = substr($normalisiert, $position);
                if (preg_match('/^&[A-Za-z0-9]+;/', $rest, $vollstaendig) === 1
                    && html_entity_decode(
                        $vollstaendig[0],
                        ENT_QUOTES | ENT_HTML5,
                        'UTF-8',
                    ) !== $vollstaendig[0]
                ) {
                    return $roh;
                }

                /*
                 * Im Attributkontext bleibt eine semikolonlose Referenz vor
                 * ASCII-Buchstaben, Ziffern oder `=` historisch unverändert.
                 */
                if ($attribut && preg_match('/^[A-Za-z0-9=]$/D', $folgezeichen) === 1) {
                    return $roh;
                }

                return $roh . ';';
            },
            subject: $normalisiert,
            flags: PREG_OFFSET_CAPTURE,
        ) ?? '';

        return html_entity_decode($normalisiert, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /** @return array{end: int, name: string}|null */
    private function readRawTagCandidate(string $html, int $start): ?array
    {
        $laenge = strlen($html);
        $position = $start + 1;
        if (($html[$position] ?? '') === '/') {
            ++$position;
        }

        $nameStart = $position;
        if (preg_match('/^[A-Za-z]$/D', $html[$position] ?? '') !== 1) {
            return null;
        }
        ++$position;
        while (preg_match('/^[A-Za-z0-9_:-]$/D', $html[$position] ?? '') === 1) {
            ++$position;
        }

        $trennzeichen = $html[$position] ?? '';
        if (!in_array($trennzeichen, ["\t", "\n", "\f", "\r", ' ', '/', '>', '&'], true)) {
            return null;
        }

        $name = strtolower(substr($html, $nameStart, $position - $nameStart));
        $quote = null;
        while ($position < $laenge) {
            $zeichen = $html[$position];
            if ($quote !== null) {
                if ($zeichen === $quote) {
                    $quote = null;
                }
            } elseif ($zeichen === '"' || $zeichen === "'") {
                $quote = $zeichen;
            } elseif ($zeichen === '>') {
                return ['end' => $position + 1, 'name' => $name];
            }
            ++$position;
        }

        return ['end' => $laenge, 'name' => $name];
    }
}
