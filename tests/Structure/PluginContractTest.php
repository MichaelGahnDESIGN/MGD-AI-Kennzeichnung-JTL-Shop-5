<?php

declare(strict_types=1);

namespace Tests\Structure;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PluginContractTest extends TestCase
{
    /** Stabiler Projektpfad, unabhängig vom aktuellen Arbeitsverzeichnis des Testaufrufs. */
    private const ROOT = __DIR__ . '/../..';

    #[Test]
    public function pluginvertrag_enthaelt_die_verbindlichen_metadaten_und_lizenzangaben(): void
    {
        $infoPfad = self::ROOT . '/plugin/MGD_AI_Kennzeichnung/info.xml';
        self::assertFileExists(
            $infoPfad,
            'Die info.xml des Plugins muss am vereinbarten Pfad vorhanden sein.',
        );

        $dokument = new DOMDocument();
        self::assertTrue(
            $dokument->load($infoPfad),
            'Die info.xml muss wohlgeformtes XML enthalten.',
        );

        $xpath = new DOMXPath($dokument);
        self::assertSame(
            'MGD_AI_Kennzeichnung',
            $this->liesXmlWert($xpath, 'PluginID'),
            'Die PluginID muss exakt MGD_AI_Kennzeichnung lauten.',
        );
        self::assertSame(
            '5.7.2',
            $this->liesXmlWert($xpath, 'MinShopVersion'),
            'Die minimale JTL-Shop-Version muss exakt 5.7.2 sein.',
        );
        self::assertSame(
            '1.0.0',
            $this->liesXmlWert($xpath, 'Version'),
            'Die Pluginversion muss exakt 1.0.0 sein.',
        );

        $lizenzPfad = self::ROOT . '/LICENSE';
        self::assertFileExists(
            $lizenzPfad,
            'Die Lizenzdatei muss im Projektstamm vorhanden sein.',
        );

        $lizenz = file_get_contents($lizenzPfad);
        self::assertIsString($lizenz, 'Die Lizenzdatei muss lesbar sein.');
        self::assertStringContainsString(
            'SPDX-License-Identifier: GPL-3.0-or-later',
            $lizenz,
            'Die Lizenzdatei muss den SPDX-Ausdruck GPL-3.0-or-later enthalten.',
        );
        self::assertStringContainsString(
            'Copyright (C) 2026 Michael Gahn DESIGN',
            $lizenz,
            'Die Lizenzdatei muss den vereinbarten Copyright-Hinweis enthalten.',
        );
    }

    /**
     * Liest einen einzelnen verpflichtenden Textwert aus der info.xml.
     */
    private function liesXmlWert(DOMXPath $xpath, string $elementName): string
    {
        $wert = $xpath->evaluate(sprintf('string(//*[local-name()="%s"])', $elementName));
        self::assertIsString(
            $wert,
            sprintf('Das XML-Element %s muss einen Textwert enthalten.', $elementName),
        );

        return trim($wert);
    }
}
