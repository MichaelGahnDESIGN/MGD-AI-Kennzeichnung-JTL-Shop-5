<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Port;

use Plugin\MGD_AI_Kennzeichnung\Admin\Value\StoredOperation;

/**
 * Verwaltet kurzlebige Einmalbestätigungen serverseitig. Der Browser erhält
 * lediglich ein undurchsichtiges Token, niemals das Servergeheimnis.
 */
interface ConfirmationPortInterface
{
    public function issue(string $subjectKey, StoredOperation $operation): string;

    public function consume(string $subjectKey, string $token): ?StoredOperation;
}
