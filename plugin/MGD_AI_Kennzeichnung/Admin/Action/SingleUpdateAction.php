<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Action;

use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AdminAssetRepositoryInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AuthorizationPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\CsrfPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Result\SingleUpdateResult;

/** Ändert genau ein Asset und übergibt nur ausdrücklich maskierte Felder. */
final class SingleUpdateAction
{
    public function __construct(
        private readonly AuthorizationPortInterface $authorization,
        private readonly CsrfPortInterface $csrf,
        private readonly AdminAssetRepositoryInterface $assets,
    ) {}

    /**
     * @param array<string, mixed> $mask
     * @param array<string, mixed> $values
     */
    public function execute(string $csrfToken, mixed $assetId, array $mask, array $values): SingleUpdateResult
    {
        $this->authorization->assertCanManageAssets();
        $this->csrf->assertValid($csrfToken);
        if (!is_int($assetId) || $assetId < 1) {
            throw new ValidationException('Die Asset-ID muss eine positive Ganzzahl sein.');
        }
        $changes = AdminInputValidator::changes($mask, $values);
        $this->assets->updateOneById($assetId, $changes);

        return new SingleUpdateResult($assetId);
    }
}
