<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Port;

use Plugin\MGD_AI_Kennzeichnung\Admin\Value\LocalAssetLabel;
use Plugin\MGD_AI_Kennzeichnung\Domain\AssetSource;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelPosition;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelStatus;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelTheme;

/** Datenbankgrenze für das atomare Laden und Speichern lokaler Bildlabels. */
interface LocalAssetLabelRepositoryInterface
{
    public function findByLocalPath(string $localPath): ?LocalAssetLabel;

    public function save(
        string $localPath,
        AssetSource $source,
        LabelStatus $status,
        LabelPosition $position,
        LabelTheme $theme,
    ): LocalAssetLabel;
}
