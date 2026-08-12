<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Request;

/** Typisierte Eingabe für genau eine maskierte Assetänderung. */
final class SingleUpdateRequest
{
    /**
     * @param array<string, bool>   $mask
     * @param array<string, string> $values
     */
    public function __construct(
        public readonly string $csrfToken,
        public readonly int $assetId,
        public readonly array $mask,
        public readonly array $values,
    ) {}
}
