<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Scanner\Filesystem;

use RuntimeException;

/** Nur dieser geschlossene Fehlerkatalog darf als Scan-Hinweis im Admin erscheinen. */
final class OpcStorageScanException extends RuntimeException
{
    public function __construct(public readonly OpcStorageScanFailure $failure)
    {
        parent::__construct($failure->message());
    }
}
