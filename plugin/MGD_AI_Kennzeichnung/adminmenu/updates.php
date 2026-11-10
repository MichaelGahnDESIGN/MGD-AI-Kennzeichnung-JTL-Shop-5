<?php

declare(strict_types=1);

use JTL\Helpers\Form;
use JTL\Plugin\PluginInterface;
use JTL\Shop;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlAuthorizationAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\AccessDeniedException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Http\AdminTabScope;
use Plugin\MGD_AI_Kennzeichnung\Admin\Update\PackageDelivery;
use Plugin\MGD_AI_Kennzeichnung\Admin\Update\UpdateDataDirectory;
use Plugin\MGD_AI_Kennzeichnung\Admin\Update\UpdatesPageFactory;

/* JTLs PluginController definiert PFAD_ROOT und stellt $oPlugin bereit. */
if (!defined('PFAD_ROOT') || !isset($oPlugin) || !$oPlugin instanceof PluginInterface) {
    http_response_code(403);
    echo 'Die Updates sind nur im JTL-Administrationsbereich verfügbar.';

    return;
}

$adminMenuId = is_object($menu ?? null) ? ($menu->kPluginAdminMenu ?? null) : null;
try {
    $scope = AdminTabScope::capture($oPlugin, $adminMenuId, 'updates.php', true);
} catch (ValidationException) {
    echo AdminTabScope::error('Die Updates konnten die Formularadresse nicht zuordnen. Bitte den Plugin-Menüpunkt erneut öffnen. Es wurde nichts geändert.');

    return;
}
if (!$scope->shouldRender) {
    return;
}

// Nicht adressierter Tab: kein Netzabruf, nur ein Link zum Updates Tab
if (!$scope->isAddressed || !is_int($adminMenuId)) {
    if (!is_int($adminMenuId)) {
        return;
    }
    echo '<div class="mgd-updates"><h3>Updates</h3><p><a href="?kPlugin='
        . $oPlugin->getID() . '&amp;kPluginAdminMenu=' . $adminMenuId
        . '">Update-Bereich öffnen</a></p></div>';

    return;
}

$container = Shop::Container();
try {
    $sessionId = session_id();
    if (!is_string($sessionId) || $sessionId === '') {
        throw new ValidationException('Der JTL-Admin-Menükontext ist ungültig.');
    }
    $authorization = new JtlAuthorizationAdapter($container->getAdminAccount(), $oPlugin->getID(), $sessionId);

    // Rechte Check
    $canManage = static function () use ($authorization): bool {
        try {
            $authorization->assertCanManageAssets();

            return true;
        } catch (AccessDeniedException) {
            return false;
        }
    };

    // Datenordner ausserhalb Web
    if (!is_string(PFAD_ROOT)) {
        throw new ValidationException('Der Shop-Pfad ist nicht sicher typisiert.');
    }
    $dataDir = UpdateDataDirectory::prepare(PFAD_ROOT . 'templates_c/');

    // CSRF Feld + JTL Routing Felder (sonst landet der POST nicht im Tab)
    $csrfField = Form::getTokenInput()
        . '<input type="hidden" name="kPlugin" value="' . $oPlugin->getID() . '">'
        . '<input type="hidden" name="kPluginAdminMenu" value="' . $adminMenuId . '">';

    $shopVersion = defined('APPLICATION_VERSION') ? constant('APPLICATION_VERSION') : '';
    $shopVersion = is_string($shopVersion) ? $shopVersion : '';
    $page = UpdatesPageFactory::create(
        rtrim($oPlugin->getPaths()->getBasePath(), '/'),
        $dataDir,
        $oPlugin->getPaths()->getFrontendURL(),
        $shopVersion,
        $csrfField,
        static fn(): bool => Form::validateToken(),
        $canManage,
    );

    $request = $scope->request;
    $result = $page->handle($request->method, $request->post);

    // ZIP Download (nur POST, CSRF und Bestätigung hat die Seite schon geprüft)
    if ($result['deliver'] !== null && PackageDelivery::send($result['deliver'], $dataDir)) {
        exit;
    }
    if ($result['deliver'] !== null) {
        echo AdminTabScope::error('Das Paket konnte nicht zum Download bereitgestellt werden.');

        return;
    }
    echo $result['html'];
} catch (ValidationException) {
    echo AdminTabScope::error('Die Updates konnten die Anfrage nicht sicher verarbeiten.');
} catch (Throwable) {
    $container->getLogService()->warning('mgd_ai_admin_event', [
        'event_code' => 'updates_request_failed',
    ]);
    echo AdminTabScope::error('Der Update-Bereich konnte die Anfrage nicht abschließen.');
}
