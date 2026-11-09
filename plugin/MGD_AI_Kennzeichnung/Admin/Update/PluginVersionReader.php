<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Update;

use DOMDocument;
use DOMXPath;
use RuntimeException;

// Version aus info.xml, nie fest einkodiert
final class PluginVersionReader
{
    public static function read(string $infoXmlPath): string
    {
        $raw = is_file($infoXmlPath) ? file_get_contents($infoXmlPath) : false;
        if (!is_string($raw) || $raw === '') {
            throw new RuntimeException('info.xml nicht lesbar.');
        }
        $doc = new DOMDocument();
        if (!$doc->loadXML($raw, LIBXML_NONET)) {
            throw new RuntimeException('info.xml ist kein gültiges XML.');
        }
        // nur das Version-Element direkt unter jtlshopplugin
        $nodes = (new DOMXPath($doc))->query('/jtlshopplugin/Version');
        $node = $nodes !== false && $nodes->length === 1 ? $nodes->item(0) : null;
        $version = $node instanceof \DOMElement ? trim($node->textContent) : '';
        if (preg_match('/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)$/D', $version) !== 1) {
            throw new RuntimeException('Plugin-Version in info.xml ungültig.');
        }

        return $version;
    }
}
