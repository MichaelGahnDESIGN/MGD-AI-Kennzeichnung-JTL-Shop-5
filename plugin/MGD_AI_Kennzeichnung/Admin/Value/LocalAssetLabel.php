<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Value;

use Plugin\MGD_AI_Kennzeichnung\Domain\AssetSource;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelPosition;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelStatus;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelTheme;

/** Unveränderlicher fachlicher Zustand einer lokalen Bildkennzeichnung. */
final class LocalAssetLabel
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $localPath,
        public readonly LabelStatus $status,
        public readonly LabelPosition $position,
        public readonly LabelTheme $theme,
        public readonly AssetSource $source,
        public readonly bool $persisted,
    ) {}
}
