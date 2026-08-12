<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Port;

/** Kapselt JTLs Berechtigungsprüfung und eine pseudonyme Sitzungsbindung. */
interface AuthorizationPortInterface
{
    public function assertCanManageAssets(): void;

    public function subjectKey(): string;
}
