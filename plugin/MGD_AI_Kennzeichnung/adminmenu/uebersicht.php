<?php

declare(strict_types=1);

use JTL\Plugin\PluginInterface;
use Plugin\MGD_AI_Kennzeichnung\Kit\Platform\Jtl\Setup;

// Tab Übersicht vom Baukasten
if (!defined('PFAD_ROOT') || !isset($oPlugin) || !$oPlugin instanceof PluginInterface) {
    http_response_code(403);

    return;
}
echo Setup::renderTab(Setup::forShop($oPlugin), 'Übersicht');
