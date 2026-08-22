<?php

declare(strict_types=1);

use Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel\AdminRoute;

/* Die Karte wird erst beim Öffnen lokal zugeordnet. Gespeichert wird später
 * ausschließlich über die geschützte JTL-Admin-Strecke. */
/** @var string $csrfToken */
/** @var AdminRoute $route */
$escapedCsrf = htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedPluginId = htmlspecialchars((string) $route->pluginId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedAdminMenuId = htmlspecialchars((string) $route->adminMenuId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<div class="mgd-dialog" role="dialog" aria-modal="true" aria-labelledby="mgd-dialog-title" aria-describedby="mgd-dialog-description" data-label-dialog hidden>
    <div class="mgd-dialog__backdrop" data-label-close></div>
    <div class="mgd-dialog__panel">
        <header class="mgd-dialog__header">
            <div>
                <p class="mgd-dialog__eyebrow">Bildkennzeichnung</p>
                <h2 id="mgd-dialog-title">Kennzeichnung bearbeiten</h2>
                <p id="mgd-dialog-description">Die Vorschau ändert sich sofort. Gespeichert wird erst mit dem grünen Speichern-Button.</p>
            </div>
            <button class="mgd-dialog__close" type="button" aria-label="Dialog schließen" data-label-close>×</button>
        </header>
        <div class="mgd-dialog__content">
            <div class="mgd-dialog__preview" data-label-preview aria-label="Vorschau der ausgewählten Kennzeichnung"></div>
            <form class="mgd-dialog__form" method="post" data-label-form>
                <input type="hidden" name="csrf_token" value="<?= $escapedCsrf ?>">
                <input type="hidden" name="kPlugin" value="<?= $escapedPluginId ?>">
                <input type="hidden" name="kPluginAdminMenu" value="<?= $escapedAdminMenuId ?>">
                <input type="hidden" name="asset_id" value="">
                <label for="mgd-dialog-status">Status</label>
                <select id="mgd-dialog-status" name="status">
                    <option value="unreviewed">Ungeprüft</option><option value="none">Keine Kennzeichnung</option>
                    <option value="generated">KI-generiert</option><option value="partially-generated">Teilweise KI-generiert</option>
                    <option value="modified">KI-bearbeitet</option><option value="deepfake">Deepfake</option>
                </select>
                <label for="mgd-dialog-position">Position</label>
                <select id="mgd-dialog-position" name="position">
                    <option value="top-left">Oben links</option><option value="top-right">Oben rechts</option>
                    <option value="bottom-left">Unten links</option><option value="bottom-right">Unten rechts</option>
                </select>
                <label for="mgd-dialog-theme">Darstellung</label>
                <select id="mgd-dialog-theme" name="theme">
                    <option value="auto">Automatisch</option><option value="light">Hell</option><option value="dark">Dunkel</option>
                </select>
            </form>
        </div>
        <p class="mgd-dialog__message" aria-live="polite" data-label-message></p>
        <footer class="mgd-dialog__footer">
            <button class="mgd-button mgd-button--secondary" type="button" data-label-close>Abbrechen</button>
            <button class="mgd-button mgd-button--primary" type="button" data-label-save>Kennzeichnung speichern</button>
        </footer>
    </div>
</div>
