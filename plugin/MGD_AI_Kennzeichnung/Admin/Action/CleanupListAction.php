<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Action;

use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AuthorizationPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\CleanupRepositoryInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel\CleanupListView;

/** Lädt begrenzt nur bereits als fehlend markierte Plugin-Fundstellen. */
final class CleanupListAction
{
    public function __construct(
        private readonly AuthorizationPortInterface $authorization,
        private readonly CleanupRepositoryInterface $usages,
    ) {}

    public function load(int $page, int $pageSize): CleanupListView
    {
        $this->authorization->assertCanManageAssets();

        return new CleanupListView(
            $this->usages->listOwnedStaleUsages(($page - 1) * $pageSize, $pageSize),
            $this->usages->countOwnedStaleUsages(),
            $page,
            $pageSize,
        );
    }
}
