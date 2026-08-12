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

    public function execute(string $csrfToken, string $confirmationToken): BulkUpdateResult
    {
        /* Diese Reihenfolge ist Teil des Sicherheitsvertrags und darf nicht vertauscht werden. */
        $this->authorization->assertCanManageAssets();
        $this->csrf->assertValid($csrfToken);
        $operation = $this->confirmation->consume($this->authorization->subjectKey(), $confirmationToken);
        if ($operation === null || $operation->name !== 'asset-bulk-update') {
            throw new ConfirmationException('Die Vorschau-Bestätigung ist ungültig oder abgelaufen.');
        }
        /* Auch serverseitige Sitzungsdaten werden vor der Mutation erneut geschlossen validiert. */
        $ids = AdminInputValidator::ids($operation->ids);
        $mask = array_fill_keys(array_keys($operation->changes), true);
        $changes = AdminInputValidator::changes($mask, $operation->changes);
        $this->assets->updateManyByIds($ids, $changes);

        return new BulkUpdateResult(count($ids));
    }
}
