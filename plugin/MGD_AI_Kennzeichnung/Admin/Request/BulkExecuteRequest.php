<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Request;

/** Der Browser liefert zur Ausführung nur CSRF- und undurchsichtiges Einmaltoken. */
final class BulkExecuteRequest
{
    public function __construct(
        public readonly string $csrfToken,
        public readonly string $confirmationToken,
    ) {}
}
