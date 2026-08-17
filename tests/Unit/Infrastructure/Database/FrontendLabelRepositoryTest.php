<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Database;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\FrontendLabelRepository;
use Tests\Support\TransactionalDatabaseFake;

final class FrontendLabelRepositoryTest extends TestCase
{
    #[Test]
    public function nur_vorhandene_und_sichtbar_klassifizierte_bilder_werden_geladen(): void
    {
        $db = new TransactionalDatabaseFake();
        $db->seedScanAsset('sichtbar', 'media/sichtbar.webp', 'generated');
        $db->seedScanUsage('sichtbar', 'media/sichtbar.webp', 'produkt-1');
        $db->seedScanAsset('ungeprueft', 'media/ungeprueft.webp', 'unreviewed');
        $db->seedScanUsage('ungeprueft', 'media/ungeprueft.webp', 'produkt-2');

        $labels = (new FrontendLabelRepository($db))->visibleLabels();

        self::assertSame([[
            'local_path' => 'media/sichtbar.webp',
            'status' => 'generated',
            'position' => 'bottom-right',
            'theme' => 'auto',
            'source_type' => 'product',
        ]], $labels);
        self::assertStringContainsString('`usage`.`is_present` = 1', $db->statements[0]['sql']);
        self::assertStringContainsString('LIMIT 500', $db->statements[0]['sql']);
        self::assertSame([], $db->statements[0]['params']);
    }
}
