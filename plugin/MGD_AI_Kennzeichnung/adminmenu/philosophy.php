<?php

declare(strict_types=1);

use JTL\Plugin\PluginInterface;
use JTL\Shop;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlAuthorizationAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlCsrfAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlSessionContext;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\AccessDeniedException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\CsrfException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Http\AdminTabScope;
use Plugin\MGD_AI_Kennzeichnung\Admin\Philosophy\PhilosophyAdminService;

/* JTLs PluginController definiert PFAD_ROOT und stellt $oPlugin bereit. */
if (!defined('PFAD_ROOT') || !isset($oPlugin) || !$oPlugin instanceof PluginInterface) {
    http_response_code(403);
    echo 'Die AI-Philosophie ist nur im JTL-Administrationsbereich verfügbar.';

    return;
}

$container = Shop::Container();
try {
    $adminMenuId = is_object($menu ?? null) ? ($menu->kPluginAdminMenu ?? null) : null;
    $scope = AdminTabScope::capture($oPlugin, $adminMenuId, 'philosophy.php');
    if (!$scope->isAddressed) {
        return;
    }
    $session = &JtlSessionContext::current();
    $sessionId = session_id();
    if (!is_string($sessionId) || $sessionId === ''
    ) {
        throw new ValidationException('Der JTL-Admin-Menükontext ist ungültig.');
    }

    $request = $scope->request;
    $authorization = new JtlAuthorizationAdapter(
        $container->getAdminAccount(),
        $oPlugin->getID(),
        $sessionId,
    );
    $csrf = new JtlCsrfAdapter($session);
    $service = new PhilosophyAdminService($authorization, $csrf, $container->getDB());
    $message = '';

    if ($request->method === 'POST') {
        $erwarteteFelder = ['content_de', 'content_en', 'csrf_token'];
        $felder = array_keys($request->post);
        sort($felder, SORT_STRING);
        sort($erwarteteFelder, SORT_STRING);
        if ($felder !== $erwarteteFelder || $request->query !== []) {
            throw new ValidationException('Das Philosophie-Formular enthält unerwartete Felder.');
        }
        $token = $request->post['csrf_token'];
        if (!is_string($token)) {
            throw new CsrfException('Das Formular-Sicherheitstoken ist ungültig.');
        }
        $inhalte = $service->save($token, $request->post['content_de'], $request->post['content_en']);
        $message = 'Beide Sprachfassungen wurden sicher gespeichert.';
    } elseif ($request->method === 'GET' && $request->query === [] && $request->post === []) {
        $inhalte = $service->load();
    } else {
        throw new ValidationException('Die HTTP-Anfrage wird für dieses Formular nicht unterstützt.');
    }

    echo Shop::Smarty()
        ->assign('csrfToken', $csrf->token())
        ->assign('pluginId', $oPlugin->getID())
        ->assign('adminMenuId', $adminMenuId)
        ->assign('contentDe', $inhalte['de'])
        ->assign('contentEn', $inhalte['en'])
        ->assign('message', $message)
        ->fetch(__DIR__ . '/templates/philosophy.tpl');
} catch (AccessDeniedException) {
    echo AdminTabScope::error('Sie besitzen keine Berechtigung für die AI-Philosophie.');
} catch (ValidationException|CsrfException) {
    echo AdminTabScope::error('Die AI-Philosophie konnte die Eingabe nicht sicher verarbeiten.');
} catch (Throwable) {
    $container->getLogService()->warning('mgd_ai_admin_event', ['event_code' => 'philosophy_request_failed', 'count' => 0]);
    echo AdminTabScope::error('Die AI-Philosophie konnte die Anfrage nicht abschließen.');
}
