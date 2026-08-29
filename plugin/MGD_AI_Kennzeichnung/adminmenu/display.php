<?php

declare(strict_types=1);

use JTL\Plugin\PluginInterface;
use JTL\Shop;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlAuthorizationAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlCsrfAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlDisplayConfigAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlHttpRequestAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlSessionContext;
use Plugin\MGD_AI_Kennzeichnung\Admin\Display\DisplaySettingsAdminService;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\AccessDeniedException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\CsrfException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\DisplayConfigCommittedException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;

/* JTLs PluginController definiert PFAD_ROOT und stellt $oPlugin bereit. */
if (!defined('PFAD_ROOT') || !isset($oPlugin) || !$oPlugin instanceof PluginInterface) {
    http_response_code(403);
    echo 'Die Darstellung ist nur im JTL-Administrationsbereich verfügbar.';

    return;
}

$container = Shop::Container();
try {
    $session = &JtlSessionContext::current();
    $sessionId = session_id();
    $adminMenuId = is_object($menu ?? null) ? ($menu->kPluginAdminMenu ?? null) : null;
    $sessionToken = $session['jtl_token'] ?? null;
    $adminMenuItem = is_int($adminMenuId) ? $oPlugin->getAdminMenu()->getItemByID($adminMenuId) : null;
    if (!is_string($sessionId) || $sessionId === ''
        || !is_int($adminMenuId) || $adminMenuId < 1
        || !is_object($adminMenuItem)
        || !isset($adminMenuItem->cDateiname) || !is_string($adminMenuItem->cDateiname)
        || $adminMenuItem->cDateiname !== 'display.php'
        || !is_string($sessionToken) || $sessionToken === '' || strlen($sessionToken) > 256
    ) {
        /* Fehlender Laufzeitkontext ist kein Formularfehler, sondern ein gesperrter Direktzugriff. */
        http_response_code(403);
        echo 'Die Darstellung ist nur in einem gültigen JTL-Administrationskontext verfügbar.';

        return;
    }

    /* Die Route wird vor der Formularprüfung kanonisch an Plugin und Menü gebunden. */
    $request = (new JtlHttpRequestAdapter())->capture($oPlugin->getID(), $adminMenuId, true);
    $authorization = new JtlAuthorizationAdapter(
        $container->getAdminAccount(),
        $oPlugin->getID(),
        $sessionId,
    );
    $csrf = new JtlCsrfAdapter($session);
    /* Das Token wird vor jeder möglichen Speicherung aus der gültigen Session gelesen. */
    $templateCsrfToken = $csrf->token();
    $service = new DisplaySettingsAdminService(
        $authorization,
        $csrf,
        new JtlDisplayConfigAdapter($container->getDB(), $oPlugin, $container->getCache()),
    );
    $message = '';

    if ($request->method === 'POST') {
        $expected = [
            'blur', 'border_radius', 'csrf_token', 'font_size', 'inner_padding',
            'kPlugin', 'kPluginAdminMenu', 'language', 'outer_margin', 'transparency',
        ];
        $fields = array_keys($request->post);
        sort($fields, SORT_STRING);
        sort($expected, SORT_STRING);
        if ($fields !== $expected || $request->query !== []) {
            throw new ValidationException('Das Darstellungsformular enthält unerwartete Felder.');
        }

        $csrfToken = $request->post['csrf_token'];
        if (!is_string($csrfToken)) {
            throw new CsrfException('Das Formular-Sicherheitstoken ist ungültig.');
        }

        /* Routing- und CSRF-Felder gehören zum Transportvertrag, nie zur Konfiguration. */
        $settingsPost = array_diff_key($request->post, array_flip([
            'csrf_token',
            'kPlugin',
            'kPluginAdminMenu',
        ]));
        $settings = $service->save($csrfToken, $settingsPost);
        $message = 'Die Darstellung wurde sicher gespeichert.';
    } elseif ($request->method === 'GET' && $request->query === [] && $request->post === []) {
        $settings = $service->load();
    } else {
        throw new ValidationException('Die Anfrage wird für den Darstellungstab nicht unterstützt.');
    }

    echo Shop::Smarty()
        ->assign('adminUrl', $oPlugin->getPaths()->getAdminURL())
        ->assign('csrfToken', $templateCsrfToken)
        ->assign('pluginId', $oPlugin->getID())
        ->assign('adminMenuId', $adminMenuId)
        ->assign('language', $settings->language->value)
        ->assign('fontSize', $settings->fontSize)
        ->assign('outerMargin', $settings->outerMargin)
        ->assign('innerPadding', $settings->innerPadding)
        ->assign('borderRadius', $settings->borderRadius)
        ->assign('blur', $settings->blur)
        ->assign('transparency', $settings->transparency)
        ->assign('message', $message)
        ->fetch(__DIR__ . '/templates/display.tpl');
} catch (AccessDeniedException) {
    http_response_code(403);
    echo 'Sie besitzen keine Berechtigung für die Darstellung.';
} catch (DisplayConfigCommittedException) {
    http_response_code(500);
    echo 'Die Werte wurden gespeichert, aber der Plugin-Cache konnte nicht aktualisiert werden.';
} catch (ValidationException|CsrfException) {
    http_response_code(400);
    echo 'Die Darstellung konnte die Eingabe nicht sicher verarbeiten.';
} catch (Throwable) {
    http_response_code(500);
    $container->getLogService()->warning('mgd_ai_admin_event', [
        'event_code' => 'display_request_failed',
    ]);
    echo 'Die Darstellung konnte die Anfrage nicht abschließen.';
}
