<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Adapter;

use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ConfirmationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\ConfirmationClaimRepositoryInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\ConfirmationPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Value\ConfirmationLease;
use Plugin\MGD_AI_Kennzeichnung\Admin\Value\StoredOperation;
use Throwable;

/**
 * Ergänzt die Session-Bestätigung um einen persistenten atomaren Claim.
 * Damit kann auch ein bereits gelesener, veralteter Session-Snapshot kein
 * zweites Mal mutieren. Ein Prozessabbruch verbrennt das Token bewusst sicher.
 */
final class DatabaseClaimingConfirmationPort implements ConfirmationPortInterface
{
    public function __construct(
        private readonly ConfirmationPortInterface $inner,
        private readonly ConfirmationClaimRepositoryInterface $claims,
    ) {}

    public function issue(string $subjectKey, StoredOperation $operation): string
    {
        return $this->inner->issue($subjectKey, $operation);
    }

    public function consume(string $subjectKey, string $token): ?ConfirmationLease
    {
        $innerLease = $this->inner->consume($subjectKey, $token);
        if ($innerLease === null) {
            return null;
        }
        if ($innerLease->expiresAt <= time()) {
            $innerLease->release();

            return null;
        }

        try {
            $this->claims->claim($token, $innerLease->expiresAt);
        } catch (Throwable $fehler) {
            try {
                $innerLease->release();
            } catch (Throwable) {
                /* Der dauerhafte Claimfehler bleibt die sicherheitsrelevante Ursache. */
            }
            throw $fehler;
        }

        return new ConfirmationLease(
            $innerLease->operation,
            static function () use ($innerLease): void {
                $innerLease->release();
            },
            $innerLease->expiresAt,
        );
    }
}
