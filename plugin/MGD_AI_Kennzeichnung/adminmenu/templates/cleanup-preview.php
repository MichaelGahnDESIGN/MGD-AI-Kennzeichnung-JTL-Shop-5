<?php

declare(strict_types=1);

use Plugin\MGD_AI_Kennzeichnung\Admin\Result\CleanupPreviewResult;

/** @var CleanupPreviewResult $preview */
/** @var string $csrfToken */
$escapedCsrf = htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedToken = htmlspecialchars($preview->confirmationToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedCount = htmlspecialchars((string) $preview->count, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<section aria-labelledby="mgd-cleanup-preview-heading">
    <h2 id="mgd-cleanup-preview-heading">Bereinigung bestätigen</h2>
    <p>Betroffene Datensätze: <?= $escapedCount ?></p>
    <p>Es werden keine Assets, Bilddateien oder JTL-Kerndaten gelöscht.</p>
    <form method="post" aria-label="Bereinigung verbindlich bestätigen">
        <input type="hidden" name="csrf_token" value="<?= $escapedCsrf ?>">
        <input type="hidden" name="confirmation_token" value="<?= $escapedToken ?>">
        <button type="submit" name="action" value="cleanup-execute">Fundstellen bereinigen</button>
    </form>
</section>
