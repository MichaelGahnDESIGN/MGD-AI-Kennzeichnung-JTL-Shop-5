<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Port;

use Plugin\MGD_AI_Kennzeichnung\Service\ImageScanResult;

/** Schmale Grenze zum bestehenden Bildscanner. */
interface ScanPortInterface
{
    public function scan(): ImageScanResult;
}
