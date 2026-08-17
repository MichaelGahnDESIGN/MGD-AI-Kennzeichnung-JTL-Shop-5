<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Value;

/** Sichere Hinweisdaten ohne Download- oder Installationsfunktion. */
final class UpdateNotice
{
    public function __construct(
        public readonly string $tag,
        public readonly string $url,
    ) {}
}
