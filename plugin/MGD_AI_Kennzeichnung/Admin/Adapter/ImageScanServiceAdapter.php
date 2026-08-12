<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Adapter;

use Plugin\MGD_AI_Kennzeichnung\Admin\Port\ScanPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Service\ImageScanResult;
use Plugin\MGD_AI_Kennzeichnung\Service\ImageScanService;

/** Bindet den bestehenden Scanner ohne globale Zugriffe an die Admin-Aktion. */
final class ImageScanServiceAdapter implements ScanPortInterface
{
    public function __construct(private readonly ImageScanService $scanner) {}

    public function scan(): ImageScanResult
    {
        return $this->scanner->scan();
    }
}
