<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

require_once __DIR__ . '/../../Stubs/JtlDatabaseStubs.php';

use JTL\DB\DbInterface;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlLockedConfirmationPort;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\ConfirmationPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Value\ConfirmationLease;
use Plugin\MGD_AI_Kennzeichnung\Admin\Value\StoredOperation;
use RuntimeException;
use stdClass;

final class JtlLockedConfirmationPortTest extends TestCase
{
    #[Test]
    public function parallele_stale_session_snapshots_koennen_nur_eine_lease_halten(): void
    {
        $db = new AdvisoryLockDatabaseFake();
        $first = new JtlLockedConfirmationPort(new StaleConfirmationPort(), $db);
        $second = new JtlLockedConfirmationPort(new StaleConfirmationPort(), $db);
        $token = str_repeat('a', 64);

        $lease = $first->consume('admin-subjekt', $token);
        self::assertInstanceOf(ConfirmationLease::class, $lease);
        self::assertNull($second->consume('admin-subjekt', $token));
        self::assertStringNotContainsString($token, implode("\n", $db->sql));
        self::assertSame([0, 0], $db->timeouts);

        $lease->release();
        self::assertFalse($db->locked);
    }

    #[Test]
    public function fehlgeschlagene_lock_freigabe_wird_sichtbar_eskaliert(): void
    {
        $db = new AdvisoryLockDatabaseFake();
        $db->releaseSucceeds = false;
        $lease = (new JtlLockedConfirmationPort(new StaleConfirmationPort(), $db))
            ->consume('admin-subjekt', str_repeat('b', 64));
        self::assertInstanceOf(ConfirmationLease::class, $lease);

        $this->expectException(RuntimeException::class);
        $lease->release();
    }

    #[Test]
    public function advisory_lock_wird_auch_bei_innerem_leasefehler_freigegeben(): void
    {
        $db = new AdvisoryLockDatabaseFake();
        $lease = (new JtlLockedConfirmationPort(new StaleConfirmationPort(true), $db))
            ->consume('admin-subjekt', str_repeat('c', 64));
        self::assertNotNull($lease);

        try {
            $lease->release();
            self::fail('Der innere Leasefehler muss sichtbar bleiben.');
        } catch (RuntimeException) {
            self::assertFalse($db->locked);
        }
    }
}

final class StaleConfirmationPort implements ConfirmationPortInterface
{
    public function __construct(private readonly bool $releaseFails = false) {}

    public function issue(string $subjectKey, StoredOperation $operation): string
    {
        return str_repeat('a', 64);
    }

    public function consume(string $subjectKey, string $token): ?ConfirmationLease
    {
        if ($token === '') {
            return null;
        }

        return new ConfirmationLease(
            new StoredOperation('asset-bulk-update', [1], ['status' => 'generated']),
            function (): void {
                if ($this->releaseFails) {
                    throw new RuntimeException('Innerer Leasefehler.');
                }
            },
        );
    }
}

final class AdvisoryLockDatabaseFake implements DbInterface
{
    public bool $locked = false;
    public bool $releaseSucceeds = true;
    /** @var list<string> */
    public array $sql = [];
    /** @var list<int> */
    public array $timeouts = [];

    public function getPDO(): PDO
    {
        throw new RuntimeException('Nicht benötigt.');
    }

    public function getObjects(string $stmt, array $params = []): array
    {
        return [];
    }

    public function getSingleObject(string $stmt, array $params = []): ?stdClass
    {
        $this->sql[] = $stmt;
        if (str_contains($stmt, 'GET_LOCK')) {
            $timeout = $params['timeout'] ?? null;
            if (is_int($timeout)) {
                $this->timeouts[] = $timeout;
            }
            if ($this->locked) {
                return (object) ['acquired' => '0'];
            }
            $this->locked = true;

            return (object) ['acquired' => '1'];
        }
        if (str_contains($stmt, 'RELEASE_LOCK')) {
            $released = $this->releaseSucceeds && $this->locked;
            $this->locked = false;

            return (object) ['released' => $released ? '1' : '0'];
        }

        return null;
    }

    public function getAffectedRows(string $stmt, array $params = []): int
    {
        return 0;
    }

    public function beginTransaction(): bool
    {
        return true;
    }
    public function commit(): bool
    {
        return true;
    }
    public function rollback(): bool
    {
        return true;
    }
}
