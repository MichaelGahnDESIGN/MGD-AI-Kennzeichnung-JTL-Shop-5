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
        $token = str_repeat('a', 64);

        $repository->claim($token, time() + 300);

        $statement = array_values(array_filter(
            $db->statements,
            static fn(array $entry): bool => str_contains($entry['sql'], 'INSERT IGNORE'),
        ))[0];
        self::assertSame(['token_hash', 'expires_at'], array_keys($statement['params']));
        $tokenHash = $statement['params']['token_hash'];
        self::assertIsString($tokenHash);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $tokenHash);
        self::assertNotSame($token, $statement['params']['token_hash']);
        self::assertStringNotContainsString($token, $statement['sql']);
        self::assertStringNotContainsString('subject', $statement['sql']);
    }

    #[Test]
    public function claim_lehnt_aeussere_transaktion_vor_insert_ab(): void
    {
        $db = $this->ownedDatabase();
        $db->beginOuterTransactionForTest();

        try {
            (new ConfirmationClaimRepository($db))->claim(str_repeat('b', 64), time() + 10);
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
        $token = str_repeat('c', 64);
        $repository->claim($token, time() + 10);

        $this->expectException(ConfirmationException::class);
        $repository->claim($token, time() + 10);
    }

    #[Test]
    public function datenbankfehler_wird_generisch_eskaliert_ohne_token_oder_subjekt(): void
    {
        $db = $this->ownedDatabase();
        $db->failConfirmationClaimsForTest();
        $token = str_repeat('d', 64);

        try {
            (new ConfirmationClaimRepository($db))->claim($token, time() + 10);
            self::fail('Der Datenbankfehler muss sichtbar sein.');
        } catch (RuntimeException $exception) {
            self::assertStringNotContainsString($token, $exception->getMessage());
            self::assertStringContainsString('Bestätigungs-Claim', $exception->getMessage());
        }
    }

    #[Test]
    public function claim_bereinigt_genau_einen_begrenzten_batch_abgelaufener_hashes_vor_dem_insert(): void
    {
        $db = $this->ownedDatabase();
        $db->seedConfirmationClaimForTest(str_repeat('1', 64), '2000-01-01 00:00:00');
        $db->seedConfirmationClaimForTest(str_repeat('2', 64), '2999-01-01 00:00:00');

        (new ConfirmationClaimRepository($db))->claim(
            str_repeat('e', 64),
            time() + 10,
        );

        $purges = array_values(array_filter(
            $db->statements,
            static fn(array $entry): bool => str_starts_with(ltrim($entry['sql']), 'DELETE'),
        ));
        self::assertCount(1, $purges);
        self::assertSame([], $purges[0]['params']);
        self::assertStringContainsString('`expires_at` <= UTC_TIMESTAMP(6)', $purges[0]['sql']);
        self::assertStringContainsString('ORDER BY `expires_at`', $purges[0]['sql']);
        self::assertStringContainsString('LIMIT 1000', $purges[0]['sql']);
        self::assertFalse($db->hasConfirmationClaimForTest(str_repeat('1', 64)));
        self::assertTrue($db->hasConfirmationClaimForTest(str_repeat('2', 64)));
    }

    #[Test]
    public function purge_fehler_verhindert_den_neuen_claim_fail_closed(): void
    {
        $db = $this->ownedDatabase();
        $db->failConfirmationClaimPurgeForTest();

        try {
            (new ConfirmationClaimRepository($db))->claim(
                str_repeat('f', 64),
                time() + 10,
            );
            self::fail('Ein Purge-Fehler muss den Claim verhindern.');
        } catch (RuntimeException) {
            self::assertFalse($this->containsInsert($db));
        }
    }

    #[Test]
    public function purge_entfernt_auch_bei_grossem_rueckstand_hoechstens_tausend_zeilen(): void
    {
        $db = $this->ownedDatabase();
        for ($index = 0; $index < 1001; ++$index) {
            $db->seedConfirmationClaimForTest(hash('sha256', 'alt-' . $index), '2000-01-01 00:00:00');
        }

        (new ConfirmationClaimRepository($db))->claim(str_repeat('9', 64), time() + 10);

        /* Eine alte Zeile plus der soeben geschriebene neue Claim bleiben übrig. */
        self::assertSame(2, $db->confirmationClaimCountForTest());
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
