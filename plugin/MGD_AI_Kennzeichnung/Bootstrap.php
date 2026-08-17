<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung;

use JTL\Events\Dispatcher;
use JTL\Plugin\Bootstrapper;
use Plugin\MGD_AI_Kennzeichnung\Service\SystemCompatibilityCheck;
use Plugin\MGD_AI_Kennzeichnung\Setup\PluginDataLifecycle;

/**
 * Einstiegspunkt des Plugins für den Startvorgang von JTL-Shop.
 *
 * Der Bootstrap hält den JTL-Lebenszyklus bewusst leicht: Laufzeitverhalten
 * wird über Listener ergänzt, Kompatibilität vor der Installation geprüft und
 * eine Datenlöschung nur bei ausdrücklicher Auswahl sicher ausgeführt.
 */
class Bootstrap extends Bootstrapper
{
    /**
     * Übergibt den Startvorgang unverändert an den JTL-Basisklassen-Bootstrap.
     *
     * @param Dispatcher $dispatcher Ereignisverteiler, den JTL-Shop bereitstellt
     */
    public function boot(Dispatcher $dispatcher): void
    {
        parent::boot($dispatcher);
    }

    /** Bricht die Installation auf nicht unterstützten Laufzeiten früh ab. */
    public function preInstallCheck(): bool
    {
        if (!defined('APPLICATION_VERSION')) {
            return false;
        }
        $shopVersion = constant('APPLICATION_VERSION');
        if (!is_string($shopVersion)) {
            return false;
        }

        return (new SystemCompatibilityCheck())->supports($shopVersion, PHP_VERSION);
    }

    /**
     * JTL übergibt den bewusst im Deinstallationsdialog gewählten Löschwunsch.
     * Ohne diesen Wunsch bleiben alle Kennzeichnungen für eine Neuinstallation
     * erhalten. Mit Wunsch greift die strenge Eigentums- und Strukturprüfung.
     */
    public function uninstalled(bool $deleteData = true): void
    {
        (new PluginDataLifecycle($this->getDB()))->uninstalled($deleteData);
        parent::uninstalled($deleteData);
    }
}
