<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Request;

/** Streng normalisierte Eingabe für eine schreibfreie Stapelvorschau. */
final class BulkPreviewRequest
{
    /**
     * @param list<int>             $assetIds
     * @param array<string, bool>   $mask
     * @param array<string, string> $values
     */
    public function __construct(
        public readonly string $csrfToken,
        public readonly array $assetIds,
        public readonly array $mask,
        public readonly array $values,
    ) {}
}
