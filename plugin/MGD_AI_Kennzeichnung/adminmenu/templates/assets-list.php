<?php

declare(strict_types=1);

use Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel\AdminRoute;
use Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel\AssetListView;

/** @var AssetListView $view */
/** @var string $csrfToken Das Token wird vom JTL-Adapter injiziert. */
/** @var string $assetScriptUrl */
/** @var string $assetStyleUrl */
/** @var AdminRoute $route */
$escapedCsrf = htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedScriptUrl = htmlspecialchars($assetScriptUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedStyleUrl = htmlspecialchars($assetStyleUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedPluginId = htmlspecialchars((string) $route->pluginId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedAdminMenuId = htmlspecialchars((string) $route->adminMenuId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedPage = htmlspecialchars((string) $view->page, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$maximumPage = max(1, (int) ceil($view->total / $view->pageSize));
$previousPage = max(1, $view->page - 1);
$nextPage = min($maximumPage, $view->page + 1);
$pagination = ['view' => 'list', 'page_size' => $view->pageSize, 'sort' => $view->sort, 'direction' => $view->direction];
foreach ($view->filters as $name => $value) {
    $pagination[$name] = is_bool($value) ? ($value ? '1' : '0') : $value;
}
$previousUrl = htmlspecialchars($route->query(['page' => $previousPage] + $pagination), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$nextUrl = htmlspecialchars($route->query(['page' => $nextPage] + $pagination), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<link rel="stylesheet" href="<?= $escapedStyleUrl ?>">
<section class="mgd-assets" aria-labelledby="mgd-assets-heading">
    <header class="mgd-assets__header">
        <p class="mgd-assets__eyebrow">MGD AI-Kennzeichnung</p>
        <h1 id="mgd-assets-heading">KI-Bildkennzeichnungen</h1>
        <p>Bilder filtern, visuell prüfen und direkt kennzeichnen. Die Originaldateien werden nicht verändert.</p>
    </header>

    <?php require __DIR__ . '/partials/asset-filter.php'; ?>
    <?php require __DIR__ . '/partials/gallery-toolbar.php'; ?>

    <form class="mgd-gallery-form" method="post" aria-label="Ausgewählte Bilder bearbeiten">
        <input type="hidden" name="kPlugin" value="<?= $escapedPluginId ?>">
        <input type="hidden" name="kPluginAdminMenu" value="<?= $escapedAdminMenuId ?>">
        <input type="hidden" name="csrf_token" value="<?= $escapedCsrf ?>">
        <?php if ($view->items === []): ?>
            <div class="mgd-empty-state" role="status">
                <h2>Keine Bilder gefunden</h2>
                <p>Passen Sie die Filter an oder starten Sie einen sicheren Bildscan.</p>
            </div>
        <?php else: ?>
            <div class="mgd-gallery">
                <?php foreach ($view->items as $item): ?>
                    <?php require __DIR__ . '/partials/asset-card.php'; ?>
                <?php endforeach; ?>
            </div>
            <section class="mgd-bulk" aria-labelledby="mgd-bulk-heading">
                <div>
                    <p class="mgd-assets__eyebrow">Stapelbearbeitung</p>
                    <h2 id="mgd-bulk-heading">Ausgewählte Bilder gemeinsam ändern</h2>
                    <p><span data-selection-count>0</span> Bilder ausgewählt. Vor dem Speichern wird immer eine Zusammenfassung angezeigt.</p>
                </div>
                <fieldset class="mgd-bulk__fields">
                    <legend class="sr-only">Änderungsfelder und Zielwerte</legend>
                    <div>
                        <label><input type="checkbox" name="mask[status]" value="1"> Status ändern</label>
                        <select name="values[status]" aria-label="Neuer Status" disabled>
                            <option value="unreviewed">Ungeprüft</option><option value="none">Keine Kennzeichnung</option>
                            <option value="generated">KI-generiert</option><option value="partially-generated">Teilweise KI-generiert</option>
                            <option value="modified">KI-bearbeitet</option><option value="deepfake">Deepfake</option>
                        </select>
                    </div>
                    <div>
                        <label><input type="checkbox" name="mask[position]" value="1"> Position ändern</label>
                        <select name="values[position]" aria-label="Neue Position" disabled>
                            <option value="top-left">Oben links</option><option value="top-right">Oben rechts</option>
                            <option value="bottom-left">Unten links</option><option value="bottom-right">Unten rechts</option>
                        </select>
                    </div>
                    <div>
                        <label><input type="checkbox" name="mask[theme]" value="1"> Darstellung ändern</label>
                        <select name="values[theme]" aria-label="Neue Darstellung" disabled>
                            <option value="auto">Automatisch</option><option value="light">Hell</option><option value="dark">Dunkel</option>
                        </select>
                    </div>
                </fieldset>
                <button class="mgd-button mgd-button--primary" type="submit" name="action" value="bulk-preview">Änderung prüfen</button>
            </section>
        <?php endif; ?>
    </form>

    <nav class="mgd-pagination" aria-label="Seitennavigation">
        <a class="mgd-button mgd-button--secondary" href="<?= $previousUrl ?>">Vorherige Seite</a>
        <span>Seite <?= $escapedPage ?> von <?= htmlspecialchars((string) $maximumPage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
        <a class="mgd-button mgd-button--secondary" href="<?= $nextUrl ?>">Nächste Seite</a>
    </nav>

    <?php require __DIR__ . '/partials/label-dialog.php'; ?>
</section>
<script src="<?= $escapedScriptUrl ?>" defer></script>
