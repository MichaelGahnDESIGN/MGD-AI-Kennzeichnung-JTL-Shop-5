<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung;

use JTL\Events\Dispatcher;
use JTL\Plugin\Bootstrapper;
use JTL\Shop;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\FrontendLabelRepository;
use Plugin\MGD_AI_Kennzeichnung\Presentation\FrontendDocumentIntegrator;
use Plugin\MGD_AI_Kennzeichnung\Service\DisplaySettings;
use Plugin\MGD_AI_Kennzeichnung\Service\SystemCompatibilityCheck;
use Plugin\MGD_AI_Kennzeichnung\Setup\PluginDataLifecycle;
use Throwable;

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
        $dispatcher->listen('shop.hook.140', function (array $argumente): void {
            $plugin = $this->getPlugin();
            $konfiguration = $plugin->getConfig();
            $einstellungen = DisplaySettings::fromJtlConfig([
                'show_credit' => $konfiguration->getValue('show_credit'),
                'update_notices' => $konfiguration->getValue('update_notices'),
                'language' => $konfiguration->getValue('language'),
                'position' => $konfiguration->getValue('position'),
                'theme' => $konfiguration->getValue('theme'),
                'font_size' => $konfiguration->getValue('font_size'),
                'outer_margin' => $konfiguration->getValue('outer_margin'),
                'inner_padding' => $konfiguration->getValue('inner_padding'),
                'border_radius' => $konfiguration->getValue('border_radius'),
                'blur' => $konfiguration->getValue('blur'),
            ]);
            $integrator = new FrontendDocumentIntegrator();
            $integrator->integrate(
                $argumente,
                $plugin->getPaths()->getFrontendURL(),
                $einstellungen->showCredit,
            );

            /* Ein Datenbankfehler darf die Auslieferung des Shops niemals unterbrechen. */
            try {
                $integrator->integrateLabels(
                    $argumente,
                    (new FrontendLabelRepository($this->getDB()))->visibleLabels(),
                    $einstellungen,
                    Shop::getLanguageCode(),
                );
            } catch (Throwable) {
                return;
            }
        });
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
