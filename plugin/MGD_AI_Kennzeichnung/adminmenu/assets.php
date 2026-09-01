<?php

declare(strict_types=1);

use JTL\Plugin\PluginInterface;
use JTL\Shop;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlSessionContext;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\AccessDeniedException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Factory\AdminRuntimeFactory;
use Plugin\MGD_AI_Kennzeichnung\Admin\Http\AdminTabScope;
use Plugin\MGD_AI_Kennzeichnung\Admin\View\AdminTemplateRenderer;

/* JTLs PluginController definiert PFAD_ROOT und stellt $oPlugin bereit. */
if (!defined('PFAD_ROOT') || !is_string(PFAD_ROOT) || !isset($oPlugin) || !$oPlugin instanceof PluginInterface) {
    http_response_code(403);
    echo 'Die Bildverwaltung ist nur im JTL-Administrationsbereich verfügbar.';

    return;
}

$adminMenuId = is_object($menu ?? null) ? ($menu->kPluginAdminMenu ?? null) : null;
$scope = AdminTabScope::capture($oPlugin, $adminMenuId, 'assets.php');
if (!$scope->shouldRender || !is_int($adminMenuId)) {
    return;
}

$container = Shop::Container();
try {
    $session = &JtlSessionContext::current();
    $sessionId = session_id();
    if (!is_string($sessionId) || $sessionId === '') {
        echo AdminTabScope::error('Die Bildverwaltung ist im aktuellen Administrationskontext nicht verfügbar.');

        return;
    }
    $request = $scope->request;
    $controller = (new AdminRuntimeFactory())->create(
        $oPlugin,
        $container->getDB(),
        $container->getAdminAccount(),
        $container->getLogService(),
        $session,
        $sessionId,
        $adminMenuId,
        PFAD_ROOT,
    );
    $page = $controller->handle($request->method, $request->query, $request->post);
    (new AdminTemplateRenderer(__DIR__ . '/templates'))->render($page);
} catch (AccessDeniedException) {
    echo AdminTabScope::error('Sie besitzen keine Berechtigung für die Bildverwaltung.');
} catch (Throwable) {
    /* Keine Ausnahme, Pfade, Anfragen oder personenbezogenen Daten ausgeben oder loggen. */
    $container->getLogService()->warning('mgd_ai_admin_event', ['event_code' => 'admin_request_failed', 'count' => 0]);
    echo AdminTabScope::error('Die Bildverwaltung konnte die Anfrage nicht abschließen.');
}
