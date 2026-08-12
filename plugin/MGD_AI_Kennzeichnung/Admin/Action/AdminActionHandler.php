<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Action;

use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AdminActionHandlerInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\CsrfTokenPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\AssetDetailRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\AssetListRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\BulkExecuteRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\BulkPreviewRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\CleanupExecuteRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\CleanupListRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\CleanupPreviewRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\ScanRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\SingleUpdateRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel\AdminPage;
use Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel\AdminRoute;

/** Verbindet jedes typisierte DTO mit genau einer fachlichen Aktion. */
final class AdminActionHandler implements AdminActionHandlerInterface
{
    public function __construct(
        private readonly AssetListAction $list,
        private readonly AssetDetailAction $detail,
        private readonly CleanupListAction $cleanupList,
        private readonly SingleUpdateAction $singleUpdate,
        private readonly BulkUpdatePreviewAction $bulkPreview,
        private readonly BulkUpdateAction $bulkUpdate,
        private readonly ScanAction $scanAction,
        private readonly CleanupPreviewAction $cleanupPreview,
        private readonly CleanupAction $cleanup,
        private readonly CsrfTokenPortInterface $csrf,
        private readonly string $assetScriptUrl,
        private readonly AdminRoute $route,
    ) {}

    public function list(AssetListRequest $request): AdminPage
    {
        $view = $this->list->load($request->page, $request->pageSize, $request->filters, $request->sort, $request->direction);

        return new AdminPage('assets-list', [
            'view' => $view,
            'csrfToken' => $this->csrf->token(),
            'assetScriptUrl' => $this->assetScriptUrl,
            'route' => $this->route,
        ]);
    }

    public function detail(AssetDetailRequest $request): AdminPage
    {
        return new AdminPage('asset-detail', [
            'detail' => $this->detail->load($request->assetId),
            'csrfToken' => $this->csrf->token(),
            'assetScriptUrl' => $this->assetScriptUrl,
            'route' => $this->route,
        ]);
    }

    public function cleanupList(CleanupListRequest $request): AdminPage
    {
        return new AdminPage('cleanup-list', [
            'view' => $this->cleanupList->load($request->page, $request->pageSize),
            'csrfToken' => $this->csrf->token(),
            'route' => $this->route,
        ]);
    }

    public function bulkPreview(BulkPreviewRequest $request): AdminPage
    {
        return new AdminPage('bulk-preview', [
            'preview' => $this->bulkPreview->preview($request->assetIds, $request->mask, $request->values),
            'csrfToken' => $this->csrf->token(),
            'route' => $this->route,
        ]);
    }

    public function bulkExecute(BulkExecuteRequest $request): AdminPage
    {
        $result = $this->bulkUpdate->execute($request->csrfToken, $request->confirmationToken);

        return $this->message($result->updatedCount . ' Bildkennzeichnungen wurden sicher aktualisiert.');
    }

    public function singleUpdate(SingleUpdateRequest $request): AdminPage
    {
        $this->singleUpdate->execute($request->csrfToken, $request->assetId, $request->mask, $request->values);

        return $this->message('Die Bildkennzeichnung wurde sicher aktualisiert.');
    }

    public function scan(ScanRequest $request): AdminPage
    {
        $result = $this->scanAction->execute($request->csrfToken);

        return new AdminPage('messages', [
            'message' => $result->message,
            'csrfToken' => $this->csrf->token(),
            'route' => $this->route,
        ]);
    }

    public function cleanupPreview(CleanupPreviewRequest $request): AdminPage
    {
        return new AdminPage('cleanup-preview', [
            'preview' => $this->cleanupPreview->preview($request->usageIds),
            'csrfToken' => $this->csrf->token(),
            'route' => $this->route,
        ]);
    }

    public function cleanupExecute(CleanupExecuteRequest $request): AdminPage
    {
        $result = $this->cleanup->execute($request->csrfToken, $request->confirmationToken);

        return $this->message($result->cleanedCount . ' veraltete Fundstellen wurden bereinigt.');
    }

    private function message(string $message): AdminPage
    {
        return new AdminPage('messages', [
            'message' => $message,
            'csrfToken' => $this->csrf->token(),
            'route' => $this->route,
        ]);
    }
}
