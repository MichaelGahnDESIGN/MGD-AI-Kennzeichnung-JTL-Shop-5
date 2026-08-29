<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Port;

use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Value\UpdateNotice;

/** Kapselt die optionale, ausschließlich hinweisende Updateprüfung. */
interface UpdateCheckerInterface
{
    public function check(bool $enabled, string $currentVersion): ?UpdateNotice;
}
