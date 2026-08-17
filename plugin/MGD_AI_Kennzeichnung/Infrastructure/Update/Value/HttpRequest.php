<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Value;

/** Beschreibt den vollständigen und begrenzten Netzwerkauftrag. */
final class HttpRequest
{
    /** @param array<string, string> $headers Fest definierte HTTP-Kopfzeilen */
    public function __construct(
        public readonly string $url,
        public readonly array $headers,
        public readonly int $connectTimeoutSeconds,
        public readonly int $totalTimeoutSeconds,
        public readonly bool $verifyTls,
        public readonly bool $followRedirects,
        public readonly int $maximumResponseBytes,
    ) {}
}
