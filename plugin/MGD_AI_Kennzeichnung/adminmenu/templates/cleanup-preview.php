<?php

declare(strict_types=1);

use Plugin\MGD_AI_Kennzeichnung\Admin\Result\CleanupPreviewResult;
use Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel\AdminRoute;

/** @var CleanupPreviewResult $preview */
/** @var string $csrfToken */
/** @var AdminRoute $route */
$escapedCsrf = htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedToken = htmlspecialchars($preview->confirmationToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedCount = htmlspecialchars((string) $preview->count, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedPluginId = htmlspecialchars((string) $route->pluginId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedAdminMenuId = htmlspecialchars((string) $route->adminMenuId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<section aria-labelledby="mgd-cleanup-preview-heading">
    <h2 id="mgd-cleanup-preview-heading">Bereinigung bestätigen</h2>
    <p>Betroffene Datensätze: <?= $escapedCount ?></p>
    <p>Es werden keine Assets, Bilddateien oder JTL-Kerndaten gelöscht.</p>
    <form method="post" aria-label="Bereinigung verbindlich bestätigen">
        <input type="hidden" name="kPlugin" value="<?= $escapedPluginId ?>">
        <input type="hidden" name="kPluginAdminMenu" value="<?= $escapedAdminMenuId ?>">
        <input type="hidden" name="csrf_token" value="<?= $escapedCsrf ?>">
        <input type="hidden" name="confirmation_token" value="<?= $escapedToken ?>">
        <button type="submit" name="action" value="cleanup-execute">Fundstellen bereinigen</button>
    </form>
    <p><a href="<?= htmlspecialchars($route->query(['view' => 'cleanup']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Zurück zur Bereinigungsliste</a></p>
</section>
