<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Action;

use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ConfirmationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AdminAssetRepositoryInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AuthorizationPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\ConfirmationPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\CsrfPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Result\BulkUpdateResult;

/** Führt genau eine zuvor bestätigte, atomare Stapeländerung aus. */
final class BulkUpdateAction
{
    public function __construct(
        private readonly AuthorizationPortInterface $authorization,
        private readonly CsrfPortInterface $csrf,
        private readonly ConfirmationPortInterface $confirmation,
        private readonly AdminAssetRepositoryInterface $assets,
    ) {}

    /**
     * @param array<mixed> $ids
     * @param array<string, mixed> $mask
     * @param array<string, mixed> $values
     */
    public function execute(string $csrfToken, array $ids, array $mask, array $values, string $confirmationToken): BulkUpdateResult
    {
        /* Diese Reihenfolge ist Teil des Sicherheitsvertrags und darf nicht vertauscht werden. */
        $this->authorization->assertCanManageAssets();
        $this->csrf->assertValid($csrfToken);
        $normalIds = AdminInputValidator::ids($ids);
        $changes = AdminInputValidator::changes($mask, $values);
        $validConfirmation = $this->confirmation->consume(
            $this->authorization->subjectKey(),
            AdminInputValidator::operationDigest($normalIds, $changes),
            $confirmationToken,
        );
        if (!$validConfirmation) {
            throw new ConfirmationException('Die Vorschau-Bestätigung ist ungültig oder abgelaufen.');
        }
        $this->assets->updateManyByIds($normalIds, $changes);

        return new BulkUpdateResult(count($normalIds));
    }
}
