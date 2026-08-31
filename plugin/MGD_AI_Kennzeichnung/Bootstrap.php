<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung;

use JTL\Backend\AdminIO;
use JTL\Events\Dispatcher;
use JTL\Plugin\Bootstrapper;
use JTL\Shop;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlAuthorizationAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\IO\AdminIoRegistration;
use Plugin\MGD_AI_Kennzeichnung\Admin\IO\LoadLocalAssetLabel;
use Plugin\MGD_AI_Kennzeichnung\Admin\IO\SaveLocalAssetLabel;
use Plugin\MGD_AI_Kennzeichnung\Admin\Presentation\LocalPreviewUrlResolver;
use Plugin\MGD_AI_Kennzeichnung\Admin\Service\LocalAssetLabelService;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\FrontendLabelRepository;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\LocalAssetLabelRepository;
use Plugin\MGD_AI_Kennzeichnung\Presentation\FrontendDocumentIntegrator;
use Plugin\MGD_AI_Kennzeichnung\Scanner\LocalPathNormalizer;
use Plugin\MGD_AI_Kennzeichnung\Service\DisplaySettings;
use Plugin\MGD_AI_Kennzeichnung\Service\SystemCompatibilityCheck;
use Plugin\MGD_AI_Kennzeichnung\Setup\CompiledTemplateCacheRefresher;
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
        if (!Shop::isFrontend()) {
            $this->bootAdminIo($dispatcher);

            return;
        }
        $dispatcher->listen('shop.hook.140', function (array $argumente): void {
            $plugin = $this->getPlugin();
            $konfiguration = $plugin->getConfig();
            $einstellungen = DisplaySettings::fromJtlConfig([
                'show_credit' => $konfiguration->getValue('show_credit'),
                'update_notices' => $konfiguration->getValue('update_notices'),
                'language' => $konfiguration->getValue('language'),
                'font_size' => $konfiguration->getValue('font_size'),
                'outer_margin' => $konfiguration->getValue('outer_margin'),
                'inner_padding' => $konfiguration->getValue('inner_padding'),
                'border_radius' => $konfiguration->getValue('border_radius'),
                'blur' => $konfiguration->getValue('blur'),
                'transparency' => $konfiguration->getValue('transparency'),
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

    /**
     * Bindet die lokale Kennzeichnung an JTLs bereits authentifizierte
     * Admin-IO-Pipeline. Es entsteht kein eigener öffentlich erreichbarer URL.
     */
    private function bootAdminIo(Dispatcher $dispatcher): void
    {
        $plugin = $this->getPlugin();
        $sessionId = session_id();
        if ($sessionId === false) {
            return;
        }

        $authorization = new JtlAuthorizationAdapter(
            Shop::Container()->getAdminAccount(),
            $plugin->getID(),
            $sessionId,
        );
        try {
            $authorization->assertCanManageAssets();
        } catch (Throwable) {
            return;
        }
        $service = new LocalAssetLabelService(
            $authorization,
            new LocalAssetLabelRepository($this->getDB()),
            new LocalPathNormalizer(),
            new LocalPreviewUrlResolver(),
        );
        $registration = new AdminIoRegistration(
            new LoadLocalAssetLabel($service),
            new SaveLocalAssetLabel($service),
        );

        /* HOOK_IO_HANDLE_REQUEST_ADMIN besitzt in JTL-Shop 5.7.2 die feste ID 311. */
        $dispatcher->listen('shop.hook.311', static function (array $arguments) use ($registration): void {
            $io = $arguments['io'] ?? null;
            if ($io instanceof AdminIO) {
                $registration->register($io);
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
     * Verwirft nach einem JTL-Update ausschließlich kompilierte Vorlagen dieses
     * Plugins. Dadurch wird trotz reproduzierbarer Datei-Zeitstempel sofort die
     * gerade installierte Oberfläche erzeugt.
     *
     * @param mixed $oldVersion Von JTL gemeldete bisherige Pluginversion
     * @param mixed $newVersion Von JTL gemeldete neue Pluginversion
     */
    public function updated($oldVersion, $newVersion): void
    {
        (new CompiledTemplateCacheRefresher(Shop::Smarty()))
            ->refresh($this->getPlugin()->getPaths()->getBasePath());
        parent::updated($oldVersion, $newVersion);
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
