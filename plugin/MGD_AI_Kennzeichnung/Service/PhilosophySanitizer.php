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

    public function sanitize(mixed $input): string
    {
        if (!is_string($input)) {
            return '';
        }

        $dekodiert = $this->decodeEntities(mb_substr(str_replace("\0", '', $input), 0, 10_000));
        if ($dekodiert === '') {
            return '';
        }

        $dokument = new DOMDocument('1.0', 'UTF-8');
        $vorher = libxml_use_internal_errors(true);
        try {
            $geladen = $dokument->loadHTML(
                '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>'
                . '<div id="mgd-ai-philosophy-root">' . $dekodiert . '</div></body></html>',
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
            $html = $dokument->saveHTML($kind);
            if (is_string($html)) {
                $ausgabe .= $html;
            }
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
                $href = $kind->getAttribute('href');
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
        $teile = parse_url($url);

        return is_array($teile)
            && ($teile['scheme'] ?? null) === 'https'
            && is_string($teile['host'] ?? null)
            && $teile['host'] !== ''
            && !isset($teile['user'])
            && !isset($teile['pass'])
            && (!isset($teile['port']) || $teile['port'] === 443);
    }

    private function decodeEntities(string $text): string
    {
        $dekodiert = $text;
        for ($durchlauf = 0; $durchlauf < 10; ++$durchlauf) {
            $naechster = html_entity_decode(
                $this->decodeNumericTagEntities($dekodiert),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8',
            );
            if ($naechster === $dekodiert) {
                return $dekodiert;
            }
            $dekodiert = $naechster;
        }

        /* Nach zehn Stufen verbliebenes potenzielles Markup wird abgewiesen. */
        $restMarkup = '/&(?:(?:amp|#0*38|#x0*26);)*(?:lt|gt|#0*(?:60|62);?|#x0*(?:3c|3e);?)/iu';

        return preg_match($restMarkup, $dekodiert) === 1 ? '' : $dekodiert;
    }

    /** Dekodiert zusätzlich semikolonlose numerische Winkelklammern. */
    private function decodeNumericTagEntities(string $text): string
    {
        return preg_replace_callback(
            '/&#0*60;?(?![0-9])|&#x0*3c;?|&#0*62;?(?![0-9])|&#x0*3e;?/iu',
            static fn(array $treffer): string => str_contains(strtolower($treffer[0]), '3c')
                || preg_match('/60/', $treffer[0]) === 1 ? '<' : '>',
            $text,
        ) ?? '';
    }
}
