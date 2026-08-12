<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Result;

/** Ausschließlich darstellungsfertige, aber noch nicht geschriebene Vorschau. */
final class BulkUpdatePreviewResult
{
    /**
     * @param list<string> $fields
     * @param array<string, string> $targets
     */
    public function __construct(
        public readonly int $count,
        public readonly array $fields,
        public readonly array $targets,
        public readonly string $confirmationToken,
    ) {}
}
