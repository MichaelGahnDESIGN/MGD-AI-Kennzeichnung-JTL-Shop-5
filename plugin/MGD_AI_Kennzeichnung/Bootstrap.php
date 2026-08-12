<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung;

use JTL\Events\Dispatcher;
use JTL\Plugin\Bootstrapper;

/**
 * Einstiegspunkt des Plugins für den Startvorgang von JTL-Shop.
 *
 * Das Grundgerüst registriert bewusst noch keine Ereignisse, Dienste oder
 * sonstige Laufzeitlogik. Dadurch verändert die bloße Installation dieses
 * Entwicklungsstands weder Shopdaten noch die Darstellung des Shops.
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
}
