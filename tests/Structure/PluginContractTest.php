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
            '0.1.0',
            $this->liesXmlWert($xpath, 'Version'),
            'Die Pluginversion muss exakt 0.1.0 sein.',
        );
        self::assertSame(
            '8.1',
            $this->liesXmlWert($xpath, 'PHPVersion'),
            'Die JTL-Metadaten müssen PHP ab Version 8.1 verlangen.',
        );
        self::assertSame(
            'Michael Gahn DESIGN',
            $this->liesXmlWert($xpath, 'Author'),
            'Der Autor muss exakt Michael Gahn DESIGN lauten.',
        );
        self::assertSame(
            'https://Michael-Gahn.de',
            $this->liesXmlWert($xpath, 'URL'),
            'Die Projektadresse muss exakt https://Michael-Gahn.de lauten.',
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

        $bootstrap = $this->liesDatei(
            'plugin/MGD_AI_Kennzeichnung/Bootstrap.php',
            'Der JTL-Bootstrap muss vorhanden und lesbar sein.',
        );
        self::assertMatchesRegularExpression(
            '/public\s+function\s+boot\s*\(\s*Dispatcher\s+\$dispatcher\s*\)\s*:\s*void\s*'
            . '\{\s*parent::boot\(\$dispatcher\);\s*\}/s',
            $bootstrap,
            'Die boot()-Methode darf ausschließlich den JTL-Elternbootstrap aufrufen.',
        );

        $composerJson = $this->liesDatei('composer.json', 'Die composer.json muss vorhanden und lesbar sein.');
        $composer = json_decode($composerJson, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($composer, 'Die composer.json muss ein JSON-Objekt enthalten.');
        self::assertFileExists(self::ROOT . '/composer.lock', 'Die aufgelösten Composer-Versionen müssen festgehalten sein.');

        $entwicklungsPakete = $composer['require-dev'] ?? null;
        self::assertIsArray($entwicklungsPakete, 'Composer muss einen Bereich require-dev enthalten.');
        self::assertSame('^10.5', $entwicklungsPakete['phpunit/phpunit'] ?? null, 'PHPUnit muss als Dev-Werkzeug vorliegen.');
        self::assertSame('^2.0', $entwicklungsPakete['phpstan/phpstan'] ?? null, 'PHPStan muss als Dev-Werkzeug vorliegen.');
        self::assertSame(
            '^3.68',
            $entwicklungsPakete['friendsofphp/php-cs-fixer'] ?? null,
            'PHP-CS-Fixer muss als Dev-Werkzeug vorliegen.',
        );

        foreach (['phpunit.xml.dist', 'phpstan.neon.dist', '.php-cs-fixer.dist.php'] as $qualitaetsdatei) {
            self::assertFileExists(
                self::ROOT . '/' . $qualitaetsdatei,
                sprintf('Die Qualitätsdatei %s muss vorhanden sein.', $qualitaetsdatei),
            );
        }

        $workflow = $this->liesDatei(
            '.github/workflows/quality.yml',
            'Der Workflow für die Qualitätsprüfung muss vorhanden sein.',
        );
        self::assertStringContainsString(
            'composer validate --strict',
            $workflow,
            'Der Workflow muss Composer ausdrücklich streng validieren.',
        );
        self::assertStringContainsString(
            'xmllint --noout plugin/MGD_AI_Kennzeichnung/info.xml',
            $workflow,
            'Der Workflow muss die info.xml mit einem echten XML-Parser prüfen.',
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

    /**
     * Liest eine verpflichtende Projektdatei mit einer verständlichen Fehlermeldung.
     */
    private function liesDatei(string $relativerPfad, string $fehlermeldung): string
    {
        $absoluterPfad = self::ROOT . '/' . $relativerPfad;
        self::assertFileExists($absoluterPfad, $fehlermeldung);

        $inhalt = file_get_contents($absoluterPfad);
        self::assertIsString($inhalt, $fehlermeldung);

        return $inhalt;
    }
}
