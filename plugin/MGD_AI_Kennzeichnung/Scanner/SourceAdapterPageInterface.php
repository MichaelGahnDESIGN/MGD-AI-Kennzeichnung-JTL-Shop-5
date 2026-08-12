<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Scanner;

/** Ergänzt den verbindlichen Iterable-Vertrag um die rohe DB-Seitengröße. */
interface SourceAdapterPageInterface
{
    public function scanPage(int $offset, int $limit): SourceScanPage;
}
