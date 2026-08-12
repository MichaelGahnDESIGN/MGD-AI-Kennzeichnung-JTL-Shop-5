<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ConfirmationException;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\ConfirmationClaimRepository;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\ConfirmationClaimSchemaGuard;
use RuntimeException;
use Tests\Support\TransactionalDatabaseFake;

final class ConfirmationClaimRepositoryTest extends TestCase
{
    #[Test]
    public function claim_speichert_nur_hashes_und_ablaufzeit_mit_bindings(): void
    {
        $db = $this->ownedDatabase();
        $repository = new ConfirmationClaimRepository($db);
        $subject = hash('sha256', 'internes-admin-subjekt');
        $token = str_repeat('a', 64);

        $repository->claim($subject, $token, time() + 300);

        $statement = array_values(array_filter(
            $db->statements,
            static fn(array $entry): bool => str_contains($entry['sql'], 'INSERT IGNORE'),
        ))[0];
        self::assertSame(['token_hash', 'subject_hash', 'expires_at'], array_keys($statement['params']));
        $tokenHash = $statement['params']['token_hash'];
        $subjectHash = $statement['params']['subject_hash'];
        self::assertIsString($tokenHash);
        self::assertIsString($subjectHash);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $tokenHash);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $subjectHash);
        self::assertNotSame($token, $statement['params']['token_hash']);
        self::assertNotSame($subject, $statement['params']['subject_hash']);
        self::assertStringNotContainsString($token, $statement['sql']);
        self::assertStringNotContainsString($subject, $statement['sql']);
    }

    #[Test]
    public function claim_lehnt_aeussere_transaktion_vor_insert_ab(): void
    {
        $db = $this->ownedDatabase();
        $db->beginOuterTransactionForTest();

        try {
            (new ConfirmationClaimRepository($db))->claim(hash('sha256', 'subject'), str_repeat('b', 64), time() + 10);
            self::fail('Eine äußere Transaktion muss abgewiesen werden.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('Transaktion', $exception->getMessage());
            self::assertFalse($this->containsInsert($db));
        }
    }

    #[Test]
    public function doppelter_claim_wird_als_kontrollierter_replay_abgewiesen(): void
    {
        $db = $this->ownedDatabase();
        $repository = new ConfirmationClaimRepository($db);
        $subject = hash('sha256', 'subject');
        $token = str_repeat('c', 64);
        $repository->claim($subject, $token, time() + 10);

        $this->expectException(ConfirmationException::class);
        $repository->claim($subject, $token, time() + 10);
    }

    #[Test]
    public function datenbankfehler_wird_generisch_eskaliert_ohne_token_oder_subjekt(): void
    {
        $db = $this->ownedDatabase();
        $db->failConfirmationClaimsForTest();
        $subject = hash('sha256', 'vertrauliches-subjekt');
        $token = str_repeat('d', 64);

        try {
            (new ConfirmationClaimRepository($db))->claim($subject, $token, time() + 10);
            self::fail('Der Datenbankfehler muss sichtbar sein.');
        } catch (RuntimeException $exception) {
            self::assertStringNotContainsString($token, $exception->getMessage());
            self::assertStringNotContainsString($subject, $exception->getMessage());
            self::assertStringContainsString('Bestätigungs-Claim', $exception->getMessage());
        }
    }

    private function ownedDatabase(): TransactionalDatabaseFake
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker(ConfirmationClaimRepository::TABLE, ConfirmationClaimSchemaGuard::OWNERSHIP_MARKER);

        return $db;
    }

    private function containsInsert(TransactionalDatabaseFake $db): bool
    {
        foreach ($db->statements as $statement) {
            if (str_contains($statement['sql'], 'INSERT IGNORE')) {
                return true;
            }
        }

        return false;
    }
}
