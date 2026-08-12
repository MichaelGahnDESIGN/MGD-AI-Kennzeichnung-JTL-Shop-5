<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Action;

use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\AssetNotFoundException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AdminAssetRepositoryInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AuthorizationPortInterface;

/** Lädt Details getrennt von der paginierten Zusammenfassung. */
final class AssetDetailAction
{
    public function __construct(
        private readonly AuthorizationPortInterface $authorization,
        private readonly AdminAssetRepositoryInterface $assets,
    ) {}

    /** @return array<string, scalar|null> */
    public function load(mixed $assetId): array
    {
        $this->authorization->assertCanManageAssets();
        if (!is_int($assetId) || $assetId < 1) {
            throw new ValidationException('Die Asset-ID muss eine positive Ganzzahl sein.');
        }
        $detail = $this->assets->detailById($assetId);
        if ($detail === null) {
            throw new AssetNotFoundException('Das Asset wurde nicht gefunden.');
        }

        return $detail;
    }
}
