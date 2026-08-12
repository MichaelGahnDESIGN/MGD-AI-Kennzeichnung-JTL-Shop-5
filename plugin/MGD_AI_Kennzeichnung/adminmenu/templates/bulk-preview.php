<?php

declare(strict_types=1);

use Plugin\MGD_AI_Kennzeichnung\Admin\Result\BulkUpdatePreviewResult;

/** @var BulkUpdatePreviewResult $preview */
/** @var string $csrfToken */
$escapedCsrf = htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedConfirmation = htmlspecialchars($preview->confirmationToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedCount = htmlspecialchars((string) $preview->count, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<section aria-labelledby="mgd-preview-heading">
    <h2 id="mgd-preview-heading">Stapeländerung prüfen</h2>
    <p>Betroffene Datensätze: <?= $escapedCount ?></p>
    <table>
        <thead><tr><th scope="col">Feld</th><th scope="col">Zielwert</th></tr></thead>
        <tbody>
        <?php foreach ($preview->targets as $field => $target): ?>
            <?php
            $escapedField = htmlspecialchars($field, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $escapedTarget = htmlspecialchars($target, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            ?>
            <tr><td><?= $escapedField ?></td><td><?= $escapedTarget ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <form method="post" aria-label="Vorschau bestätigen">
        <input type="hidden" name="csrf_token" value="<?= $escapedCsrf ?>">
        <input type="hidden" name="confirmation_token" value="<?= $escapedConfirmation ?>">
        <button type="submit" name="action" value="bulk-update">Verbindlich speichern</button>
    </form>
</section>
