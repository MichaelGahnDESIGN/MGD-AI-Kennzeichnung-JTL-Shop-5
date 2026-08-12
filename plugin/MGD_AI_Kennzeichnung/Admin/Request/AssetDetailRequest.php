<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Request;

/** Kanonische technische ID einer Detailansicht. */
final class AssetDetailRequest
{
    public function __construct(public readonly int $assetId) {}
}
