<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Http;

/** Unveränderlicher, noch unvalidierter HTTP-Schnappschuss. */
final class AdminHttpRequest
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    public function __construct(
        public readonly string $method,
        public readonly array $query,
        public readonly array $post,
    ) {}
}
