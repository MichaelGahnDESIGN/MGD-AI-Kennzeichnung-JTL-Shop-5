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

    /**
     * @param mixed $oldVersion
     * @param mixed $newVersion
     */
    public function updated($oldVersion, $newVersion): void {}

    public function getDB(): \JTL\DB\DbInterface
    {
        throw new \RuntimeException('Im reinen Bootstrap-Strukturtest ist keine Datenbank gesetzt.');
    }

    public function getCache(): \JTL\Cache\JTLCacheInterface
    {
        throw new \RuntimeException('Im reinen Bootstrap-Strukturtest ist kein Cache gesetzt.');
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
    /** @param array<int, int|string> $items Adminmenü-ID mit optionalem Dateinamen für echte Tab-Tests. */
    public function __construct(private readonly array $items = []) {}

    public function getItemByID(int $menuID): ?\stdClass
    {
        if (array_key_exists($menuID, $this->items) && is_string($this->items[$menuID])) {
            return (object) [
                'kPluginAdminMenu' => $menuID,
                'cDateiname' => $this->items[$menuID],
            ];
        }

        return in_array($menuID, $this->items, true) ? (object) ['kPluginAdminMenu' => $menuID] : null;
    }
}

class Paths
{
    public function __construct(
        private readonly string $adminURL = '/plugin/adminmenu/',
        private readonly string $frontendURL = 'https://example.test/plugin/frontend/',
        private readonly string $basePath = '/plugin/',
    ) {}

    public function getAdminURL(): string
    {
        return $this->adminURL;
    }

    public function getFrontendURL(): string
    {
        return $this->frontendURL;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
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
use JTL\Cache\JTLCacheInterface;
use JTL\DB\DbInterface;
use Psr\Log\LoggerInterface;

interface DefaultServicesInterface
{
    public function getDB(): DbInterface;

    public function getCache(): JTLCacheInterface;

    public function getAdminAccount(): AdminAccount;

    public function getLogService(): LoggerInterface;
}

namespace JTL\Cache;

/* JTL 5.7.2 nutzt diesen globalen Cache-Tag für Plugin-Konfigurationen. */
if (!\defined('CACHING_GROUP_PLUGIN')) {
    \define('CACHING_GROUP_PLUGIN', 'plgn');
}

/** Beschreibt den tatsächlichen, von JTLs Service-Container gelieferten Cachevertrag. */
interface JTLCacheInterface
{
    /** @param list<string> $tags */
    public function flushTags(array $tags): int;
}

/** Beobachtbarer Ersatz für JTLs tagbasierten Plugin-Cache. */
class JTLCache implements JTLCacheInterface
{
    /** @var list<list<string>> */
    public array $flushedTags = [];

    /** @param list<string> $tags */
    public function flushTags(array $tags): int
    {
        $this->flushedTags[] = $tags;

        return count($tags);
    }
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
    /** Die Testhülle macht erfolgreiche lokale Template-Renderings sichtbar. */
    public static string $testFetchOutput = '';

    /** @var list<array{name: string, value: mixed}> Dokumentiert Zuweisungen für Entry-Point-Vertragstests. */
    public static array $zuweisungen = [];

    /** @var list<string> Dokumentiert gezielt entfernte kompilierte Vorlagen. */
    public static array $geleerteTemplates = [];

    public function assign(string $name, mixed $value): self
    {
        self::$zuweisungen[] = ['name' => $name, 'value' => $value];

        return $this;
    }

    /**
     * Liefert alle Werte einer bestimmten Smarty-Zuweisung in ihrer tatsächlichen Reihenfolge.
     *
     * @return list<mixed>
     */
    public static function zuweisungenMitNamen(string $name): array
    {
        $werte = [];
        foreach (self::$zuweisungen as $zuweisung) {
            if ($zuweisung['name'] === $name) {
                $werte[] = $zuweisung['value'];
            }
        }

        return $werte;
    }

    public function fetch(string $path): string
    {
        return self::$testFetchOutput;
    }

    /**
     * @param mixed $resource_name
     * @param mixed $compile_id
     * @param mixed $exp_time
     */
    public function clearCompiledTemplate(
        $resource_name = null,
        $compile_id = null,
        $exp_time = null,
    ): int {
        if (is_string($resource_name)) {
            self::$geleerteTemplates[] = $resource_name;
        }

        return 1;
    }

    /**
     * Spiegelt JTLs Zugriff auf die tatsächlich aktive Smarty-Engine. Im
     * normalen Modus ist dies die Fassade selbst, im Legacy-Modus liefert JTL
     * stattdessen die intern gekapselte Smarty-4-Instanz.
     */
    public function getSmarty(): self
    {
        return $this;
    }
}

/**
 * Bildet JTLs ausdrücklich für das Backend erzeugte Smarty-Instanz ab.
 * Die Instanzzählung verhindert, dass Tests versehentlich die allgemeine
 * Shop-Smarty-Instanz mit dem Backend-Cache gleichsetzen.
 */
class BackendSmarty extends JTLSmarty
{
    public static int $erzeugteInstanzen = 0;
    public static ?\JTL\DB\DbInterface $letzteDatenbank = null;
    public static ?\JTL\Cache\JTLCacheInterface $letzterCache = null;

    public function __construct(\JTL\DB\DbInterface $db, \JTL\Cache\JTLCacheInterface $cache)
    {
        ++self::$erzeugteInstanzen;
        self::$letzteDatenbank = $db;
        self::$letzterCache = $cache;
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
