<?php

declare(strict_types=1);

/* Direkter Webzugriff ohne geladenen JTL-Shop-Kontext wird abgewiesen. */
if (!defined('PFAD_ROOT') && !defined('JTL_INCLUDE_ONLY')) {
    http_response_code(403);
    exit;
}

/*
 * Aktionen, Ports, Request-Normalisierung und ViewModels werden im offiziellen
 * Plugin-Bootstrap zusammengesetzt. Dieser Einstieg rendert nur eine bereits
 * freigegebene Ansicht und liest bewusst keine HTTP-Globals.
 */
$templateName = isset($templateName) && is_string($templateName) ? $templateName : 'assets-list';
$templateFile = match ($templateName) {
    'assets-list' => __DIR__ . '/templates/assets-list.php',
    'asset-detail' => __DIR__ . '/templates/asset-detail.php',
    'bulk-preview' => __DIR__ . '/templates/bulk-preview.php',
    'messages' => __DIR__ . '/templates/messages.php',
    default => __DIR__ . '/templates/messages.php',
};
require $templateFile;
