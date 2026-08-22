<?php

declare(strict_types=1);

use Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel\AdminRoute;
use Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel\AssetCardView;

/** @var AssetCardView $item */
/** @var AdminRoute $route */
$escapedId = htmlspecialchars((string) $item->id, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedFileName = htmlspecialchars($item->fileName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedStatus = htmlspecialchars($item->status, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedStatusLabel = htmlspecialchars($item->statusLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedSourceLabel = htmlspecialchars($item->sourceLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedPosition = htmlspecialchars($item->position, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedTheme = htmlspecialchars($item->theme, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedUsageCount = htmlspecialchars((string) $item->usageCount, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedUpdatedAt = htmlspecialchars($item->updatedAt !== '' ? $item->updatedAt : 'Noch nicht geändert', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escapedDetailUrl = htmlspecialchars($route->query(['view' => 'detail', 'asset_id' => $item->id]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<article class="mgd-asset-card" data-asset-card data-asset-id="<?= $escapedId ?>" data-status="<?= $escapedStatus ?>" data-position="<?= $escapedPosition ?>" data-theme="<?= $escapedTheme ?>">
    <div class="mgd-asset-card__selection">
        <input id="mgd-asset-<?= $escapedId ?>" type="checkbox" name="asset_ids[]" value="<?= $escapedId ?>" aria-label="Bild <?= $escapedId ?> auswählen">
    </div>
    <div class="mgd-asset-card__preview">
        <?php if ($item->previewUrl !== null): ?>
            <img src="<?= htmlspecialchars($item->previewUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="Vorschau von <?= $escapedFileName ?>" width="480" height="320" loading="lazy" decoding="async">
        <?php else: ?>
            <div class="mgd-asset-card__placeholder" role="img" aria-label="Keine sichere Vorschau verfügbar">Keine Vorschau</div>
        <?php endif; ?>
    </div>
    <div class="mgd-asset-card__body">
        <p class="mgd-status mgd-status--<?= $escapedStatus ?>"><strong>Status:</strong> <span data-status-text><?= $escapedStatusLabel ?></span></p>
        <h2 class="mgd-asset-card__title" title="<?= $escapedFileName ?>"><?= $escapedFileName ?></h2>
        <dl class="mgd-asset-card__meta">
            <div><dt>Quelle</dt><dd><?= $escapedSourceLabel ?></dd></div>
            <div><dt>Fundstellen</dt><dd><?= $escapedUsageCount ?></dd></div>
            <div><dt>Geändert</dt><dd><?= $escapedUpdatedAt ?></dd></div>
        </dl>
        <div class="mgd-asset-card__actions">
            <button class="mgd-button mgd-button--primary" type="button" data-label-open>Kennzeichnen</button>
            <a class="mgd-button mgd-button--secondary" href="<?= $escapedDetailUrl ?>">Details</a>
        </div>
    </div>
</article>
