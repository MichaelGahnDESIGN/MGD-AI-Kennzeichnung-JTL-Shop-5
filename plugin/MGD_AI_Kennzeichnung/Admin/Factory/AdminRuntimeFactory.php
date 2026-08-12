<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Factory;

use JTL\Backend\AdminAccount;
use JTL\DB\DbInterface;
use JTL\Plugin\PluginInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Action\AdminActionHandler;
use Plugin\MGD_AI_Kennzeichnung\Admin\Action\AssetDetailAction;
use Plugin\MGD_AI_Kennzeichnung\Admin\Action\AssetListAction;
use Plugin\MGD_AI_Kennzeichnung\Admin\Action\BulkUpdateAction;
use Plugin\MGD_AI_Kennzeichnung\Admin\Action\BulkUpdatePreviewAction;
use Plugin\MGD_AI_Kennzeichnung\Admin\Action\CleanupAction;
use Plugin\MGD_AI_Kennzeichnung\Admin\Action\CleanupListAction;
use Plugin\MGD_AI_Kennzeichnung\Admin\Action\CleanupPreviewAction;
use Plugin\MGD_AI_Kennzeichnung\Admin\Action\ScanAction;
use Plugin\MGD_AI_Kennzeichnung\Admin\Action\SingleUpdateAction;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\ImageScanServiceAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlAuthorizationAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlCsrfAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlLogAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\OneTimeConfirmationAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\SessionConfirmationStore;
use Plugin\MGD_AI_Kennzeichnung\Admin\Controller\AdminAssetController;
use Plugin\MGD_AI_Kennzeichnung\Admin\Http\AdminRequestNormalizer;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\AssetRepository;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\UsageRepository;
use Plugin\MGD_AI_Kennzeichnung\Scanner\Adapter\BannerSourceAdapter;
use Plugin\MGD_AI_Kennzeichnung\Scanner\Adapter\CategorySourceAdapter;
use Plugin\MGD_AI_Kennzeichnung\Scanner\Adapter\ManufacturerSourceAdapter;
use Plugin\MGD_AI_Kennzeichnung\Scanner\Adapter\OpcSourceAdapter;
use Plugin\MGD_AI_Kennzeichnung\Scanner\Adapter\ProductSourceAdapter;
use Plugin\MGD_AI_Kennzeichnung\Scanner\LocalPathNormalizer;
use Plugin\MGD_AI_Kennzeichnung\Service\ImageScanService;
use Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel\AdminRoute;
use Psr\Log\LoggerInterface;

/** Erstellt den vollständigen Admin-Laufzeitgraphen ohne Service-Locator in Fachklassen. */
final class AdminRuntimeFactory
{
    /** @param array<string, mixed> $session */
    public function create(
        PluginInterface $plugin,
        DbInterface $db,
        AdminAccount $account,
        LoggerInterface $logger,
        array &$session,
        string $sessionId,
        int $adminMenuId,
    ): AdminAssetController {
        $authorization = new JtlAuthorizationAdapter($account, $plugin->getID(), $sessionId);
        $csrf = new JtlCsrfAdapter($session);
        $confirmation = new OneTimeConfirmationAdapter(new SessionConfirmationStore($session));
        $assets = new AssetRepository($db);
        $usages = new UsageRepository($db);
        $pathNormalizer = new LocalPathNormalizer();
        $scanner = new ImageScanService(
            [
                new ProductSourceAdapter($db, $pathNormalizer),
                new CategorySourceAdapter($db, $pathNormalizer),
                new ManufacturerSourceAdapter($db, $pathNormalizer),
                new BannerSourceAdapter($db, $pathNormalizer),
                new OpcSourceAdapter($db, $pathNormalizer),
            ],
            $assets,
            $usages,
        );
        $handler = new AdminActionHandler(
            new AssetListAction($authorization, $assets),
            new AssetDetailAction($authorization, $assets),
            new CleanupListAction($authorization, $usages),
            new SingleUpdateAction($authorization, $csrf, $assets),
            new BulkUpdatePreviewAction($authorization, $confirmation, $assets),
            new BulkUpdateAction($authorization, $csrf, $confirmation, $assets),
            new ScanAction($authorization, $csrf, new ImageScanServiceAdapter($scanner), new JtlLogAdapter($logger)),
            new CleanupPreviewAction($authorization, $confirmation, $usages),
            new CleanupAction($authorization, $csrf, $confirmation, $usages),
            $csrf,
            $plugin->getPaths()->getAdminURL() . 'assets.js',
            new AdminRoute($plugin->getID(), $adminMenuId),
        );

        return new AdminAssetController($authorization, $csrf, new AdminRequestNormalizer(), $handler);
    }
}
