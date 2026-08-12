<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Value;

use Closure;

/** Hält eine Bestätigungsreservierung bis zum Ende der zugehörigen Mutation. */
final class ConfirmationLease
{
    private readonly Closure $releaseCallback;
    private bool $released = false;

    public function __construct(public readonly StoredOperation $operation, callable $release)
    {
        $this->releaseCallback = Closure::fromCallable($release);
    }

    /** Gibt die Reservierung idempotent frei. Fehler werden bewusst nicht verschluckt. */
    public function release(): void
    {
        if ($this->released) {
            return;
        }
        ($this->releaseCallback)();
        $this->released = true;
    }
}
