<?php

declare(strict_types=1);

use Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel\AdminRoute;

/** @var array<string, scalar|null> $detail */
/** @var string $csrfToken */
/** @var string $assetScriptUrl */
/** @var AdminRoute $route */
$escapedCsrf = htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedScriptUrl = htmlspecialchars($assetScriptUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedId = htmlspecialchars((string) ($detail['id'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedPath = htmlspecialchars((string) ($detail['local_path'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedStatus = htmlspecialchars((string) ($detail['status'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedPluginId = htmlspecialchars((string) $route->pluginId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedAdminMenuId = htmlspecialchars((string) $route->adminMenuId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<section aria-labelledby="mgd-detail-heading">
    <h2 id="mgd-detail-heading">Bilddetails</h2>
    <dl>
        <dt>Technische ID</dt><dd><?= $escapedId ?></dd>
        <dt>Lokaler Pfad</dt><dd><?= $escapedPath ?></dd>
        <dt>Status</dt><dd><?= $escapedStatus ?></dd>
    </dl>
    <form method="post" aria-label="Bildkennzeichnung speichern">
        <input type="hidden" name="kPlugin" value="<?= $escapedPluginId ?>">
        <input type="hidden" name="kPluginAdminMenu" value="<?= $escapedAdminMenuId ?>">
        <input type="hidden" name="csrf_token" value="<?= $escapedCsrf ?>">
        <input type="hidden" name="asset_id" value="<?= $escapedId ?>">
        <label><input type="checkbox" name="mask[status]" value="1"> Status ändern</label>
        <label for="mgd-status">Neuer Status</label>
        <select id="mgd-status" name="values[status]" disabled>
            <option value="unreviewed">Ungeprüft</option>
            <option value="none">Keine KI-Kennzeichnung</option>
            <option value="generated">KI-generiert</option>
            <option value="partially-generated">Teilweise KI-generiert</option>
            <option value="modified">KI-bearbeitet</option>
            <option value="deepfake">Deepfake</option>
        </select>
        <label><input type="checkbox" name="mask[position]" value="1"> Position ändern</label>
        <label for="mgd-position">Neue Position</label>
        <select id="mgd-position" name="values[position]" disabled>
            <option value="top-left">Oben links</option><option value="top-right">Oben rechts</option>
            <option value="bottom-left">Unten links</option><option value="bottom-right">Unten rechts</option>
        </select>
        <label><input type="checkbox" name="mask[theme]" value="1"> Darstellung ändern</label>
        <label for="mgd-theme">Neue Darstellung</label>
        <select id="mgd-theme" name="values[theme]" disabled>
            <option value="auto">Automatisch</option><option value="light">Hell</option><option value="dark">Dunkel</option>
        </select>
        <button type="submit" name="action" value="single-update">Speichern</button>
    </form>
    <p><a href="<?= htmlspecialchars($route->query(['view' => 'list']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Zurück zur Bildliste</a></p>
</section>
<script src="<?= $escapedScriptUrl ?>" defer></script>
