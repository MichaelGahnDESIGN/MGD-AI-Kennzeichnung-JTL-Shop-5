<?php

declare(strict_types=1);

use JTL\Plugin\PluginInterface;
use JTL\Shop;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlAuthorizationAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlCsrfAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlDisplayConfigAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlSessionContext;
use Plugin\MGD_AI_Kennzeichnung\Admin\Display\DisplaySettingsAdminService;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\AccessDeniedException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\CsrfException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\DisplayConfigCommittedException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Http\AdminTabScope;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\UpdateCheckerProviderInterface;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Adapter\CurlHttpClient;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Adapter\FileReleaseCache;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\Adapter\SystemClock;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Update\GitHubReleaseChecker;

/* JTLs PluginController definiert PFAD_ROOT und stellt $oPlugin bereit. */
if (!defined('PFAD_ROOT') || !isset($oPlugin) || !$oPlugin instanceof PluginInterface) {
    http_response_code(403);
    echo 'Die Darstellung ist nur im JTL-Administrationsbereich verfügbar.';

    return;
}

$adminMenuId = is_object($menu ?? null) ? ($menu->kPluginAdminMenu ?? null) : null;
$scope = AdminTabScope::capture($oPlugin, $adminMenuId, 'display.php', true);
if (!$scope->shouldRender) {
    return;
}

$container = Shop::Container();
try {
    /* Nur gültige Tabs dürfen eine Session für ihren neutralen Lesestand verwenden. */
    $session = &JtlSessionContext::current();
    $sessionId = session_id();
    $sessionToken = $session['jtl_token'] ?? null;
    if (!is_string($sessionId) || $sessionId === ''
        || !is_string($sessionToken) || $sessionToken === '' || strlen($sessionToken) > 256
    ) {
        echo AdminTabScope::error('Die Darstellung ist im aktuellen Administrationskontext nicht verfügbar.');

        return;
    }

    /* Die Route wird vor der Formularprüfung kanonisch an Plugin und Menü gebunden. */
    $request = $scope->request;
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
    $updateNotice = null;

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

        /*
         * JTL führt Customlinks parallel aus. Deshalb darf die externe,
         * freiwillige Prüfung nur im wirklich adressierten Darstellungstab
         * nach erfolgreicher Session-, Rechte- und Einstellungslast erfolgen.
         */
        if ($scope->isAddressed && $oPlugin->getConfig()->getValue('update_notices') === 'Y') {
            try {
                if (!is_string(PFAD_ROOT)) {
                    throw new RuntimeException('Der Pluginpfad ist nicht sicher typisiert.');
                }
                $cachePath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
                    . DIRECTORY_SEPARATOR
                    . 'mgd-ai-release-'
                    . hash('sha256', (string) PFAD_ROOT)
                    . '.json';
                $checker = $container instanceof UpdateCheckerProviderInterface
                    ? $container->getUpdateChecker()
                    : new GitHubReleaseChecker(
                        new CurlHttpClient(),
                        new FileReleaseCache($cachePath),
                        new SystemClock(),
                    );
                $updateNotice = $checker->check(true, '1.2.1');
            } catch (Throwable) {
                // Die rein optionale Prüfung darf niemals einen Adminfehler erzeugen.
                $updateNotice = null;
            }
        }
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
        ->assign('updateNotice', $updateNotice)
        ->fetch(__DIR__ . '/templates/display.tpl');
} catch (AccessDeniedException) {
    echo AdminTabScope::error('Sie besitzen keine Berechtigung für die Darstellung.');
} catch (DisplayConfigCommittedException) {
    $container->getLogService()->warning('mgd_ai_admin_event', [
        'event_code' => 'display_cache_invalidation_failed',
    ]);
    echo AdminTabScope::error('Werte gespeichert, Cache nicht aktualisiert.');
} catch (ValidationException|CsrfException) {
    echo AdminTabScope::error('Die Darstellung konnte die Eingabe nicht sicher verarbeiten.');
} catch (Throwable) {
    $container->getLogService()->warning('mgd_ai_admin_event', [
        'event_code' => 'display_request_failed',
    ]);
    echo AdminTabScope::error('Die Darstellung konnte die Anfrage nicht abschließen.');
}
