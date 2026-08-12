<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Admin\Action\AssetListAction;
use Plugin\MGD_AI_Kennzeichnung\Admin\Action\CleanupAction;
use Plugin\MGD_AI_Kennzeichnung\Admin\Action\CleanupPreviewAction;
use Plugin\MGD_AI_Kennzeichnung\Admin\Action\ScanAction;
use Plugin\MGD_AI_Kennzeichnung\Admin\Action\SingleUpdateAction;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\CleanupRepositoryInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\LogPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\ScanPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Service\ImageScanResult;
use Plugin\MGD_AI_Kennzeichnung\Admin\Value\StoredOperation;
use RuntimeException;

final class AdminActionsTest extends TestCase
{
    #[Test]
    public function einzelupdate_bewahrt_nicht_maskierte_felder(): void
    {
        $trace = [];
        $repository = new RecordingAssetRepository($trace);
        $action = new SingleUpdateAction(
            new RecordingAuthorization($trace, true),
            new RecordingCsrf($trace, true),
            $repository,
        );

        $action->execute('csrf', 7, ['theme' => true, 'status' => false], ['theme' => 'dark']);

        self::assertSame([[[7], ['theme' => 'dark']]], $repository->writes);
        self::assertSame(['permission', 'csrf'], $trace);
    }

    #[Test]
    public function listenfilter_sortierung_und_seitengroesse_sind_geschlossen(): void
    {
        $trace = [];
        $action = new AssetListAction(new RecordingAuthorization($trace, true), new RecordingAssetRepository($trace));

        foreach ([
            [1, 101, [], 'id', 'asc'],
            [1, 25, ['status' => 'fremd'], 'id', 'asc'],
            [1, 25, [], 'sql;drop', 'asc'],
            [1, 25, [], 'id', 'sideways'],
        ] as [$page, $size, $filters, $sort, $direction]) {
            try {
                $action->load($page, $size, $filters, $sort, $direction);
                self::fail('Ungültige Listenparameter müssen abgelehnt werden.');
            } catch (ValidationException) {
                self::addToAssertionCount(1);
            }
        }
    }

    #[Test]
    public function scan_fehler_bleibt_generisch_und_loggt_nur_code_und_anzahl(): void
    {
        $trace = [];
        $logger = new RecordingLogger();
        $scanner = new RecordingScanner($trace);
        $scanner->failure = new RuntimeException('/privat/pfad token=geheim person@example.org');
        $action = new ScanAction(
            new RecordingAuthorization($trace, true),
            new RecordingCsrf($trace, true),
            $scanner,
            $logger,
        );

        $result = $action->execute('csrf');

        self::assertFalse($result->successful);
        self::assertSame('Der Bildscan konnte nicht abgeschlossen werden.', $result->message);
        self::assertSame([['admin_scan_failed', 0]], $logger->events);
        self::assertSame(['permission', 'csrf', 'scan'], $trace);
        self::assertStringNotContainsString('geheim', serialize($logger->events));
    }

    #[Test]
    public function bereinigung_loescht_keine_assets_oder_dateien_und_erfordert_vorschau(): void
    {
        $trace = [];
        $repository = new RecordingCleanupRepository($trace);
        $confirmation = new RecordingConfirmation($trace);
        $preview = new CleanupPreviewAction(
            new RecordingAuthorization($trace, true),
            $confirmation,
            $repository,
        );
        $previewResult = $preview->preview([9, 4]);
        $confirmation->operationToConsume = new StoredOperation('cleanup-stale-usages', [4, 9], []);
        $action = new CleanupAction(
            new RecordingAuthorization($trace, true),
            new RecordingCsrf($trace, true),
            $confirmation,
            $repository,
        );

        $result = $action->execute('csrf', $previewResult->confirmationToken);

        self::assertSame(2, $result->cleanedCount);
        self::assertSame([[4, 9]], $repository->cleanedUsageIds);
        self::assertFalse($repository->assetOrFileDeletionCalled);
    }
}

final class RecordingScanner implements ScanPortInterface
{
    public ?\Throwable $failure = null;

    /** @param list<string> $trace */
    public function __construct(private array &$trace) {}

    public function scan(): ImageScanResult
    {
        $this->trace[] = 'scan';
        if ($this->failure !== null) {
            throw $this->failure;
        }

        return new ImageScanResult(1, 2);
    }

    /** @return list<string> */
    public function traceSnapshot(): array
    {
        return $this->trace;
    }
}

final class RecordingLogger implements LogPortInterface
{
    /** @var list<array{0: string, 1: int}> */
    public array $events = [];

    public function event(string $code, int $count): void
    {
        $this->events[] = [$code, $count];
    }
}

final class RecordingCleanupRepository implements CleanupRepositoryInterface
{
    /** @var list<list<int>> */
    public array $cleanedUsageIds = [];
    public bool $assetOrFileDeletionCalled = false;

    /** @param list<string> $trace */
    public function __construct(private array &$trace) {}

    public function countOwnedStaleUsageIds(array $usageIds): int
    {
        $this->trace[] = 'cleanup-count';

        return count($usageIds);
    }

    public function listOwnedStaleUsages(int $offset, int $limit): array
    {
        return [];
    }

    public function countOwnedStaleUsages(): int
    {
        return 0;
    }

    public function cleanupOwnedStaleUsages(array $usageIds): void
    {
        $this->trace[] = 'cleanup';
        $this->cleanedUsageIds[] = $usageIds;
    }

    /** @return list<string> */
    public function traceSnapshot(): array
    {
        return $this->trace;
    }
}
