<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Port;

use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Port\UpdateCheckerInterface;

/** Optionale Test- und Integrationsgrenze für den reinen Updatehinweis. */
interface UpdateCheckerProviderInterface
{
    public function getUpdateChecker(): UpdateCheckerInterface;
}
