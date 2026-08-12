<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\View;

use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel\AdminPage;

/** Rendert ausschließlich fest zugeordnete lokale Templates mit definierten Variablen. */
final class AdminTemplateRenderer
{
    public function __construct(private readonly string $templateDirectory) {}

    public function render(AdminPage $page): void
    {
        $file = match ($page->template) {
            'assets-list' => 'assets-list.php',
            'asset-detail' => 'asset-detail.php',
            'bulk-preview' => 'bulk-preview.php',
            'cleanup-list' => 'cleanup-list.php',
            'cleanup-preview' => 'cleanup-preview.php',
            'messages' => 'messages.php',
            default => throw new ValidationException('Das Ausgabetemplate ist nicht freigegeben.'),
        };
        /* extract() arbeitet intern mit Referenzen und darf deshalb nie direkt
         * auf einer readonly-Property ausgeführt werden. */
        $variables = $page->variables;
        extract($variables, EXTR_SKIP);
        require $this->templateDirectory . '/' . $file;
    }
}
