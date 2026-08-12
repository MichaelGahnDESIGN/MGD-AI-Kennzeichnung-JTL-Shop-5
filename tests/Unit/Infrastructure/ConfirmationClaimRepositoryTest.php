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
        self::assertSame(
            ['token_hash', 'expires_at_value', 'expires_at_guard'],
            array_keys($statement['params']),
        );
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
        self::assertStringContainsString('- INTERVAL 1 DAY', $purges[0]['sql']);
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

    #[Test]
    public function kurz_abgelaufener_claim_bleibt_als_replaysperre_erhalten(): void
    {
        $db = $this->ownedDatabase();
        $token = str_repeat('8', 64);
        $db->seedConfirmationClaimForTest(hash('sha256', $token), gmdate('Y-m-d H:i:s'));

        $this->expectException(ConfirmationException::class);
        (new ConfirmationClaimRepository($db))->claim($token, time() + 10);
    }

    #[Test]
    public function datenbankzeit_lehnt_bei_vorauslaufender_db_uhr_abgelaufenen_neuen_claim_ab(): void
    {
        $db = $this->ownedDatabase();
        $db->setConfirmationDatabaseNowForTest(gmdate('Y-m-d H:i:s', time() + 600));
        $token = str_repeat('7', 64);

        try {
            (new ConfirmationClaimRepository($db))->claim($token, time() + 300);
            self::fail('Die autoritative Datenbankzeit muss den abgelaufenen Claim abweisen.');
        } catch (ConfirmationException) {
            self::assertFalse($db->hasConfirmationClaimForTest(hash('sha256', $token)));
        }
    }

    #[Test]
    public function insert_prueft_ablauf_atomar_mit_zwei_eindeutigen_bindings(): void
    {
        $db = $this->ownedDatabase();
        (new ConfirmationClaimRepository($db))->claim(str_repeat('6', 64), time() + 300);

        $insert = array_values(array_filter(
            $db->statements,
            static fn(array $entry): bool => str_contains($entry['sql'], 'INSERT IGNORE'),
        ))[0];
        self::assertStringContainsString('SELECT :token_hash, :expires_at_value, UTC_TIMESTAMP(6)', $insert['sql']);
        self::assertStringContainsString('WHERE :expires_at_guard > UTC_TIMESTAMP(6)', $insert['sql']);
        self::assertSame(
            $insert['params']['expires_at_value'],
            $insert['params']['expires_at_guard'],
        );
    }

    #[Test]
    public function purge_loescht_nur_mehr_als_einen_tag_abgelaufene_claims(): void
    {
        $db = $this->ownedDatabase();
        $now = time();
        $db->setConfirmationDatabaseNowForTest(gmdate('Y-m-d H:i:s', $now));
        $old = str_repeat('3', 64);
        $recent = str_repeat('4', 64);
        $live = str_repeat('5', 64);
        $db->seedConfirmationClaimForTest($old, gmdate('Y-m-d H:i:s', $now - 90000));
        $db->seedConfirmationClaimForTest($recent, gmdate('Y-m-d H:i:s', $now - 3600));
        $db->seedConfirmationClaimForTest($live, gmdate('Y-m-d H:i:s', $now + 3600));

        (new ConfirmationClaimRepository($db))->claim(str_repeat('a', 64), $now + 300);

        self::assertFalse($db->hasConfirmationClaimForTest($old));
        self::assertTrue($db->hasConfirmationClaimForTest($recent));
        self::assertTrue($db->hasConfirmationClaimForTest($live));
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
