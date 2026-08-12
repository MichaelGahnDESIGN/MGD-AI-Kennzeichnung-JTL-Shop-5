<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Http\AdminRequestNormalizer;

final class AdminRequestNormalizerTest extends TestCase
{
    #[Test]
    public function echtes_bulk_formular_wird_streng_in_ein_typisiertes_dto_ueberfuehrt(): void
    {
        $request = (new AdminRequestNormalizer())->bulkPreview([
            'action' => 'bulk-preview',
            'csrf_token' => str_repeat('a', 32),
            'asset_ids' => ['2', '1'],
            'mask' => ['status' => '1', 'theme' => '0'],
            'values' => ['status' => 'generated'],
        ]);

        self::assertSame([2, 1], $request->assetIds);
        self::assertSame(['status' => true, 'theme' => false], $request->mask);
        self::assertSame(['status' => 'generated'], $request->values);
        self::assertSame(str_repeat('a', 32), $request->csrfToken);
    }

    #[Test]
    public function ids_akzeptieren_nur_kanonische_positive_dezimalstrings(): void
    {
        foreach (['01', '+1', '-1', ' 1', '1 ', '1e2', '0', ''] as $badId) {
            $this->expectRejected(['asset_ids' => [$badId]]);
        }
    }

    #[Test]
    public function unbekannte_checkbox_token_schluessel_und_zu_tiefe_arrays_werden_abgelehnt(): void
    {
        foreach ([
            ['mask' => ['status' => 'true']],
            ['mask' => ['status' => '1', 'evil' => '1']],
            ['values' => ['status' => ['generated']]],
            ['evil' => ['a' => ['b' => ['c' => 'd']]]],
        ] as $override) {
            $this->expectRejected($override);
        }
    }

    #[Test]
    public function csrf_und_bestaetigungstoken_sind_strings_mit_harter_laengengrenze(): void
    {
        foreach ([null, [], str_repeat('x', 257)] as $token) {
            try {
                (new AdminRequestNormalizer())->bulkExecute([
                    'action' => 'bulk-update',
                    'csrf_token' => $token,
                    'confirmation_token' => 'abc',
                ]);
                self::fail('Ungültige Tokens müssen abgelehnt werden.');
            } catch (ValidationException) {
                self::addToAssertionCount(1);
            }
        }
    }

    #[Test]
    public function listenparameter_werden_begrenzt_und_geschlossen_normalisiert(): void
    {
        $request = (new AdminRequestNormalizer())->assetList([
            'view' => 'list',
            'page' => '2',
            'page_size' => '50',
            'status' => 'generated',
            'source' => 'product',
            'present' => '1',
            'sort' => 'updated_at',
            'direction' => 'desc',
        ]);

        self::assertSame(2, $request->page);
        self::assertSame(50, $request->pageSize);
        self::assertSame(['status' => 'generated', 'source' => 'product', 'present' => true], $request->filters);
        self::assertSame('updated_at', $request->sort);
        self::assertSame('desc', $request->direction);

        foreach ([['page' => '01'], ['page_size' => '101'], ['present' => 'true'], ['evil' => 'x']] as $bad) {
            try {
                (new AdminRequestNormalizer())->assetList(['view' => 'list', ...$bad]);
                self::fail('Manipulierte Listenparameter müssen abgelehnt werden.');
            } catch (ValidationException) {
                self::addToAssertionCount(1);
            }
        }
    }

    /** @param array<string, mixed> $override */
    private function expectRejected(array $override): void
    {
        $payload = [...[
            'action' => 'bulk-preview',
            'csrf_token' => 'token',
            'asset_ids' => ['1'],
            'mask' => ['status' => '1'],
            'values' => ['status' => 'generated'],
        ], ...$override];
        try {
            (new AdminRequestNormalizer())->bulkPreview($payload);
            self::fail('Das manipulierte Formular muss abgelehnt werden.');
        } catch (ValidationException) {
            self::addToAssertionCount(1);
        }
    }
}
