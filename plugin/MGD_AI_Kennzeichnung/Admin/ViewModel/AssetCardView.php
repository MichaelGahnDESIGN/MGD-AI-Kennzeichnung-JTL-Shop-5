<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel;

/**
 * Bereits aufbereitete, unveränderliche Daten einer einzelnen Galeriekarte.
 *
 * Das Template erhält dadurch weder Datenbankzeilen noch die Aufgabe, freie
 * technische Werte in sichtbare Beschriftungen oder URLs zu übersetzen.
 */
final class AssetCardView
{
    public function __construct(
        public readonly int $id,
        public readonly string $fileName,
        public readonly ?string $previewUrl,
        public readonly string $status,
        public readonly string $statusLabel,
        public readonly string $sourceLabel,
        public readonly string $position,
        public readonly string $theme,
        public readonly int $usageCount,
        public readonly string $updatedAt,
    ) {}
}
