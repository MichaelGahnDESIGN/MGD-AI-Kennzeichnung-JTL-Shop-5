<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Port;

/**
 * Verwaltet kurzlebige Einmalbestätigungen serverseitig. Der Browser erhält
 * lediglich ein undurchsichtiges Token, niemals das Servergeheimnis.
 */
interface ConfirmationPortInterface
{
    public function issue(string $subjectKey, string $operationDigest): string;

    public function consume(string $subjectKey, string $operationDigest, string $token): bool;
}
