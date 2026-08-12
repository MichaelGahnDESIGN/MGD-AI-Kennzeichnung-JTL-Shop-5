<?php

declare(strict_types=1);

use Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel\CleanupListView;

/** @var CleanupListView $view */
/** @var string $csrfToken */
$escapedCsrf = htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<section aria-labelledby="mgd-cleanup-heading">
    <h2 id="mgd-cleanup-heading">Veraltete Fundstellen</h2>
    <p>Es werden ausschließlich technische Plugin-Fundstellen bereinigt. Assets, JTL-Daten und Bilddateien bleiben erhalten.</p>
    <form method="post" aria-label="Veraltete Fundstellen auswählen">
        <input type="hidden" name="csrf_token" value="<?= $escapedCsrf ?>">
        <table><thead><tr><th scope="col">Auswahl</th><th scope="col">ID</th><th scope="col">Quelle</th><th scope="col">Referenz</th></tr></thead><tbody>
        <?php foreach ($view->items as $item): ?>
            <?php $escapedId = htmlspecialchars((string) $item['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
            <tr>
                <td><input type="checkbox" name="usage_ids[]" value="<?= $escapedId ?>" aria-label="Fundstelle <?= $escapedId ?> auswählen"></td>
                <td><?= $escapedId ?></td>
                <td><?= htmlspecialchars($item['source_type'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($item['source_reference'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody></table>
        <button type="submit" name="action" value="cleanup-preview">Bereinigung prüfen</button>
    </form>
    <?php
    $maximumPage = max(1, (int) ceil($view->total / $view->pageSize));
$previousPage = max(1, $view->page - 1);
$nextPage = min($maximumPage, $view->page + 1);
?>
    <nav aria-label="Seitennavigation Bereinigung">
        <a href="?view=cleanup&amp;page=<?= htmlspecialchars((string) $previousPage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>&amp;page_size=<?= htmlspecialchars((string) $view->pageSize, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Vorherige Seite</a>
        <a href="?view=cleanup&amp;page=<?= htmlspecialchars((string) $nextPage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>&amp;page_size=<?= htmlspecialchars((string) $view->pageSize, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Nächste Seite</a>
    </nav>
</section>
