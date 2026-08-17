<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Value;

/** Enthält nur die für die Release-Prüfung erforderlichen Antwortdaten. */
final class HttpResponse
{
    /** @param array<string, string> $headers Empfangene HTTP-Kopfzeilen */
    public function __construct(
        public readonly int $statusCode,
        public readonly string $body,
        public readonly array $headers,
    ) {}
}
