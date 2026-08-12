<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Action;

use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ConfirmationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AuthorizationPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\CleanupRepositoryInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\ConfirmationPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\CsrfPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Result\CleanupResult;

/** Entfernt nur ausgewählte veraltete Nutzungszeilen, niemals Assets oder Bilddateien. */
final class CleanupAction
{
    public function __construct(
        private readonly AuthorizationPortInterface $authorization,
        private readonly CsrfPortInterface $csrf,
        private readonly ConfirmationPortInterface $confirmation,
        private readonly CleanupRepositoryInterface $usages,
    ) {}

    public function execute(string $csrfToken, string $confirmationToken): CleanupResult
    {
        $this->authorization->assertCanManageAssets();
        $this->csrf->assertValid($csrfToken);
        $operation = $this->confirmation->consume($this->authorization->subjectKey(), $confirmationToken);
        if ($operation === null || $operation->name !== 'cleanup-stale-usages' || $operation->changes !== []) {
            throw new ConfirmationException('Die Bereinigungsbestätigung ist ungültig oder abgelaufen.');
        }
        $ids = AdminInputValidator::ids($operation->ids);
        $this->usages->cleanupOwnedStaleUsages($ids);

        return new CleanupResult(count($ids));
    }
}
