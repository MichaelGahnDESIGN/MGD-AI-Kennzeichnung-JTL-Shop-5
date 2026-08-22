<?php

declare(strict_types=1);

use Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel\AdminRoute;
use Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel\AssetListView;

/** @var AssetListView $view */
/** @var AdminRoute $route */
/** @var string $csrfToken */
$escapedTotal = htmlspecialchars((string) $view->total, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedCsrf = htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedPluginId = htmlspecialchars((string) $route->pluginId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedAdminMenuId = htmlspecialchars((string) $route->adminMenuId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<div class="mgd-gallery-toolbar">
    <p class="mgd-gallery-toolbar__count" aria-live="polite"><strong><?= $escapedTotal ?></strong> Ergebnisse</p>
    <div class="mgd-gallery-toolbar__actions">
        <a class="mgd-button mgd-button--secondary" href="<?= htmlspecialchars($route->query(['view' => 'cleanup']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Veraltete Fundstellen</a>
        <form method="post" aria-label="Bildquellen neu scannen">
            <input type="hidden" name="kPlugin" value="<?= $escapedPluginId ?>">
            <input type="hidden" name="kPluginAdminMenu" value="<?= $escapedAdminMenuId ?>">
            <input type="hidden" name="csrf_token" value="<?= $escapedCsrf ?>">
            <button class="mgd-button mgd-button--secondary" type="submit" name="action" value="scan">Sicheren Bildscan starten</button>
        </form>
    </div>
</div>
