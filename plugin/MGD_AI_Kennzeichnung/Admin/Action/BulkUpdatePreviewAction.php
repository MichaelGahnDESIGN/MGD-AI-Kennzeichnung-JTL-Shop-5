<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Action;

use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\AssetNotFoundException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AdminAssetRepositoryInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AuthorizationPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\ConfirmationPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Result\BulkUpdatePreviewResult;
use Plugin\MGD_AI_Kennzeichnung\Admin\Value\StoredOperation;

/** Erstellt eine schreibfreie, serverseitig gebundene Stapelvorschau. */
final class BulkUpdatePreviewAction
{
    public function __construct(
        private readonly AuthorizationPortInterface $authorization,
        private readonly ConfirmationPortInterface $confirmation,
        private readonly AdminAssetRepositoryInterface $assets,
    ) {}

    /**
     * @param array<mixed> $ids
     * @param array<string, mixed> $mask
     * @param array<string, mixed> $values
     */
    public function preview(array $ids, array $mask, array $values): BulkUpdatePreviewResult
    {
        $this->authorization->assertCanManageAssets();
        $normalIds = AdminInputValidator::ids($ids);
        $changes = AdminInputValidator::changes($mask, $values);
        if ($this->assets->countExistingIds($normalIds) !== count($normalIds)) {
            throw new AssetNotFoundException('Mindestens ein ausgewähltes Asset ist nicht mehr vorhanden.');
        }
        $token = $this->confirmation->issue(
            $this->authorization->subjectKey(),
            new StoredOperation('asset-bulk-update', $normalIds, $changes),
        );

        return new BulkUpdatePreviewResult(count($normalIds), array_keys($changes), $changes, $token);
    }
}
