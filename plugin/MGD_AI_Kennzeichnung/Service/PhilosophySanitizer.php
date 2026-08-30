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
        if (!str_starts_with($url, 'https://')
            || str_contains($url, '\\')
            || preg_match('/[\x00-\x20\x7f]/u', $url) === 1
        ) {
            return false;
        }

        $authority = preg_split('/[\/?#]/u', substr($url, strlen('https://')), 2)[0] ?? '';
        if ($authority === ''
            || str_contains($authority, '@')
            || preg_match('/%(?:00|2f|5c|40|3a)/iu', $authority) === 1
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
            return htmlspecialchars(
                is_string($node->nodeValue) ? $node->nodeValue : '',
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
