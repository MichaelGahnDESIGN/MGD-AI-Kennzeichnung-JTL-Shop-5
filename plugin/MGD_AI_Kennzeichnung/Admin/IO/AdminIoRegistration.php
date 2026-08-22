<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\IO;

use JTL\Backend\AdminIO;

/** Registriert ausschließlich die beiden dokumentierten Plugin-Funktionen. */
final class AdminIoRegistration
{
    public function __construct(
        private readonly LoadLocalAssetLabel $load,
        private readonly SaveLocalAssetLabel $save,
    ) {}

    public function register(AdminIO $io): void
    {
        /* JTL prüft vor diesem Hook bereits Admin-Session und CSRF-Token. */
        $io->register('mgd_ai_label_load', $this->load);
        $io->register('mgd_ai_label_save', $this->save);
    }
}
