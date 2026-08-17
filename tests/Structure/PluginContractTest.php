<?php

declare(strict_types=1);

namespace Tests\Structure;

use DOMDocument;
use DOMXPath;
use JTL\Events\Dispatcher;
use JTL\Plugin\Bootstrapper as JtlBootstrapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

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
        $phpVersionElemente = $xpath->query('//*[local-name()="PHPVersion"]');
        self::assertNotFalse($phpVersionElemente, 'Die Prüfung auf unbekannte PHP-Metadaten muss ausführbar sein.');
        self::assertCount(
            0,
            $phpVersionElemente,
            'Die info.xml darf kein von JTL-Shop 5.7.2 unbekanntes PHPVersion-Element enthalten.',
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

        $this->pruefeSchlankenLifecycleBootstrap();

        $composerJson = $this->liesDatei('composer.json', 'Die composer.json muss vorhanden und lesbar sein.');
        $composer = json_decode($composerJson, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($composer, 'Die composer.json muss ein JSON-Objekt enthalten.');
        self::assertFileExists(self::ROOT . '/composer.lock', 'Die aufgelösten Composer-Versionen müssen festgehalten sein.');

        $laufzeitPakete = $composer['require'] ?? null;
        self::assertIsArray($laufzeitPakete, 'Composer muss einen Bereich require enthalten.');
        self::assertSame('^8.1', $laufzeitPakete['php'] ?? null, 'Composer muss mindestens PHP 8.1 verlangen.');

        $composerKonfiguration = $composer['config'] ?? null;
        self::assertIsArray($composerKonfiguration, 'Composer muss einen Bereich config enthalten.');
        $plattform = $composerKonfiguration['platform'] ?? null;
        self::assertIsArray($plattform, 'Composer muss die Zielplattform festlegen.');
        self::assertSame(
            '8.1.0',
            $plattform['php'] ?? null,
            'Composer muss Abhängigkeiten verbindlich für PHP 8.1 auflösen.',
        );

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
            'DOMDocument',
            $workflow,
            'Der Workflow muss die info.xml mit dem plattformunabhängigen DOM-Parser prüfen.',
        );
        self::assertStringContainsString(
            "php: ['8.1', '8.5']",
            $workflow,
            'Die CI-Matrix muss PHP 8.1 ausdrücklich prüfen.',
        );
        self::assertStringContainsString(
            'persist-credentials: false',
            $workflow,
            'Der Checkout darf keine GitHub-Zugangsdaten im Arbeitsverzeichnis belassen.',
        );

        $validatePosition = strpos($workflow, 'composer validate --strict');
        $installPosition = strpos($workflow, 'composer install --no-interaction');
        self::assertIsInt($validatePosition, 'Der Composer-Validierungsschritt muss auffindbar sein.');
        self::assertIsInt($installPosition, 'Der Composer-Installationsschritt muss auffindbar sein.');
        self::assertLessThan(
            $installPosition,
            $validatePosition,
            'Die strenge Composer-Validierung muss vor der Installation laufen.',
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

    /**
     * Führt den echten Plugin-Bootstrap gegen beobachtbare JTL-Teststubs aus.
     *
     * Neben der tatsächlichen Weitergabe an den Elternbootstrap wird geprüft,
     * dass der Plugin-Bootstrap keine zusätzlichen öffentlichen Einstiegspunkte,
     * eigenen Zustände oder Initialisierungswege mitbringt.
     */
    private function pruefeSchlankenLifecycleBootstrap(): void
    {
        require_once self::ROOT . '/tests/Stubs/JtlPluginStubs.php';
        require_once self::ROOT . '/plugin/MGD_AI_Kennzeichnung/Bootstrap.php';

        $klasse = new ReflectionClass(\Plugin\MGD_AI_Kennzeichnung\Bootstrap::class);
        $konstruktor = $klasse->getConstructor();
        self::assertTrue(
            $konstruktor === null || $konstruktor->getDeclaringClass()->getName() !== $klasse->getName(),
            'Der Lifecycle-Bootstrap darf keinen eigenen Konstruktor besitzen.',
        );

        $eigeneEigenschaften = array_filter(
            $klasse->getProperties(),
            static fn(ReflectionProperty $eigenschaft): bool => $eigenschaft->getDeclaringClass()->getName() === $klasse->getName(),
        );
        self::assertSame([], $eigeneEigenschaften, 'Der Lifecycle-Bootstrap darf keine eigenen Eigenschaften besitzen.');

        $eigeneStatischeMethoden = array_filter(
            $klasse->getMethods(ReflectionMethod::IS_STATIC),
            static fn(ReflectionMethod $methode): bool => $methode->getDeclaringClass()->getName() === $klasse->getName(),
        );
        self::assertSame(
            [],
            $eigeneStatischeMethoden,
            'Der Lifecycle-Bootstrap darf keine eigenen statischen Initialisierungswege besitzen.',
        );

        $eigeneOeffentlicheMethoden = array_values(array_map(
            static fn(ReflectionMethod $methode): string => $methode->getName(),
            array_filter(
                $klasse->getMethods(ReflectionMethod::IS_PUBLIC),
                static fn(ReflectionMethod $methode): bool => $methode->getDeclaringClass()->getName() === $klasse->getName(),
            ),
        ));
        self::assertSame(
            ['boot', 'preInstallCheck', 'uninstalled'],
            $eigeneOeffentlicheMethoden,
            'Der Bootstrap darf nur die drei vorgesehenen JTL-Lifecycle-Einstiege bereitstellen.',
        );

        JtlBootstrapper::$bootAufrufe = 0;
        $dispatcher = new Dispatcher();
        $bootstrap = $klasse->newInstance();
        $bootstrap->boot($dispatcher);

        self::assertSame(1, JtlBootstrapper::$bootAufrufe, 'boot() muss genau einmal an JTL weitergeben.');
        self::assertSame(
            ['shop.hook.140'],
            $dispatcher->events(),
            'Der Bootstrap muss genau den offiziellen JTL-Outputfilter für lokale Frontendassets registrieren.',
        );
    }
}
