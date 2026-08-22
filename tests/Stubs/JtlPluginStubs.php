<?php

declare(strict_types=1);

/*
 * Diese Datei beschreibt PHPStan nur die beiden JTL-Typen, die das minimale
 * Grundgerüst benötigt. Sie wird nicht von Composer geladen und gelangt daher
 * niemals in die Laufzeit eines JTL-Shops.
 */

namespace JTL\Events;

class Dispatcher
{
    /** @var array<string, callable> */
    private array $listeners = [];

    public function listen(string $event, callable $listener): void
    {
        $this->listeners[$event] = $listener;
    }

    /** @return list<string> */
    public function events(): array
    {
        return array_keys($this->listeners);
    }

    /** @param array<mixed> $arguments */
    public function dispatch(string $event, array $arguments): void
    {
        ($this->listeners[$event])($arguments);
    }
}

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

    public function preInstallCheck(): bool
    {
        return true;
    }

    public function uninstalled(bool $deleteData = true): void {}

    public function getDB(): \JTL\DB\DbInterface
    {
        throw new \RuntimeException('Im reinen Bootstrap-Strukturtest ist keine Datenbank gesetzt.');
    }

    public function getPlugin(): PluginInterface
    {
        throw new \RuntimeException('Im reinen Bootstrap-Strukturtest ist kein Pluginobjekt gesetzt.');
    }
}

interface PluginInterface
{
    public function getID(): int;

    public function getPaths(): \JTL\Plugin\Data\Paths;

    public function getAdminMenu(): \JTL\Plugin\Data\AdminMenu;

    public function getConfig(): \JTL\Plugin\Data\Config;
}

namespace JTL\Plugin\Data;

class AdminMenu
{
    /** @param list<int> $ids */
    public function __construct(private readonly array $ids = []) {}

    public function getItemByID(int $menuID): ?\stdClass
    {
        return in_array($menuID, $this->ids, true) ? (object) ['kPluginAdminMenu' => $menuID] : null;
    }
}

class Paths
{
    public function __construct(
        private readonly string $adminURL = '/plugin/adminmenu/',
        private readonly string $frontendURL = 'https://example.test/plugin/frontend/',
    ) {}

    public function getAdminURL(): string
    {
        return $this->adminURL;
    }

    public function getFrontendURL(): string
    {
        return $this->frontendURL;
    }
}

class Config
{
    /** @param array<string, mixed> $values */
    public function __construct(private readonly array $values = []) {}

    public function getValue(string $name): mixed
    {
        return $this->values[$name] ?? null;
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
    public int $permissionCalls = 0;

    /** @param list<string> $permissions */
    public function __construct(
        private readonly array $permissions = [],
        private readonly int $id = 1,
        private readonly bool $logged = true,
    ) {}

    public function logged(): bool
    {
        return $this->logged;
    }

    public function permission(string $permission, bool $redirectToLogin = false, bool $showNoAccessPage = false): bool
    {
        ++$this->permissionCalls;

        return in_array($permission, $this->permissions, true);
    }

    public function getID(): int
    {
        return $this->id;
    }
}

/** Beobachtbarer Ersatz für JTLs bereits authentifizierten Admin-IO-Container. */
class AdminIO
{
    /** @var array<string, callable> */
    private array $functions = [];

    /**
     * @param array{object|string, string}|callable|null $function
     */
    public function register(
        string $name,
        array|callable|null $function = null,
        ?string $include = null,
        ?string $permission = null,
    ): self {
        if (!is_callable($function) || isset($this->functions[$name])) {
            throw new \RuntimeException('Ungültige oder doppelte Admin-IO-Registrierung.');
        }
        $this->functions[$name] = $function;

        return $this;
    }

    /** @return list<string> */
    public function registeredNames(): array
    {
        return array_keys($this->functions);
    }

    /** @param list<mixed> $params */
    public function executeForTest(string $name, array $params): mixed
    {
        return ($this->functions[$name])(...$params);
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
    public static bool $frontend = true;

    public static function Container(): DefaultServicesInterface
    {
        if (self::$container === null) {
            throw new \RuntimeException('Der JTL-Testcontainer wurde nicht gesetzt.');
        }

        return self::$container;
    }

    public static function getLanguageCode(): string
    {
        return 'ger';
    }

    public static function isFrontend(): bool
    {
        return self::$frontend;
    }

    public static function Smarty(): \JTL\Smarty\JTLSmarty
    {
        return new \JTL\Smarty\JTLSmarty();
    }
}

namespace JTL\Smarty;

class JTLSmarty
{
    public function assign(string $name, mixed $value): self
    {
        return $this;
    }

    public function fetch(string $path): string
    {
        return '';
    }
}

namespace JTL\OPC;

use JTL\DB\DbInterface;

class Portlet
{
    protected string $title = '';
    protected string $group = '';
    protected bool $active = false;

    public function __construct(protected DbInterface $db) {}
}

class PortletInstance {}
