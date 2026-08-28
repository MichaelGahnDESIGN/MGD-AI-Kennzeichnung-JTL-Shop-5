<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Display;

use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AuthorizationPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\CsrfPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\DisplayConfigPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Service\DisplaySettings;

/** Orchestriert Berechtigung, CSRF-Schutz, strikte Eingabe und atomaren Konfigurationsport. */
final class DisplaySettingsAdminService
{
    public function __construct(
        private readonly AuthorizationPortInterface $authorization,
        private readonly CsrfPortInterface $csrf,
        private readonly DisplayConfigPortInterface $config,
    ) {}

    /** Lädt ausschließlich für berechtigte Admins die defensiv typisierte Anzeigeeinstellung. */
    public function load(): DisplaySettings
    {
        $this->authorization->assertCanManageAssets();

        return DisplaySettings::fromJtlConfig($this->config->load());
    }

    /** Speichert nur vollständig geprüfte Werte nach Berechtigung und CSRF-Prüfung. */
    public function save(string $csrfToken, mixed $post): DisplaySettings
    {
        $this->authorization->assertCanManageAssets();
        $this->csrf->assertValid($csrfToken);
        $input = DisplaySettingsInput::fromPost($post);
        $savedValues = $input->toJtlConfig();
        $this->config->save($savedValues);

        return DisplaySettings::fromJtlConfig([
            ...$this->config->load(),
            ...$savedValues,
        ]);
    }
}
