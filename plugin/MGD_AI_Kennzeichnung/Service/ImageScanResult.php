<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Service;

/** Kompaktes unveränderliches Ergebnis eines vollständig bestätigten Scans. */
final class ImageScanResult
{
    public function __construct(
        public readonly int $createdAssets,
        public readonly int $recordedUsages,
    ) {}
}
