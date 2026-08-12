<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Action;

use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\AssetNotFoundException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AuthorizationPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\CleanupRepositoryInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\ConfirmationPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Result\CleanupPreviewResult;
use Plugin\MGD_AI_Kennzeichnung\Admin\Value\StoredOperation;

/** Prüft Besitz und Veraltung, ohne Daten zu ändern. */
final class CleanupPreviewAction
{
    public function __construct(
        private readonly AuthorizationPortInterface $authorization,
        private readonly ConfirmationPortInterface $confirmation,
        private readonly CleanupRepositoryInterface $usages,
    ) {}

    /** @param array<mixed> $usageIds */
    public function preview(array $usageIds): CleanupPreviewResult
    {
        $this->authorization->assertCanManageAssets();
        $ids = AdminInputValidator::ids($usageIds);
        if ($this->usages->countOwnedStaleUsageIds($ids) !== count($ids)) {
            throw new AssetNotFoundException('Mindestens eine Fundstelle ist nicht bereinigungsfähig.');
        }
        $operation = new StoredOperation('cleanup-stale-usages', $ids, []);

        return new CleanupPreviewResult(
            count($ids),
            $this->confirmation->issue($this->authorization->subjectKey(), $operation),
        );
    }
}
