<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Action;

use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AuthorizationPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\CsrfPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\LogPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\ScanPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Result\ScanActionResult;
use Throwable;

/** Startet den Scanner erst nach beiden Sicherheitsprüfungen. */
final class ScanAction
{
    public function __construct(
        private readonly AuthorizationPortInterface $authorization,
        private readonly CsrfPortInterface $csrf,
        private readonly ScanPortInterface $scanner,
        private readonly LogPortInterface $logger,
    ) {}

    public function execute(string $csrfToken): ScanActionResult
    {
        $this->authorization->assertCanManageAssets();
        $this->csrf->assertValid($csrfToken);
        try {
            $result = $this->scanner->scan();

            return new ScanActionResult(true, 'Der Bildscan wurde abgeschlossen.', $result->createdAssets, $result->recordedUsages);
        } catch (Throwable) {
            /* Ausnahme, Anfrage, Pfade und Benutzerbezug dürfen niemals in das Log gelangen. */
            $this->logger->event('admin_scan_failed', 0);

            return new ScanActionResult(false, 'Der Bildscan konnte nicht abgeschlossen werden.');
        }
    }
}
