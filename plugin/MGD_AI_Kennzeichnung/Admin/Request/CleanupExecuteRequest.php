<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Request;

/** Ausführung einer serverseitig gespeicherten Bereinigungsvorschau. */
final class CleanupExecuteRequest
{
    public function __construct(
        public readonly string $csrfToken,
        public readonly string $confirmationToken,
    ) {}
}
