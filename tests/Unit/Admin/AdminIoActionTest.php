<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

require_once __DIR__ . '/../../Stubs/JtlPluginStubs.php';

use JTL\Backend\AdminIO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Admin\IO\AdminIoRegistration;
use Plugin\MGD_AI_Kennzeichnung\Admin\IO\AdminIoResponse;
use Plugin\MGD_AI_Kennzeichnung\Admin\IO\LoadLocalAssetLabel;
use Plugin\MGD_AI_Kennzeichnung\Admin\IO\SaveLocalAssetLabel;
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

final class AdminIoActionTest extends TestCase
{
    #[Test]
    public function registriert_nur_die_beiden_eng_begrenzten_admin_funktionen(): void
    {
        $io = new AdminIO();
        $service = $this->service();

        (new AdminIoRegistration(
            new LoadLocalAssetLabel($service),
            new SaveLocalAssetLabel($service),
        ))->register($io);

        self::assertSame(['mgd_ai_label_load', 'mgd_ai_label_save'], $io->registeredNames());
    }

    #[Test]
    public function laedt_und_speichert_ueber_feste_positionsparameter(): void
    {
        $io = new AdminIO();
        $repository = new AdminIoRepositoryFake();
        $service = $this->service($repository);
        (new AdminIoRegistration(
            new LoadLocalAssetLabel($service),
            new SaveLocalAssetLabel($service),
        ))->register($io);

        $load = $io->executeForTest('mgd_ai_label_load', ['/opc/banner/bild.png']);
        $save = $io->executeForTest('mgd_ai_label_save', [
            '/opc/banner/bild.png',
            'opc',
            'generated',
            'top-right',
            'dark',
        ]);

        self::assertInstanceOf(AdminIoResponse::class, $load);
        self::assertTrue($load->ok);
        self::assertSame('unreviewed', $load->data['status'] ?? null);
        self::assertInstanceOf(AdminIoResponse::class, $save);
        self::assertTrue($save->ok);
        self::assertSame('generated', $save->data['status'] ?? null);
    }

    #[Test]
    public function fehlerantworten_enthalten_keine_rohwerte_oder_internen_details(): void
    {
        $load = (new LoadLocalAssetLabel($this->service()))('/opc/a.jpg', '<script>');
        $save = (new SaveLocalAssetLabel($this->service()))(
            '/opc/a.jpg',
            'opc',
            '<script>',
            'bottom-right',
            'auto',
        );

        self::assertFalse($load->ok);
        self::assertSame('invalid_request', $load->code);
        self::assertFalse($save->ok);
        self::assertSame('validation_failed', $save->code);
        self::assertStringNotContainsString('<script>', serialize([$load, $save]));
        self::assertStringNotContainsString('/opc/a.jpg', serialize([$load, $save]));
    }

    private function service(?AdminIoRepositoryFake $repository = null): LocalAssetLabelService
    {
        return new LocalAssetLabelService(
            new AdminIoAuthorizationFake(),
            $repository ?? new AdminIoRepositoryFake(),
            new LocalPathNormalizer(),
            new LocalPreviewUrlResolver(),
        );
    }
}

final class AdminIoAuthorizationFake implements AuthorizationPortInterface
{
    public function assertCanManageAssets(): void {}

    public function subjectKey(): string
    {
        return 'admin-io-test';
    }
}

final class AdminIoRepositoryFake implements LocalAssetLabelRepositoryInterface
{
    public function findByLocalPath(string $localPath): ?LocalAssetLabel
    {
        return null;
    }

    public function save(
        string $localPath,
        AssetSource $source,
        LabelStatus $status,
        LabelPosition $position,
        LabelTheme $theme,
    ): LocalAssetLabel {
        return new LocalAssetLabel(9, $localPath, $status, $position, $theme, $source, true);
    }
}
