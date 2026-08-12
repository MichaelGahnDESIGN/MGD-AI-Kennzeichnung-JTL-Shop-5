<?php

declare(strict_types=1);

use Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel\AssetListView;

/** @var AssetListView $view */
/** @var string $csrfToken Das Token wird vom JTL-Adapter injiziert. */
/** @var string $assetScriptUrl */
$escapedCsrf = htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedScriptUrl = htmlspecialchars($assetScriptUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<section aria-labelledby="mgd-assets-heading">
    <h1 id="mgd-assets-heading">KI-Bildkennzeichnungen</h1>
    <form method="get" aria-label="Bildliste filtern">
        <input type="hidden" name="view" value="list">
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
        <label for="mgd-filter-source">Quelle</label>
        <select id="mgd-filter-source" name="source">
            <option value="">Alle Quellen</option>
            <?php foreach (['product', 'category', 'manufacturer', 'banner', 'opc', 'custom-local-manual', 'unknown'] as $source): ?>
                <option value="<?= htmlspecialchars($source, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" <?= ($view->filters['source'] ?? null) === $source ? 'selected' : '' ?>><?= htmlspecialchars($source, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
        <label for="mgd-filter-present">Fundstelle</label>
        <select id="mgd-filter-present" name="present">
            <option value="">Alle Fundstellen</option>
            <option value="1" <?= ($view->filters['present'] ?? null) === true ? 'selected' : '' ?>>Vorhanden</option>
            <option value="0" <?= ($view->filters['present'] ?? null) === false ? 'selected' : '' ?>>Veraltet</option>
        </select>
        <label for="mgd-sort">Sortierung</label>
        <select id="mgd-sort" name="sort">
            <option value="id" <?= $view->sort === 'id' ? 'selected' : '' ?>>ID</option>
            <option value="status" <?= $view->sort === 'status' ? 'selected' : '' ?>>Status</option>
            <option value="updated_at" <?= $view->sort === 'updated_at' ? 'selected' : '' ?>>Änderungsdatum</option>
        </select>
        <label for="mgd-direction">Richtung</label>
        <select id="mgd-direction" name="direction">
            <option value="asc" <?= $view->direction === 'asc' ? 'selected' : '' ?>>Aufsteigend</option>
            <option value="desc" <?= $view->direction === 'desc' ? 'selected' : '' ?>>Absteigend</option>
        </select>
        <label for="mgd-page-size">Einträge pro Seite</label>
        <select id="mgd-page-size" name="page_size">
            <?php foreach ([10, 25, 50, 100] as $pageSize): ?>
                <option value="<?= htmlspecialchars((string) $pageSize, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" <?= $view->pageSize === $pageSize ? 'selected' : '' ?>><?= htmlspecialchars((string) $pageSize, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Liste anwenden</button>
    </form>
    <form method="post" aria-label="Ausgewählte Bilder bearbeiten">
        <input type="hidden" name="csrf_token" value="<?= $escapedCsrf ?>">
        <table>
            <thead>
            <tr>
                <th scope="col">Auswahl</th>
                <th scope="col">ID</th>
                <th scope="col">Lokaler Pfad</th>
                <th scope="col">Status</th>
                <th scope="col">Fundstellen</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($view->items as $item): ?>
                <?php
                $escapedId = htmlspecialchars((string) ($item['id'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $escapedPath = htmlspecialchars((string) ($item['local_path'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $escapedStatus = htmlspecialchars((string) ($item['status'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $escapedUsageCount = htmlspecialchars((string) ($item['usage_count'] ?? 0), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                ?>
                <tr>
                    <td><input type="checkbox" name="asset_ids[]" value="<?= $escapedId ?>" aria-label="Asset <?= $escapedId ?> auswählen"></td>
                    <td><?= $escapedId ?></td>
                    <td><?= $escapedPath ?></td>
                    <td><?= $escapedStatus ?></td>
                    <td><?= $escapedUsageCount ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <fieldset>
            <legend>Änderungsfelder und Zielwerte</legend>
            <label><input type="checkbox" name="mask[status]" value="1"> Status ändern</label>
            <label for="mgd-bulk-status">Statusziel</label>
            <select id="mgd-bulk-status" name="values[status]" disabled>
                <option value="unreviewed">Ungeprüft</option><option value="none">Keine Kennzeichnung</option>
                <option value="generated">KI-generiert</option><option value="partially-generated">Teilweise KI-generiert</option>
                <option value="modified">KI-bearbeitet</option><option value="deepfake">Deepfake</option>
            </select>
            <label><input type="checkbox" name="mask[position]" value="1"> Position ändern</label>
            <label for="mgd-bulk-position">Positionsziel</label>
            <select id="mgd-bulk-position" name="values[position]" disabled>
                <option value="top-left">Oben links</option><option value="top-right">Oben rechts</option>
                <option value="bottom-left">Unten links</option><option value="bottom-right">Unten rechts</option>
            </select>
            <label><input type="checkbox" name="mask[theme]" value="1"> Darstellung ändern</label>
            <label for="mgd-bulk-theme">Darstellungsziel</label>
            <select id="mgd-bulk-theme" name="values[theme]" disabled>
                <option value="auto">Automatisch</option><option value="light">Hell</option><option value="dark">Dunkel</option>
            </select>
        </fieldset>
        <button type="submit" name="action" value="bulk-preview">Änderung prüfen</button>
    </form>
    <form method="post" aria-label="Bildquellen neu scannen">
        <input type="hidden" name="csrf_token" value="<?= $escapedCsrf ?>">
        <button type="submit" name="action" value="scan">Sicheren Bildscan starten</button>
    </form>
    <p><a href="?view=cleanup">Veraltete Fundstellen bereinigen</a></p>
    <?php
    $escapedPage = htmlspecialchars((string) $view->page, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedTotal = htmlspecialchars((string) $view->total, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
    <?php
    $maximumPage = max(1, (int) ceil($view->total / $view->pageSize));
$previousPage = max(1, $view->page - 1);
$nextPage = min($maximumPage, $view->page + 1);
$pagination = ['view' => 'list', 'page_size' => $view->pageSize, 'sort' => $view->sort, 'direction' => $view->direction];
foreach ($view->filters as $name => $value) {
    $pagination[$name] = is_bool($value) ? ($value ? '1' : '0') : $value;
}
$previousUrl = '?' . http_build_query(['page' => $previousPage] + $pagination, '', '&', PHP_QUERY_RFC3986);
$nextUrl = '?' . http_build_query(['page' => $nextPage] + $pagination, '', '&', PHP_QUERY_RFC3986);
?>
    <nav aria-label="Seitennavigation">
        <a href="<?= htmlspecialchars($previousUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Vorherige Seite</a>
        Seite <?= $escapedPage ?> · insgesamt <?= $escapedTotal ?> Einträge
        <a href="<?= htmlspecialchars($nextUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Nächste Seite</a>
    </nav>
</section>
<script src="<?= $escapedScriptUrl ?>" defer></script>
