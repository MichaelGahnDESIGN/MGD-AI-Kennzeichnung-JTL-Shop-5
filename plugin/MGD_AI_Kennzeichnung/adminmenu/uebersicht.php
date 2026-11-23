<?php

declare(strict_types=1);

use JTL\Plugin\PluginInterface;
use Plugin\MGD_AI_Kennzeichnung\Kit\Platform\Jtl\Setup;
use Plugin\MGD_AI_Kennzeichnung\Setup\LegacyUpdateData;

// Tab Übersicht vom Baukasten
if (!defined('PFAD_ROOT') || !isset($oPlugin) || !$oPlugin instanceof PluginInterface) {
    http_response_code(403);

    return;
}
$compiled = LegacyUpdateData::compiledDir();
if ($compiled !== null) {
    LegacyUpdateData::migrate($compiled);
}
echo Setup::renderTab(Setup::forShop($oPlugin), 'Übersicht');
