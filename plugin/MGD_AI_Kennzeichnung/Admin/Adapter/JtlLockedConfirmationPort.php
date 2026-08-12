<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Adapter;

use JTL\DB\DbInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\ConfirmationPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Value\ConfirmationLease;
use Plugin\MGD_AI_Kennzeichnung\Admin\Value\StoredOperation;
use RuntimeException;
use Throwable;

/**
 * Sperrt einen Bestätigungsvorgang verbindungsübergreifend per MySQL-Advisory-
 * Lock. Der feste Lockname enthält ausschließlich einen lokalen SHA-256-Ausschnitt.
 */
final class JtlLockedConfirmationPort implements ConfirmationPortInterface
{
    public function __construct(
        private readonly ConfirmationPortInterface $inner,
        private readonly DbInterface $db,
    ) {}

    public function issue(string $subjectKey, StoredOperation $operation): string
    {
        return $this->inner->issue($subjectKey, $operation);
    }

    public function consume(string $subjectKey, string $token): ?ConfirmationLease
    {
        $lockName = 'mgd_ai_confirm_' . substr(hash('sha256', $subjectKey . "\0" . $token), 0, 48);
        $row = $this->db->getSingleObject(
            'SELECT GET_LOCK(:lock_name, :timeout) AS `acquired`',
            ['lock_name' => $lockName, 'timeout' => 0],
        );
        $acquired = $row === null ? null : ($row->acquired ?? null);
        if (!$this->isOne($acquired)) {
            return null;
        }

        try {
            $innerLease = $this->inner->consume($subjectKey, $token);
            if ($innerLease === null) {
                $this->releaseLock($lockName);

                return null;
            }

            return new ConfirmationLease(
                $innerLease->operation,
                function () use ($innerLease, $lockName): void {
                    $innerError = null;
                    try {
                        $innerLease->release();
                    } catch (Throwable $error) {
                        $innerError = $error;
                    }
                    try {
                        $this->releaseLock($lockName);
                    } catch (Throwable $releaseError) {
                        throw new RuntimeException(
                            'Innere und JTL-Bestätigungsreservierung konnten nicht freigegeben werden.',
                            0,
                            $innerError ?? $releaseError,
                        );
                    }
                    if ($innerError !== null) {
                        throw $innerError;
                    }
                },
            );
        } catch (Throwable $error) {
            try {
                $this->releaseLock($lockName);
            } catch (Throwable) {
                throw new RuntimeException('Bestätigungsreservierung und Freigabe sind fehlgeschlagen.', 0, $error);
            }
            throw $error;
        }
    }

    private function releaseLock(string $lockName): void
    {
        $row = $this->db->getSingleObject(
            'SELECT RELEASE_LOCK(:lock_name) AS `released`',
            ['lock_name' => $lockName],
        );
        $released = $row === null ? null : ($row->released ?? null);
        if (!$this->isOne($released)) {
            throw new RuntimeException('Die Bestätigungsreservierung konnte nicht freigegeben werden.');
        }
    }

    private function isOne(mixed $value): bool
    {
        return $value === 1 || $value === '1';
    }
}
