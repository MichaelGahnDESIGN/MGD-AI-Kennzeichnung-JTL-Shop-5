<?php

declare(strict_types=1);

use Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel\AdminRoute;
use Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel\AssetListView;

/** @var AssetListView $view */
/** @var AdminRoute $route */
$escapedPluginId = htmlspecialchars((string) $route->pluginId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedAdminMenuId = htmlspecialchars((string) $route->adminMenuId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$sources = [
    'product' => 'Artikel',
    'category' => 'Kategorie',
    'manufacturer' => 'Hersteller',
    'banner' => 'Banner oder Slider',
    'opc' => 'OnPage Composer',
    'custom-local-manual' => 'Manuell hinzugefügt',
    'unknown' => 'Unbekannte Quelle',
];
?>
<form class="mgd-filter" method="get" aria-label="Bildgalerie filtern">
    <input type="hidden" name="kPlugin" value="<?= $escapedPluginId ?>">
    <input type="hidden" name="kPluginAdminMenu" value="<?= $escapedAdminMenuId ?>">
    <input type="hidden" name="view" value="list">
    <div class="mgd-filter__field">
        <label for="mgd-filter-status">Status</label>
        <select id="mgd-filter-status" name="status">
            <option value="">Alle Status</option>
            <option value="unreviewed" <?= ($view->filters['status'] ?? null) === 'unreviewed' ? 'selected' : '' ?>>Ungeprüft</option>
            <option value="none" <?= ($view->filters['status'] ?? null) === 'none' ? 'selected' : '' ?>>Keine Kennzeichnung</option>
            <option value="generated" <?= ($view->filters['status'] ?? null) === 'generated' ? 'selected' : '' ?>>KI-generiert</option>
            <option value="partially-generated" <?= ($view->filters['status'] ?? null) === 'partially-generated' ? 'selected' : '' ?>>Teilweise KI-generiert</option>
            <option value="modified" <?= ($view->filters['status'] ?? null) === 'modified' ? 'selected' : '' ?>>KI-bearbeitet</option>
            <option value="deepfake" <?= ($view->filters['status'] ?? null) === 'deepfake' ? 'selected' : '' ?>>Deepfake</option>
        </select>
    </div>
    <div class="mgd-filter__field">
        <label for="mgd-filter-source">Quelle</label>
        <select id="mgd-filter-source" name="source">
            <option value="">Alle Quellen</option>
            <?php foreach ($sources as $source => $label): ?>
                <option value="<?= htmlspecialchars($source, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" <?= ($view->filters['source'] ?? null) === $source ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mgd-filter__field">
        <label for="mgd-filter-present">Fundstelle</label>
        <select id="mgd-filter-present" name="present">
            <option value="">Alle Fundstellen</option>
            <option value="1" <?= ($view->filters['present'] ?? null) === true ? 'selected' : '' ?>>Vorhanden</option>
            <option value="0" <?= ($view->filters['present'] ?? null) === false ? 'selected' : '' ?>>Veraltet</option>
        </select>
    </div>
    <div class="mgd-filter__field">
        <label for="mgd-sort">Sortierung</label>
        <select id="mgd-sort" name="sort">
            <option value="id" <?= $view->sort === 'id' ? 'selected' : '' ?>>ID</option>
            <option value="status" <?= $view->sort === 'status' ? 'selected' : '' ?>>Status</option>
            <option value="updated_at" <?= $view->sort === 'updated_at' ? 'selected' : '' ?>>Änderungsdatum</option>
        </select>
    </div>
    <div class="mgd-filter__field">
        <label for="mgd-direction">Richtung</label>
        <select id="mgd-direction" name="direction">
            <option value="asc" <?= $view->direction === 'asc' ? 'selected' : '' ?>>Aufsteigend</option>
            <option value="desc" <?= $view->direction === 'desc' ? 'selected' : '' ?>>Absteigend</option>
        </select>
    </div>
    <div class="mgd-filter__field">
        <label for="mgd-page-size">Einträge pro Seite</label>
        <select id="mgd-page-size" name="page_size">
            <?php foreach ([10, 25, 50, 100] as $pageSize): ?>
                <option value="<?= htmlspecialchars((string) $pageSize, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" <?= $view->pageSize === $pageSize ? 'selected' : '' ?>><?= htmlspecialchars((string) $pageSize, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button class="mgd-button mgd-button--primary" type="submit">Galerie anzeigen</button>
</form>
