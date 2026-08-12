<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Admin\Action\BulkUpdateAction;
use Plugin\MGD_AI_Kennzeichnung\Admin\Action\BulkUpdatePreviewAction;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\AccessDeniedException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ConfirmationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\CsrfException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AdminAssetRepositoryInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AuthorizationPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\ConfirmationPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\CsrfPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Value\StoredOperation;
use Plugin\MGD_AI_Kennzeichnung\Admin\Value\ConfirmationLease;
use RuntimeException;

final class BulkUpdateActionTest extends TestCase
{
    #[Test]
    public function nicht_berechtigte_anfrage_stoppt_vor_csrf_und_repository(): void
    {
        $trace = [];
        $action = new BulkUpdateAction(
            new RecordingAuthorization($trace, false),
            new RecordingCsrf($trace, true),
            new RecordingConfirmation($trace),
            new RecordingAssetRepository($trace),
        );

        $this->expectException(AccessDeniedException::class);
        try {
            $action->execute('csrf', 'token');
        } finally {
            self::assertSame(['permission'], $trace);
        }
    }

    #[Test]
    public function ungueltiges_csrf_stoppt_vor_ids_bestaetigung_und_repository(): void
    {
        $trace = [];
        $action = new BulkUpdateAction(
            new RecordingAuthorization($trace, true),
            new RecordingCsrf($trace, false),
            new RecordingConfirmation($trace),
            new RecordingAssetRepository($trace),
        );

        $this->expectException(CsrfException::class);
        try {
            $action->execute('falsch', 'token');
        } finally {
            self::assertSame(['permission', 'csrf'], $trace);
        }
    }

    #[Test]
    public function ids_sind_positive_eindeutige_integer_und_auf_500_begrenzt(): void
    {
        foreach ([[1, 1], ['1'], [0], range(1, 501)] as $ids) {
            $trace = [];
            $preview = new BulkUpdatePreviewAction(
                new RecordingAuthorization($trace, true),
                new RecordingConfirmation($trace),
                new RecordingAssetRepository($trace),
            );

            try {
                $preview->preview($ids, ['status' => true], ['status' => 'generated']);
                self::fail('Ungültige IDs müssen vor der Bestätigung abgelehnt werden.');
            } catch (ValidationException) {
                self::assertSame(['permission'], $trace);
            }
        }
    }

    #[Test]
    public function feldmaske_ist_geschlossen_und_unbekannte_enumwerte_werden_abgelehnt(): void
    {
        foreach ([
            [['sql_identifier' => true], ['sql_identifier' => 'status']],
            [['status' => true], ['status' => 'unbekannt']],
            [['status' => false], ['status' => 'generated']],
        ] as [$mask, $values]) {
            $trace = [];
            $preview = new BulkUpdatePreviewAction(
                new RecordingAuthorization($trace, true),
                new RecordingConfirmation($trace),
                new RecordingAssetRepository($trace),
            );

            $this->expectValidation(static fn() => $preview->preview([1], $mask, $values));
            self::assertSame(['permission'], $trace);
        }
    }

    #[Test]
    public function adminwerte_werden_exakt_und_ohne_zusaetzliche_schluessel_validiert(): void
    {
        foreach ([
            [['status' => true], ['status' => ' GENERATED ']],
            [['status' => true], ['status' => 'generated', 'evil' => 'x']],
            [['status' => true, 'theme' => false], ['status' => 'generated', 'theme' => 'dark']],
        ] as [$mask, $values]) {
            $trace = [];
            $preview = new BulkUpdatePreviewAction(
                new RecordingAuthorization($trace, true),
                new RecordingConfirmation($trace),
                new RecordingAssetRepository($trace),
            );
            $this->expectValidation(fn() => $preview->preview([1], $mask, $values));
        }
    }

    #[Test]
    public function preview_schreibt_nicht_und_bindet_token_an_subjekt_ids_maske_und_ziele(): void
    {
        $trace = [];
        $repository = new RecordingAssetRepository($trace);
        $confirmation = new RecordingConfirmation($trace);
        $preview = new BulkUpdatePreviewAction(
            new RecordingAuthorization($trace, true),
            $confirmation,
            $repository,
        );

        $result = $preview->preview([2, 1], ['status' => true, 'theme' => false], ['status' => 'generated']);

        self::assertSame(2, $result->count);
        self::assertSame(['status'], $result->fields);
        self::assertSame(['status' => 'generated'], $result->targets);
        self::assertSame('confirmation-token', $result->confirmationToken);
        self::assertSame([], $repository->writes);
        self::assertSame(['permission', 'count', 'issue'], $trace);
        self::assertSame('administrator', $confirmation->lastSubject);
        if ($confirmation->lastOperation === null) {
            self::fail('Die bestätigte Serveroperation fehlt.');
        }
        self::assertSame([1, 2], $confirmation->lastOperation->ids);
        self::assertSame(['status' => 'generated'], $confirmation->lastOperation->changes);
    }

    #[Test]
    public function ausfuehrung_prueft_bestaetigung_und_aendert_nur_maskierte_felder(): void
    {
        $trace = [];
        $repository = new RecordingAssetRepository($trace);
        $confirmation = new RecordingConfirmation($trace);
        $confirmation->operationToConsume = new StoredOperation('asset-bulk-update', [1, 2], ['status' => 'generated']);
        $action = new BulkUpdateAction(
            new RecordingAuthorization($trace, true),
            new RecordingCsrf($trace, true),
            $confirmation,
            $repository,
        );

        $result = $action->execute('csrf', 'confirmation-token');

        self::assertSame(2, $result->updatedCount);
        self::assertSame([[1, 2], ['status' => 'generated']], $repository->writes[0]);
        self::assertSame(['permission', 'csrf', 'consume', 'bulk'], $trace);
    }

    #[Test]
    public function manipuliertes_bestaetigungstoken_verhindert_jeden_schreibzugriff(): void
    {
        $trace = [];
        $confirmation = new RecordingConfirmation($trace);
        $confirmation->accept = false;
        $repository = new RecordingAssetRepository($trace);
        $action = new BulkUpdateAction(
            new RecordingAuthorization($trace, true),
            new RecordingCsrf($trace, true),
            $confirmation,
            $repository,
        );

        $this->expectException(ConfirmationException::class);
        try {
            $action->execute('csrf', 'manipuliert');
        } finally {
            self::assertSame([], $repository->writes);
            self::assertSame(['permission', 'csrf', 'consume'], $trace);
        }
    }

    #[Test]
    public function beschaedigte_serveroperation_wird_vor_dem_repository_abgelehnt(): void
    {
        $trace = [];
        $confirmation = new RecordingConfirmation($trace);
        $confirmation->operationToConsume = new StoredOperation(
            'asset-bulk-update',
            [1],
            [],
        );
        $repository = new RecordingAssetRepository($trace);
        $action = new BulkUpdateAction(
            new RecordingAuthorization($trace, true),
            new RecordingCsrf($trace, true),
            $confirmation,
            $repository,
        );

        $this->expectException(ValidationException::class);
        try {
            $action->execute('csrf', 'confirmation-token');
        } finally {
            self::assertSame([], $repository->writes);
            self::assertSame(['permission', 'csrf', 'consume'], $trace);
        }
    }

    #[Test]
    public function lease_freigabefehler_nach_mutation_wird_sichtbar_eskaliert(): void
    {
        $trace = [];
        $confirmation = new RecordingConfirmation($trace);
        $confirmation->operationToConsume = new StoredOperation('asset-bulk-update', [1], ['status' => 'generated']);
        $confirmation->releaseFails = true;
        $repository = new RecordingAssetRepository($trace);
        $action = new BulkUpdateAction(
            new RecordingAuthorization($trace, true),
            new RecordingCsrf($trace, true),
            $confirmation,
            $repository,
        );

        $this->expectException(RuntimeException::class);
        try {
            $action->execute('csrf', 'confirmation-token');
        } finally {
            self::assertCount(1, $repository->writes);
        }
    }

    private function expectValidation(callable $operation): void
    {
        try {
            $operation();
            self::fail('Eine ValidationException wurde erwartet.');
        } catch (ValidationException) {
            self::addToAssertionCount(1);
        }
    }
}

final class RecordingAuthorization implements AuthorizationPortInterface
{
    /** @param list<string> $trace */
    public function __construct(private array &$trace, private readonly bool $allowed) {}

    public function assertCanManageAssets(): void
    {
        $this->trace[] = 'permission';
        if (!$this->allowed) {
            throw new AccessDeniedException('Keine Berechtigung für die Bildverwaltung.');
        }
    }

    public function subjectKey(): string
    {
        return 'administrator';
    }

    /** @return list<string> */
    public function traceSnapshot(): array
    {
        return $this->trace;
    }
}

final class RecordingCsrf implements CsrfPortInterface
{
    /** @param list<string> $trace */
    public function __construct(private array &$trace, private readonly bool $valid) {}

    public function assertValid(string $token): void
    {
        $this->trace[] = 'csrf';
        if (!$this->valid) {
            throw new CsrfException('Die Sicherheitsprüfung ist fehlgeschlagen.');
        }
    }

    /** @return list<string> */
    public function traceSnapshot(): array
    {
        return $this->trace;
    }
}

final class RecordingConfirmation implements ConfirmationPortInterface
{
    public bool $accept = true;
    public string $lastSubject = '';
    public ?StoredOperation $lastOperation = null;
    public ?StoredOperation $operationToConsume = null;
    public bool $releaseFails = false;

    /** @param list<string> $trace */
    public function __construct(private array &$trace) {}

    public function issue(string $subjectKey, StoredOperation $operation): string
    {
        $this->trace[] = 'issue';
        $this->lastSubject = $subjectKey;
        $this->lastOperation = $operation;

        return 'confirmation-token';
    }

    public function consume(string $subjectKey, string $token): ?ConfirmationLease
    {
        $this->trace[] = 'consume';

        if (!$this->accept || $token !== 'confirmation-token') {
            return null;
        }

        $operation = $this->operationToConsume ?? $this->lastOperation;

        return $operation === null
            ? null
            : new ConfirmationLease($operation, function (): void {
                if ($this->releaseFails) {
                    throw new RuntimeException('Erzwungener Lease-Freigabefehler.');
                }
            });
    }

    /** @return list<string> */
    public function traceSnapshot(): array
    {
        return $this->trace;
    }
}

final class RecordingAssetRepository implements AdminAssetRepositoryInterface
{
    /** @var list<array{0: list<int>, 1: array<string, string>}> */
    public array $writes = [];

    /** @param list<string> $trace */
    public function __construct(private array &$trace) {}

    public function countExistingIds(array $ids): int
    {
        $this->trace[] = 'count';

        return count($ids);
    }

    public function updateOneById(int $id, array $changes): void
    {
        $this->writes[] = [[$id], $changes];
    }

    /** @param list<int> $ids @param array<string, string> $changes */
    public function updateManyByIds(array $ids, array $changes): void
    {
        $this->trace[] = 'bulk';
        $this->writes[] = [$ids, $changes];
    }

    /** @return list<array<string, scalar|null>> */
    public function listPage(int $offset, int $limit, array $filters, string $sort, string $direction): array
    {
        return [];
    }

    public function countForList(array $filters): int
    {
        return 0;
    }

    public function detailById(int $id): ?array
    {
        return null;
    }

    /** @return list<string> */
    public function traceSnapshot(): array
    {
        return $this->trace;
    }
}
