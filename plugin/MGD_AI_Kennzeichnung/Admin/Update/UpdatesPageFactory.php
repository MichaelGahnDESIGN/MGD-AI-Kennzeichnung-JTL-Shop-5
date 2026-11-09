<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Update;

use Plugin\MGD_AI_Kennzeichnung\UpdateClient\Admin\UpdatesPage;
use Plugin\MGD_AI_Kennzeichnung\UpdateClient\Factory;
use RuntimeException;

// Verdrahtung Updates Menue > MGD Update Client
final class UpdatesPageFactory
{
    // Produktname laut Update-API (Blocker B2: Server kennt das Produkt noch nicht)
    public const PRODUCT = 'mgd-ai-kennzeichnung';
    public const EDITION = 'free';
    public const PLUGIN_NAME = 'MGD AI Kennzeichnung';

    /**
     * @param callable():bool $csrfValid
     * @param callable():bool $canManage
     */
    public static function create(
        string $pluginDir,
        string $dataDir,
        string $frontendUrl,
        string $shopVersion,
        string $csrfField,
        callable $csrfValid,
        callable $canManage,
    ): UpdatesPage {
        $pluginDir = rtrim($pluginDir, '/');
        $host = parse_url($frontendUrl, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            throw new RuntimeException('Shop-Domain nicht ermittelbar.');
        }

        // Free: kein Lizenz-Provider (NullLicenseProvider im Client)
        return Factory::page(
            $pluginDir . '/update/root-keys.json',
            $dataDir,
            self::PRODUCT,
            self::EDITION,
            self::PLUGIN_NAME,
            PluginVersionReader::read($pluginDir . '/info.xml'),
            strtolower($host),
            $shopVersion,
            $csrfValid,
            $canManage,
            $csrfField,
        );
    }
}
