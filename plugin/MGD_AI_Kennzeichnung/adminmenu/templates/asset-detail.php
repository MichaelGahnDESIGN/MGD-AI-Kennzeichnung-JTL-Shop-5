<?php

declare(strict_types=1);

/** @var array<string, scalar|null> $detail */
/** @var string $csrfToken */
$escapedCsrf = htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedId = htmlspecialchars((string) ($detail['id'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedPath = htmlspecialchars((string) ($detail['local_path'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedStatus = htmlspecialchars((string) ($detail['status'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<section aria-labelledby="mgd-detail-heading">
    <h2 id="mgd-detail-heading">Bilddetails</h2>
    <dl>
        <dt>Technische ID</dt><dd><?= $escapedId ?></dd>
        <dt>Lokaler Pfad</dt><dd><?= $escapedPath ?></dd>
        <dt>Status</dt><dd><?= $escapedStatus ?></dd>
    </dl>
    <form method="post" aria-label="Bildkennzeichnung speichern">
        <input type="hidden" name="csrf_token" value="<?= $escapedCsrf ?>">
        <input type="hidden" name="asset_id" value="<?= $escapedId ?>">
        <label><input type="checkbox" name="mask[status]" value="1"> Status ändern</label>
        <label for="mgd-status">Neuer Status</label>
        <select id="mgd-status" name="values[status]">
            <option value="unreviewed">Ungeprüft</option>
            <option value="none">Keine KI-Kennzeichnung</option>
            <option value="generated">KI-generiert</option>
            <option value="partially-generated">Teilweise KI-generiert</option>
            <option value="modified">KI-bearbeitet</option>
            <option value="deepfake">Deepfake</option>
        </select>
        <button type="submit" name="action" value="single-update">Speichern</button>
    </form>
</section>
