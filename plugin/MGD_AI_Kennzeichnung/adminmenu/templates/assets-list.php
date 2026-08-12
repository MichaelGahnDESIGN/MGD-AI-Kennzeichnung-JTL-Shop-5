<?php

declare(strict_types=1);

use Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel\AssetListView;

/** @var AssetListView $view */
/** @var string $csrfToken Das Token wird vom JTL-Adapter injiziert. */
$escapedCsrf = htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<section aria-labelledby="mgd-assets-heading">
    <h1 id="mgd-assets-heading">KI-Bildkennzeichnungen</h1>
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
        <button type="submit" name="action" value="bulk-preview">Änderung prüfen</button>
    </form>
    <?php
    $escapedPage = htmlspecialchars((string) $view->page, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedTotal = htmlspecialchars((string) $view->total, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
    <nav aria-label="Seitennavigation">Seite <?= $escapedPage ?> · insgesamt <?= $escapedTotal ?> Einträge</nav>
</section>
