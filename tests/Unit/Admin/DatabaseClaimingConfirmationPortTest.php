<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\DatabaseClaimingConfirmationPort;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\OneTimeConfirmationAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\SessionConfirmationStore;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ConfirmationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Value\StoredOperation;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\ConfirmationClaimRepository;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\ConfirmationClaimSchemaGuard;
use Tests\Support\TransactionalDatabaseFake;

final class DatabaseClaimingConfirmationPortTest extends TestCase
{
    #[Test]
    public function zwei_veraltete_sessionsnapshots_duerfen_denselben_vorgang_nur_einmal_claimen(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker(
            ConfirmationClaimRepository::TABLE,
            ConfirmationClaimSchemaGuard::OWNERSHIP_MARKER,
        );
        $sessionA = [];
        $innerA = new OneTimeConfirmationAdapter(new SessionConfirmationStore($sessionA));
        $token = $innerA->issue(
            hash('sha256', 'admin-a'),
            new StoredOperation('asset-bulk-update', [1], ['status' => 'generated']),
        );
        /* Simuliert zwei PHP-Prozesse, die denselben alten JTL-Sessionstand gelesen haben. */
        $sessionB = $sessionA;
        $first = new DatabaseClaimingConfirmationPort($innerA, new ConfirmationClaimRepository($db));
        $second = new DatabaseClaimingConfirmationPort(
            new OneTimeConfirmationAdapter(new SessionConfirmationStore($sessionB)),
            new ConfirmationClaimRepository($db),
        );

        $lease = $first->consume(hash('sha256', 'admin-a'), $token);
        self::assertNotNull($lease);
        $lease->release();

        $this->expectException(ConfirmationException::class);
        $second->consume(hash('sha256', 'admin-a'), $token);
    }

    #[Test]
    public function prozessabbruch_nach_claim_verbrennt_token_sicher(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker(ConfirmationClaimRepository::TABLE, ConfirmationClaimSchemaGuard::OWNERSHIP_MARKER);
        $sessionA = [];
        $inner = new OneTimeConfirmationAdapter(new SessionConfirmationStore($sessionA));
        $subject = hash('sha256', 'admin-a');
        $token = $inner->issue(
            $subject,
            new StoredOperation('asset-bulk-update', [7], ['theme' => 'dark']),
        );
        $staleSession = $sessionA;

        /* Keine Mutation und kein release(): Der persistente Claim muss trotzdem gelten. */
        (new DatabaseClaimingConfirmationPort($inner, new ConfirmationClaimRepository($db)))
            ->consume($subject, $token);

        $this->expectException(ConfirmationException::class);
        (new DatabaseClaimingConfirmationPort(
            new OneTimeConfirmationAdapter(new SessionConfirmationStore($staleSession)),
            new ConfirmationClaimRepository($db),
        ))->consume($subject, $token);
    }
}
