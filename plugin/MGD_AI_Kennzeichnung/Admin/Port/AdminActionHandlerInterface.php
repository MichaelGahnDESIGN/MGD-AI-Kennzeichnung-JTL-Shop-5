<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Port;

use Plugin\MGD_AI_Kennzeichnung\Admin\Request\BulkExecuteRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\AssetDetailRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\AssetListRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\BulkPreviewRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\CleanupExecuteRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\CleanupListRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\CleanupPreviewRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\ScanRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\SingleUpdateRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel\AdminPage;

/** Typisierte Controllergrenze zu den einzelnen Anwendungsaktionen. */
interface AdminActionHandlerInterface
{
    public function list(AssetListRequest $request): AdminPage;

    public function detail(AssetDetailRequest $request): AdminPage;

    public function cleanupList(CleanupListRequest $request): AdminPage;

    public function bulkPreview(BulkPreviewRequest $request): AdminPage;

    public function bulkExecute(BulkExecuteRequest $request): AdminPage;

    public function singleUpdate(SingleUpdateRequest $request): AdminPage;

    public function scan(ScanRequest $request): AdminPage;

    public function cleanupPreview(CleanupPreviewRequest $request): AdminPage;

    public function cleanupExecute(CleanupExecuteRequest $request): AdminPage;
}
