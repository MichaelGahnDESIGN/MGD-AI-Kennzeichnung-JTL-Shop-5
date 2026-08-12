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

    /** @param array<mixed> $usageIds */
    public function execute(string $csrfToken, array $usageIds, string $confirmationToken): CleanupResult
    {
        $this->authorization->assertCanManageAssets();
        $this->csrf->assertValid($csrfToken);
        $ids = AdminInputValidator::ids($usageIds);
        $digest = AdminInputValidator::operationDigest($ids, [], 'cleanup-stale-usages');
        if (!$this->confirmation->consume($this->authorization->subjectKey(), $digest, $confirmationToken)) {
            throw new ConfirmationException('Die Bereinigungsbestätigung ist ungültig oder abgelaufen.');
        }
        $this->usages->cleanupOwnedStaleUsages($ids);

        return new CleanupResult(count($ids));
    }
}
