<?php

declare(strict_types=1);

use JTL\Plugin\PluginInterface;
use JTL\Shop;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlAuthorizationAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\AccessDeniedException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Http\AdminTabScope;

/* JTLs PluginController definiert PFAD_ROOT und stellt $oPlugin bereit. */
if (!defined('PFAD_ROOT') || !isset($oPlugin) || !$oPlugin instanceof PluginInterface) {
    http_response_code(403);
    echo 'Das Impressum ist nur im JTL-Administrationsbereich verfügbar.';

    return;
}

$container = Shop::Container();
try {
    $sessionId = session_id();
    $adminMenuId = is_object($menu ?? null) ? ($menu->kPluginAdminMenu ?? null) : null;
    $scope = AdminTabScope::capture($oPlugin, $adminMenuId, 'impressum.php');
    if (!$scope->isAddressed) {
        return;
    }
    if (!is_string($sessionId) || $sessionId === ''
    ) {
        throw new ValidationException('Der JTL-Admin-Menükontext ist ungültig.');
    }

    /* Auch die statische Seite akzeptiert ausschließlich JTLs kanonische Route. */
    $request = $scope->request;
    if ($request->method !== 'GET' || $request->query !== [] || $request->post !== []) {
        throw new ValidationException('Das Impressum unterstützt ausschließlich den lesenden Aufruf.');
    }

    /* Die Anzeige verwendet dieselbe offizielle Plugin-Berechtigung wie die Verwaltung. */
    (new JtlAuthorizationAdapter(
        $container->getAdminAccount(),
        $oPlugin->getID(),
        $sessionId,
    ))->assertCanManageAssets();

    echo Shop::Smarty()->fetch(__DIR__ . '/templates/impressum.tpl');
} catch (AccessDeniedException) {
    echo AdminTabScope::error('Sie besitzen keine Berechtigung für das Plugin-Impressum.');
} catch (ValidationException) {
    echo AdminTabScope::error('Das Plugin-Impressum konnte die Anfrage nicht sicher verarbeiten.');
} catch (Throwable) {
    $container->getLogService()->warning('mgd_ai_admin_event', [
        'event_code' => 'imprint_request_failed',
        'count' => 0,
    ]);
    echo AdminTabScope::error('Das Plugin-Impressum konnte die Anfrage nicht abschließen.');
}
