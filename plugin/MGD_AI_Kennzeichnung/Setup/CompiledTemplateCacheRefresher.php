<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Setup;

use FilesystemIterator;
use InvalidArgumentException;
use JTL\Smarty\JTLSmarty;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Entfernt beim Plugin-Update ausschließlich kompilierte Smarty-Vorlagen
 * dieses Plugins.
 *
 * Release-Archive besitzen für reproduzierbare Builds feste Zeitstempel. Eine
 * bereits von JTL kompilierte Vorlage kann dadurch scheinbar neuer sein als
 * ihre aktualisierte Quelldatei. Die gezielte Invalidierung verhindert, dass
 * JTL nach einem Update weiterhin die Oberfläche der Vorversion ausliefert.
 */
final class CompiledTemplateCacheRefresher
{
    public function __construct(private readonly JTLSmarty $smarty) {}

    /**
     * @return int Anzahl der gezielt zur Neukompilierung vorgemerkten Vorlagen
     */
    public function refresh(string $pluginBasePath): int
    {
        $pluginRoot = realpath($pluginBasePath);
        if ($pluginRoot === false || !is_dir($pluginRoot)) {
            throw new InvalidArgumentException('Das Pluginverzeichnis für die Template-Aktualisierung ist ungültig.');
        }

        /** @var list<string> $templates */
        $templates = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($pluginRoot, FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $datei) {
            /*
             * RecursiveIteratorIterator ist zur Laufzeit auf SplFileInfo
             * festgelegt. Die explizite Prüfung hält die Dateigrenze dennoch
             * fail-closed und macht die Typannahme für Menschen sichtbar.
             */
            if (!$datei instanceof SplFileInfo) {
                continue;
            }
            if (!$datei->isFile() || $datei->isLink() || strtolower($datei->getExtension()) !== 'tpl') {
                continue;
            }
            $templatePath = $datei->getRealPath();
            if ($templatePath === false || !str_starts_with($templatePath, $pluginRoot . DIRECTORY_SEPARATOR)) {
                continue;
            }
            $templates[] = $templatePath;
        }

        sort($templates, SORT_STRING);
        /*
         * JTL kapselt bei aktivem Smarty-4-Kompatibilitätsmodus die echte
         * Template-Engine innerhalb von JTLSmarty. Die Compile-Verzeichnisse
         * werden dann ausschließlich auf dieser internen Engine gesetzt.
         * Ein direkter Aufruf auf der äußeren Fassade würde wegen der von
         * Smarty 5 geerbten Methode am falschen Compile-Ordner vorbeilaufen.
         */
        $smartyEngine = $this->smarty->getSmarty();
        foreach ($templates as $templatePath) {
            $smartyEngine->clearCompiledTemplate($templatePath);
        }

        return count($templates);
    }
}
