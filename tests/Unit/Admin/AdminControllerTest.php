<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Admin\Controller\AdminAssetController;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\AccessDeniedException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Http\AdminRequestNormalizer;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AdminActionHandlerInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\BulkExecuteRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\BulkPreviewRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\AssetDetailRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\AssetListRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\CleanupExecuteRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\CleanupListRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\CleanupPreviewRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\ScanRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\Request\SingleUpdateRequest;
use Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel\AdminPage;

final class AdminControllerTest extends TestCase
{
    #[Test]
    public function unberechtigter_post_stoppt_vor_csrf_normalisierung_und_handler(): void
    {
        $trace = [];
        $controller = new AdminAssetController(
            new RecordingAuthorization($trace, false),
            new RecordingCsrf($trace, true),
            new AdminRequestNormalizer(),
            new RecordingAdminHandler($trace),
        );

        $this->expectException(AccessDeniedException::class);
        try {
            $controller->handle('POST', [], ['beliebig' => ['zu' => ['tief' => ['x']]]]);
        } finally {
            self::assertSame(['permission'], $trace);
        }
    }

    #[Test]
    public function bulk_execute_uebergibt_nur_normalisierte_tokens_und_keine_client_operation(): void
    {
        $trace = [];
        $handler = new RecordingAdminHandler($trace);
        $controller = new AdminAssetController(
            new RecordingAuthorization($trace, true),
            new RecordingCsrf($trace, true),
            new AdminRequestNormalizer(),
            $handler,
        );

        $page = $controller->handle('POST', [], [
            'action' => 'bulk-update',
            'csrf_token' => 'csrf',
            'confirmation_token' => 'opaque',
        ]);

        self::assertSame('messages', $page->template);
        self::assertSame(['permission', 'csrf', 'bulk-execute'], $trace);
        self::assertSame($trace, $handler->trace());
        self::assertSame('opaque', $handler->bulkExecute?->confirmationToken);
    }

    #[Test]
    public function unbekannte_post_aktion_und_get_ansicht_werden_abgelehnt(): void
    {
        $trace = [];
        $controller = new AdminAssetController(
            new RecordingAuthorization($trace, true),
            new RecordingCsrf($trace, true),
            new AdminRequestNormalizer(),
            new RecordingAdminHandler($trace),
        );

        foreach ([
            ['POST', [], ['action' => 'delete-everything', 'csrf_token' => 'csrf']],
            ['GET', ['view' => 'include-path'], []],
        ] as [$method, $query, $post]) {
            try {
                $controller->handle($method, $query, $post);
                self::fail('Nicht freigegebene Routen müssen abgelehnt werden.');
            } catch (ValidationException) {
                self::addToAssertionCount(1);
            }
        }
    }
}

final class RecordingAdminHandler implements AdminActionHandlerInterface
{
    public ?BulkExecuteRequest $bulkExecute = null;

    /** @param list<string> $trace */
    public function __construct(private array &$trace) {}

    /** @return list<string> */
    public function trace(): array
    {
        return $this->trace;
    }

    public function list(AssetListRequest $request): AdminPage
    {
        $this->trace[] = 'list';

        return new AdminPage('assets-list', []);
    }

    public function detail(AssetDetailRequest $request): AdminPage
    {
        $this->trace[] = 'detail';

        return new AdminPage('asset-detail', []);
    }

    public function cleanupList(CleanupListRequest $request): AdminPage
    {
        $this->trace[] = 'cleanup-list';

        return new AdminPage('cleanup-list', []);
    }

    public function bulkPreview(BulkPreviewRequest $request): AdminPage
    {
        $this->trace[] = 'bulk-preview';

        return new AdminPage('bulk-preview', []);
    }

    public function bulkExecute(BulkExecuteRequest $request): AdminPage
    {
        $this->trace[] = 'bulk-execute';
        $this->bulkExecute = $request;

        return new AdminPage('messages', ['message' => 'Gespeichert.']);
    }

    public function singleUpdate(SingleUpdateRequest $request): AdminPage
    {
        $this->trace[] = 'single-update';

        return new AdminPage('messages', ['message' => 'Ausgeführt.']);
    }

    public function scan(ScanRequest $request): AdminPage
    {
        $this->trace[] = 'scan';

        return new AdminPage('messages', ['message' => 'Ausgeführt.']);
    }

    public function cleanupPreview(CleanupPreviewRequest $request): AdminPage
    {
        $this->trace[] = 'cleanup-preview';

        return new AdminPage('cleanup-preview', []);
    }

    public function cleanupExecute(CleanupExecuteRequest $request): AdminPage
    {
        $this->trace[] = 'cleanup-execute';

        return new AdminPage('messages', ['message' => 'Ausgeführt.']);
    }
}
