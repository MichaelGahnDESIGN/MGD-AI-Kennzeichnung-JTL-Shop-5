<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database;

use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ConfirmationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\ConfirmationClaimRepositoryInterface;
use JTL\DB\DbInterface;
use RuntimeException;
use Throwable;

/** Persistiert ausschließlich irreversible Hashes zur atomaren Replay-Abwehr. */
final class ConfirmationClaimRepository implements ConfirmationClaimRepositoryInterface
{
    public const TABLE = 'xplugin_mgd_ai_confirmation_claim';

    private readonly ConfirmationClaimSchemaGuard $guard;

    public function __construct(private readonly DbInterface $db)
    {
        $this->guard = new ConfirmationClaimSchemaGuard($db);
    }

    public function claim(string $subjectKey, string $token, int $expiresAt): void
    {
        if (preg_match('/^[a-f0-9]{64}$/D', $subjectKey) !== 1
            || preg_match('/^[a-f0-9]{64}$/D', $token) !== 1
            || $expiresAt <= time()) {
            throw new ConfirmationException('Die Bestätigung ist ungültig oder abgelaufen.');
        }
        if ($this->db->getPDO()->inTransaction()) {
            throw new RuntimeException('Bestätigungs-Claim ist innerhalb einer äußeren Transaktion nicht zulässig.');
        }

        $this->guard->assertOwned();
        try {
            $affected = $this->db->getAffectedRows(
                <<<'SQL'
                    INSERT IGNORE INTO `xplugin_mgd_ai_confirmation_claim`
                        (`token_hash`, `subject_hash`, `expires_at`, `claimed_at`)
                    VALUES (:token_hash, :subject_hash, :expires_at, CURRENT_TIMESTAMP(6))
                    SQL,
                [
                    'token_hash' => hash('sha256', $token),
                    'subject_hash' => hash('sha256', $subjectKey),
                    'expires_at' => gmdate('Y-m-d H:i:s', $expiresAt),
                ],
            );
        } catch (Throwable $fehler) {
            throw new RuntimeException('Der sichere Bestätigungs-Claim konnte nicht gespeichert werden.', 0, $fehler);
        }
        if ($affected !== 1) {
            throw new ConfirmationException('Die Bestätigung ist ungültig oder bereits verwendet.');
        }
    }
}
