<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Admin\Action\AssetListAction;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AdminAssetRepositoryInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AuthorizationPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Presentation\AssetDisplayMapper;
use Plugin\MGD_AI_Kennzeichnung\Admin\Presentation\LocalPreviewUrlResolver;
use Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel\AssetCardView;

final class AssetListActionTest extends TestCase
{
    #[Test]
    public function liefert_der_ansicht_nur_fertig_aufbereitete_galeriekarten(): void
    {
        $repository = new GalleryAssetRepositoryFake();
        $repository->rows = [[
            'id' => 7,
            'local_path' => '/media/image/storage/Bild groß.jpg',
            'status' => 'generated',
            'position' => 'top-left',
            'theme' => 'dark',
            'source' => 'product',
            'usage_count' => 2,
            'updated_at' => '2026-08-22 12:00:00',
        ]];
        $repository->total = 1;
        $action = new AssetListAction(
            new GalleryAuthorizationFake(),
            $repository,
            new LocalPreviewUrlResolver(),
            new AssetDisplayMapper(),
            'https://shop.example/plugin/frontend/',
        );

        $view = $action->load(1, 25, [], 'id', 'asc');

        self::assertCount(1, $view->items);
        $card = $view->items[0];
        self::assertInstanceOf(AssetCardView::class, $card);
        self::assertSame(7, $card->id);
        self::assertSame('Bild groß.jpg', $card->fileName);
        self::assertSame('https://shop.example/media/image/storage/Bild%20gro%C3%9F.jpg', $card->previewUrl);
        self::assertSame('generated', $card->status);
        self::assertSame('KI-generiert', $card->statusLabel);
        self::assertSame('Produkt', $card->sourceLabel);
        self::assertSame('top-left', $card->position);
        self::assertSame('dark', $card->theme);
        self::assertSame(2, $card->usageCount);
        self::assertSame('2026-08-22 12:00:00', $card->updatedAt);
    }
}

final class GalleryAuthorizationFake implements AuthorizationPortInterface
{
    public function assertCanManageAssets(): void {}

    public function subjectKey(): string
    {
        return 'gallery-test';
    }
}

final class GalleryAssetRepositoryFake implements AdminAssetRepositoryInterface
{
    /** @var list<array<string, scalar|null>> */
    public array $rows = [];
    public int $total = 0;

    public function countExistingIds(array $ids): int
    {
        return count($ids);
    }

    public function updateOneById(int $id, array $changes): void {}

    public function updateManyByIds(array $ids, array $changes): void {}

    public function listPage(int $offset, int $limit, array $filters, string $sort, string $direction): array
    {
        return $this->rows;
    }

    public function countForList(array $filters): int
    {
        return $this->total;
    }

    public function detailById(int $id): ?array
    {
        return null;
    }
}
