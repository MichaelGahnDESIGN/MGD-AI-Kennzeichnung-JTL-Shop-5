<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Value;

use InvalidArgumentException;

/**
 * Hält den Zeitpunkt jedes echten Abrufversuchs und optional dessen geprüftes
 * Release fest. So werden auch fehlgeschlagene GitHub-Anfragen datensparsam
 * für die Cache-Dauer gebremst.
 */
final class ReleaseCheckState
{
    public function __construct(
        public readonly int $attemptedAt,
        public readonly ?CachedRelease $release,
    ) {
        if ($attemptedAt < 0 || ($release !== null && $release->fetchedAt !== $attemptedAt)) {
            throw new InvalidArgumentException('Der Release-Cachezustand ist ungültig.');
        }
    }
}
