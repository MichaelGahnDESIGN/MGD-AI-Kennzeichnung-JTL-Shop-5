<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Port;

/** Schreibt den dauerhaften, datensparsamen Einmal-Claim vor einer Admin-Mutation. */
interface ConfirmationClaimRepositoryInterface
{
    public function claim(string $token, int $expiresAt): void;
}
