<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Adapter;

use JTL\Backend\AdminAccount;
use JTL\Backend\Permissions;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\AccessDeniedException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AuthorizationPortInterface;

/** Bindet die Admin-Autorisierung an JTL-Shop 5.7.2 AdminAccount::permission(). */
final class JtlAuthorizationAdapter implements AuthorizationPortInterface
{
    public function __construct(
        private readonly AdminAccount $account,
        private readonly int $pluginId,
        private readonly string $sessionId,
    ) {}

    public function assertCanManageAssets(): void
    {
        $allowed = $this->account->permission(Permissions::PLUGIN_DETAIL_VIEW_ALL)
            || $this->account->permission(Permissions::PLUGIN_DETAIL_VIEW_ID . $this->pluginId);
        if (!$allowed) {
            throw new AccessDeniedException('Keine Berechtigung für die Bildverwaltung.');
        }
    }

    public function subjectKey(): string
    {
        return hash('sha256', $this->account->getID() . "\0" . $this->sessionId . "\0" . $this->pluginId);
    }
}
