<?php

declare(strict_types=1);

/*
 * Diese Datei beschreibt PHPStan nur die beiden JTL-Typen, die das minimale
 * Grundgerüst benötigt. Sie wird nicht von Composer geladen und gelangt daher
 * niemals in die Laufzeit eines JTL-Shops.
 */

namespace JTL\Events;

class Dispatcher {}

namespace JTL\Plugin;

use JTL\Events\Dispatcher;

class Bootstrapper
{
    /** Zählt im Strukturtest ausschließlich die Weitergabe an den JTL-Elternbootstrap. */
    public static int $bootAufrufe = 0;

    public function boot(Dispatcher $dispatcher): void
    {
        ++self::$bootAufrufe;
    }
}

interface PluginInterface
{
    public function getID(): int;

    public function getPaths(): \JTL\Plugin\Data\Paths;
}

namespace JTL\Plugin\Data;

class Paths
{
    public function __construct(private readonly string $adminURL = '/plugin/adminmenu/') {}

    public function getAdminURL(): string
    {
        return $this->adminURL;
    }
}

namespace JTL\Backend;

class Permissions
{
    public const PLUGIN_DETAIL_VIEW_ALL = 'PLUGIN_DETAIL_VIEW_ALL';
    public const PLUGIN_DETAIL_VIEW_ID = 'PLUGIN_DETAIL_VIEW_';
}

class AdminAccount
{
    /** @param list<string> $permissions */
    public function __construct(private readonly array $permissions = [], private readonly int $id = 1) {}

    public function permission(string $permission, bool $redirectToLogin = false, bool $showNoAccessPage = false): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    public function getID(): int
    {
        return $this->id;
    }
}

namespace JTL\Helpers;

class Form
{
    public static string $validToken = 'csrf';

    public static function validateToken(?string $token = null): bool
    {
        return $token === self::$validToken;
    }
}

namespace JTL\Services;

use JTL\Backend\AdminAccount;
use JTL\DB\DbInterface;
use Psr\Log\LoggerInterface;

interface DefaultServicesInterface
{
    public function getDB(): DbInterface;

    public function getAdminAccount(): AdminAccount;

    public function getLogService(): LoggerInterface;
}

namespace JTL;

use JTL\Services\DefaultServicesInterface;

class Shop
{
    public static ?DefaultServicesInterface $container = null;

    public static function Container(): DefaultServicesInterface
    {
        if (self::$container === null) {
            throw new \RuntimeException('Der JTL-Testcontainer wurde nicht gesetzt.');
        }

        return self::$container;
    }
}
