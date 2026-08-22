<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

require_once __DIR__ . '/../../Stubs/JtlDatabaseStubs.php';

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Domain\AssetSource;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelPosition;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelStatus;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelTheme;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\LocalAssetLabelRepository;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\SchemaOwnershipGuard;
use RuntimeException;
use Tests\Support\TransactionalDatabaseFake;

final class LocalAssetLabelRepositoryTest extends TestCase
{
    #[Test]
    public function findet_ein_vorhandenes_bild_mit_seiner_bestehenden_quelle(): void
    {
        $db = $this->database();
        $key = hash('sha256', 'media/image/bild.jpg');
        $db->seedScanAsset($key, 'media/image/bild.jpg', 'generated');
        $db->seedScanUsage($key, 'media/image/bild.jpg', 'artikel:9', 'product');

        $label = (new LocalAssetLabelRepository($db))->findByLocalPath('media/image/bild.jpg');

        self::assertNotNull($label);
        self::assertSame(1, $label->id);
        self::assertSame(LabelStatus::Generated, $label->status);
        self::assertSame(AssetSource::Product, $label->source);
    }

    #[Test]
    public function speichert_ein_unbekanntes_bild_mit_fundstelle_atomar_und_idempotent(): void
    {
        $db = $this->database();
        $repository = new LocalAssetLabelRepository($db);

        $first = $repository->save(
            'opc/banner/neu.png',
            AssetSource::Opc,
            LabelStatus::PartiallyGenerated,
            LabelPosition::BottomLeft,
            LabelTheme::Light,
        );
        $second = $repository->save(
            'opc/banner/neu.png',
            AssetSource::Opc,
            LabelStatus::PartiallyGenerated,
            LabelPosition::BottomLeft,
            LabelTheme::Light,
        );

        self::assertSame($first->id, $second->id);
        self::assertSame(1, $db->assetCount());
        self::assertSame(1, $db->usageCount());
        self::assertSame(2, $db->commits);
        self::assertSame(
            ['status' => 'partially-generated', 'position' => 'bottom-left', 'theme' => 'light'],
            $db->presentationForAsset(hash('sha256', 'opc/banner/neu.png')),
        );
    }

    #[Test]
    public function commitfehler_rollt_neues_asset_und_fundstelle_vollstaendig_zurueck(): void
    {
        $db = $this->database();
        $db->returnFalseOnCommit = true;

        try {
            (new LocalAssetLabelRepository($db))->save(
                'opc/banner/fehler.png',
                AssetSource::Opc,
                LabelStatus::Generated,
                LabelPosition::BottomRight,
                LabelTheme::Auto,
            );
            self::fail('Ein fehlgeschlagener Commit muss die gesamte Speicherung abbrechen.');
        } catch (RuntimeException $error) {
            self::assertSame('Die lokale Bildkennzeichnung konnte nicht bestätigt werden.', $error->getMessage());
            self::assertSame(0, $db->assetCount());
            self::assertSame(0, $db->usageCount());
            self::assertSame(1, $db->rollbacks);
        }
    }

    private function database(): TransactionalDatabaseFake
    {
        $db = new TransactionalDatabaseFake();
        $db->setMarker('xplugin_mgd_ai_asset', SchemaOwnershipGuard::OWNERSHIP_MARKER);
        $db->setMarker('xplugin_mgd_ai_usage', SchemaOwnershipGuard::OWNERSHIP_MARKER);

        return $db;
    }
}
