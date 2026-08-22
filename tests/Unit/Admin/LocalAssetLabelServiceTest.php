<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\AccessDeniedException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AuthorizationPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\LocalAssetLabelRepositoryInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Presentation\LocalPreviewUrlResolver;
use Plugin\MGD_AI_Kennzeichnung\Admin\Service\LocalAssetLabelService;
use Plugin\MGD_AI_Kennzeichnung\Admin\Value\LocalAssetLabel;
use Plugin\MGD_AI_Kennzeichnung\Domain\AssetSource;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelPosition;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelStatus;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelTheme;
use Plugin\MGD_AI_Kennzeichnung\Scanner\LocalPathNormalizer;

final class LocalAssetLabelServiceTest extends TestCase
{
    #[Test]
    public function laedt_eine_bekannte_kennzeichnung_nach_berechtigungs_und_pfadpruefung(): void
    {
        $trace = new LocalLabelTrace();
        $repository = new LocalLabelRepositoryFake($trace);
        $repository->found = new LocalAssetLabel(
            7,
            'media/image/storage/bild.jpg',
            LabelStatus::Generated,
            LabelPosition::TopLeft,
            LabelTheme::Dark,
            AssetSource::Product,
            true,
        );
        $service = $this->service($trace, $repository);

        $label = $service->load('/media/image/storage/bild.jpg');

        self::assertSame(7, $label->id);
        self::assertSame(LabelStatus::Generated, $label->status);
        self::assertSame(['permission', 'find:media/image/storage/bild.jpg'], $trace->events);
    }

    #[Test]
    public function unbekanntes_bild_bleibt_beim_laden_unveraendert_und_ungespeichert(): void
    {
        $trace = new LocalLabelTrace();
        $repository = new LocalLabelRepositoryFake($trace);
        $service = $this->service($trace, $repository);

        $label = $service->load('/opc/banner/neu.png');

        self::assertNull($label->id);
        self::assertFalse($label->persisted);
        self::assertSame(LabelStatus::Unreviewed, $label->status);
        self::assertSame(LabelPosition::BottomRight, $label->position);
        self::assertSame(LabelTheme::Auto, $label->theme);
        self::assertSame([], $repository->writes);
    }

    #[Test]
    public function speichert_nur_exakte_erlaubte_werte_und_normalisierten_lokalen_pfad(): void
    {
        $trace = new LocalLabelTrace();
        $repository = new LocalLabelRepositoryFake($trace);
        $service = $this->service($trace, $repository);

        $label = $service->save(
            '/opc//banner/./motiv.png',
            'opc',
            'partially-generated',
            'bottom-left',
            'light',
        );

        self::assertTrue($label->persisted);
        self::assertSame(41, $label->id);
        self::assertSame([[
            'opc/banner/motiv.png',
            AssetSource::Opc,
            LabelStatus::PartiallyGenerated,
            LabelPosition::BottomLeft,
            LabelTheme::Light,
        ]], $repository->writes);
    }

    #[Test]
    public function verwirft_ungueltige_pfade_und_enumwerte_vor_jedem_schreiben(): void
    {
        foreach ([
            ['https://fremd.example/a.jpg', 'opc', 'generated', 'bottom-right', 'auto'],
            ['/uploads/frei.jpg', 'opc', 'generated', 'bottom-right', 'auto'],
            ['/opc/a.svg', 'opc', 'generated', 'bottom-right', 'auto'],
            ['/opc/a.jpg', 'product', 'generated', 'bottom-right', 'auto'],
            ['/opc/a.jpg', 'opc', ' GENERATED ', 'bottom-right', 'auto'],
            ['/opc/a.jpg', 'opc', 'generated', 'mitte', 'auto'],
            ['/opc/a.jpg', 'opc', 'generated', 'bottom-right', '<script>'],
        ] as $werte) {
            $trace = new LocalLabelTrace();
            $repository = new LocalLabelRepositoryFake($trace);
            $service = $this->service($trace, $repository);
            try {
                $service->save(...$werte);
                self::fail('Unsichere Pfade oder Werte müssen abgelehnt werden.');
            } catch (ValidationException) {
                self::assertSame([], $repository->writes);
            }
        }
    }

    #[Test]
    public function prueft_die_berechtigung_immer_vor_repositoryzugriff(): void
    {
        $trace = new LocalLabelTrace();
        $repository = new LocalLabelRepositoryFake($trace);
        $service = new LocalAssetLabelService(
            new LocalLabelAuthorizationFake($trace, false),
            $repository,
            new LocalPathNormalizer(),
            new LocalPreviewUrlResolver(),
        );

        $this->expectException(AccessDeniedException::class);
        try {
            $service->load('/media/image/a.jpg');
        } finally {
            self::assertSame(['permission'], $trace->events);
        }
    }

    #[Test]
    public function identisches_wiederholtes_speichern_bleibt_idempotent(): void
    {
        $trace = new LocalLabelTrace();
        $repository = new LocalLabelRepositoryFake($trace);
        $service = $this->service($trace, $repository);

        $first = $service->save('/media/image/a.jpg', 'custom-local-manual', 'modified', 'top-right', 'dark');
        $second = $service->save('/media/image/a.jpg', 'custom-local-manual', 'modified', 'top-right', 'dark');

        self::assertSame($first->id, $second->id);
        self::assertCount(2, $repository->writes);
    }

    private function service(LocalLabelTrace $trace, LocalLabelRepositoryFake $repository): LocalAssetLabelService
    {
        return new LocalAssetLabelService(
            new LocalLabelAuthorizationFake($trace, true),
            $repository,
            new LocalPathNormalizer(),
            new LocalPreviewUrlResolver(),
        );
    }
}

final class LocalLabelAuthorizationFake implements AuthorizationPortInterface
{
    public function __construct(private readonly LocalLabelTrace $trace, private readonly bool $allowed) {}

    public function assertCanManageAssets(): void
    {
        $this->trace->events[] = 'permission';
        if (!$this->allowed) {
            throw new AccessDeniedException('Nicht erlaubt.');
        }
    }

    public function subjectKey(): string
    {
        return 'local-label-test';
    }
}

final class LocalLabelRepositoryFake implements LocalAssetLabelRepositoryInterface
{
    public ?LocalAssetLabel $found = null;
    /** @var list<array{string, AssetSource, LabelStatus, LabelPosition, LabelTheme}> */
    public array $writes = [];

    public function __construct(private readonly LocalLabelTrace $trace) {}

    public function findByLocalPath(string $localPath): ?LocalAssetLabel
    {
        $this->trace->events[] = 'find:' . $localPath;

        return $this->found;
    }

    public function save(
        string $localPath,
        AssetSource $source,
        LabelStatus $status,
        LabelPosition $position,
        LabelTheme $theme,
    ): LocalAssetLabel {
        $this->trace->events[] = 'save:' . $localPath;
        $this->writes[] = [$localPath, $source, $status, $position, $theme];

        return new LocalAssetLabel(41, $localPath, $status, $position, $theme, $source, true);
    }
}

final class LocalLabelTrace
{
    /** @var list<string> */
    public array $events = [];
}
