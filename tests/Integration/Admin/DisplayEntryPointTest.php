<?php

declare(strict_types=1);

namespace Tests\Integration\Admin;

require_once __DIR__ . '/../../Stubs/JtlDatabaseStubs.php';
require_once __DIR__ . '/../../Stubs/JtlPluginStubs.php';
require_once __DIR__ . '/../../Support/TransactionalDatabaseFake.php';

use JTL\Backend\AdminAccount;
use JTL\Cache\JTLCache;
use JTL\Cache\JTLCacheInterface;
use JTL\DB\DbInterface;
use JTL\Helpers\Form;
use JTL\Plugin\Data\AdminMenu;
use JTL\Plugin\Data\Config;
use JTL\Plugin\Data\Paths;
use JTL\Plugin\PluginInterface;
use JTL\Services\DefaultServicesInterface;
use JTL\Shop;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Tests\Support\TransactionStatePdo;
use Tests\Support\TransactionalDatabaseFake;

/** Prüft den geschützten Darstellungstab mit einem echten Include und JTL-Testcontainer. */
final class DisplayEntryPointTest extends TestCase
{
    #[Test]
    public function gueltiger_get_laesst_den_darstellungstab_ohne_schreibzugriff_laden(): void
    {
        $db = new DisplayEntryDatabase();
        $logger = new DisplayEntryLogger();
        $this->bereiteKontextVor(new AdminAccount(['PLUGIN_DETAIL_VIEW_17']), $db, $logger);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = $this->aktiveGetRoute(9);
        $_POST = [];

        $output = $this->fuehreEinstiegAus();

        self::assertSame('', $output);
        self::assertSame([], $db->updates);
        self::assertSame([], $logger->records);
    }

    #[Test]
    public function deaktivierte_persistente_updatehinweise_bleiben_im_gueltigen_display_get_ohne_nebenwirkung(): void
    {
        $db = new DisplayEntryDatabase();
        $logger = new DisplayEntryLogger();
        $this->bereiteKontextVor(new AdminAccount(['PLUGIN_DETAIL_VIEW_17']), $db, $logger);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = $this->aktiveGetRoute(9);
        $_POST = [];

        $output = $this->fuehreEinstiegAus();

        self::assertSame('', $output);
        self::assertSame([], $db->updates);
        self::assertSame([], $logger->records);
        self::assertSame('N', (new DisplayEntryPlugin())->getConfig()->getValue('update_notices'));
    }

    #[Test]
    public function gueltiger_post_prueft_die_route_und_speichert_nur_die_sieben_darstellungswerte(): void
    {
        $db = new DisplayEntryDatabase();
        $logger = new DisplayEntryLogger();
        $this->bereiteKontextVor(new AdminAccount(['PLUGIN_DETAIL_VIEW_17']), $db, $logger);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = [];
        $_POST = $this->gueltigerPost();

        $this->fuehreEinstiegAus();

        self::assertSame([
            'language' => 'de',
            'font_size' => '18',
            'outer_margin' => '12',
            'inner_padding' => '8',
            'border_radius' => '10',
            'blur' => '6',
            'transparency' => '20',
        ], $db->values);
        self::assertSame([], $logger->records);
    }

    #[Test]
    public function unberechtigter_admin_erhaelt_im_jtl_zyklus_einen_inline_alert_bei_status_200(): void
    {
        $db = new DisplayEntryDatabase();
        $this->bereiteKontextVor(new AdminAccount([]), $db, new DisplayEntryLogger());
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = $this->aktiveGetRoute(9);
        $_POST = [];

        $output = $this->fuehreEinstiegAus();

        self::assertSame(200, http_response_code());
        self::assertStringContainsString('role="alert"', $output);
        self::assertStringContainsString('keine Berechtigung', $output);
        self::assertSame([], $db->updates);
    }

    #[Test]
    public function fehlende_oder_fremde_route_wird_im_jtl_zyklus_neutral_ignoriert(): void
    {
        $db = new DisplayEntryDatabase();
        $this->bereiteKontextVor(new AdminAccount(['PLUGIN_DETAIL_VIEW_17']), $db, new DisplayEntryLogger());
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = [];
        $_POST = [];

        $output = $this->fuehreEinstiegAus(99);

        self::assertSame(200, http_response_code());
        self::assertSame('', $output);
        self::assertSame([], $db->updates);
    }

    #[Test]
    public function anderer_bestehender_plugin_tab_wird_nicht_als_darstellungstab_akzeptiert(): void
    {
        $db = new DisplayEntryDatabase();
        $this->bereiteKontextVor(new AdminAccount(['PLUGIN_DETAIL_VIEW_17']), $db, new DisplayEntryLogger());
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = $this->aktiveGetRoute(10);
        $_POST = [];

        $output = $this->fuehreEinstiegAus(10);

        self::assertSame(200, http_response_code());
        self::assertSame('', $output);
        self::assertSame([], $db->updates);
    }

    #[Test]
    public function fehlendes_sessiontoken_wird_im_jtl_zyklus_vor_jedem_schreibversuch_als_inline_alert_gemeldet(): void
    {
        $db = new DisplayEntryDatabase();
        $this->bereiteKontextVor(new AdminAccount(['PLUGIN_DETAIL_VIEW_17']), $db, new DisplayEntryLogger());
        $_SESSION = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = [];
        $_POST = $this->gueltigerPost();

        $output = $this->fuehreEinstiegAus();

        self::assertSame(200, http_response_code());
        self::assertStringContainsString('role="alert"', $output);
        self::assertSame([], $db->updates);
    }

    #[Test]
    public function csrf_fehler_und_unerwartete_postfelder_werden_im_jtl_zyklus_ohne_speichern_als_inline_alert_gemeldet(): void
    {
        foreach ([
            'csrf' => ['csrf_token' => 'falsch'],
            'unbekanntes Feld' => ['manipuliert' => '1'],
        ] as $beschreibung => $abweichung) {
            $db = new DisplayEntryDatabase();
            $this->bereiteKontextVor(new AdminAccount(['PLUGIN_DETAIL_VIEW_17']), $db, new DisplayEntryLogger());
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_GET = [];
            $_POST = $abweichung + $this->gueltigerPost();

            $output = $this->fuehreEinstiegAus();

            self::assertSame(200, http_response_code(), $beschreibung);
            self::assertStringContainsString('role="alert"', $output, $beschreibung);
            self::assertStringContainsString('nicht sicher verarbeiten', $output);
            self::assertSame([], $db->updates, $beschreibung);
        }
    }

    #[Test]
    public function unerwarteter_fehler_wird_im_jtl_zyklus_als_inline_alert_gemeldet_und_loggt_nur_den_technischen_eventcode(): void
    {
        $db = new DisplayEntryDatabase();
        $db->throwOnUpdate = true;
        $logger = new DisplayEntryLogger();
        $this->bereiteKontextVor(new AdminAccount(['PLUGIN_DETAIL_VIEW_17']), $db, $logger);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = [];
        $_POST = $this->gueltigerPost(['csrf_token' => 'geheimer-token']);
        Form::$validToken = 'geheimer-token';

        $output = $this->fuehreEinstiegAus();

        self::assertSame(200, http_response_code());
        self::assertStringContainsString('role="alert"', $output);
        self::assertStringContainsString('nicht abschließen', $output);
        self::assertSame([['mgd_ai_admin_event', ['event_code' => 'display_request_failed']]], $logger->records);
        self::assertStringNotContainsString('geheimer-token', serialize($logger->records));
    }

    #[Test]
    public function cachefehler_nach_dem_commit_meldet_den_gespeicherten_status_ohne_erneuten_schreibversuch(): void
    {
        $db = new DisplayEntryDatabase();
        $logger = new DisplayEntryLogger();
        $this->bereiteKontextVor(
            new AdminAccount(['PLUGIN_DETAIL_VIEW_17']),
            $db,
            $logger,
            new DisplayEntryFailingCache(),
        );
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = [];
        $_POST = $this->gueltigerPost();

        $output = $this->fuehreEinstiegAus();

        self::assertSame(200, http_response_code());
        self::assertStringContainsString('Werte gespeichert, Cache nicht aktualisiert', $output);
        self::assertCount(7, $db->updates);
        self::assertSame([['mgd_ai_admin_event', ['event_code' => 'display_cache_invalidation_failed']]], $logger->records);
    }

    #[Test]
    public function vollstaendiger_customlink_zyklus_speichert_den_display_post_genau_einmal(): void
    {
        $db = new DisplayEntryDatabase();
        $logger = new DisplayEntryLogger();
        $this->bereiteKontextVor(new AdminAccount(['PLUGIN_DETAIL_VIEW_17']), $db, $logger);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = [];
        $_POST = $this->gueltigerPost();

        \JTL\Smarty\JTLSmarty::$testFetchOutput = '<section>Neutraler Lesetab</section>';
        try {
            $outputs = [];
            foreach ([['assets.php', 7], ['philosophy.php', 8], ['display.php', 9], ['impressum.php', 10]] as [$file, $menuId]) {
                $outputs[$file] = $this->fuehreCustomlinkAus($file, $menuId);
            }
        } finally {
            \JTL\Smarty\JTLSmarty::$testFetchOutput = '';
        }

        self::assertCount(7, $db->updates);
        self::assertSame([], $logger->records);
        foreach ($outputs as $file => $output) {
            self::assertNotSame('', $output, $file . ' muss nach dem Display-POST neutral lesbar bleiben.');
        }
    }

    #[Test]
    public function vollstaendiger_customlink_get_ohne_route_rendert_alle_gueltigen_tabs_ohne_seiteneffekte(): void
    {
        $db = new DisplayEntryDatabase();
        $logger = new DisplayEntryLogger();
        $this->bereiteKontextVor(new AdminAccount(['PLUGIN_DETAIL_VIEW_17']), $db, $logger);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = [];
        $_POST = [];

        \JTL\Smarty\JTLSmarty::$testFetchOutput = '<section>Neutraler Lesetab</section>';
        try {
            $outputs = [];
            foreach ([['assets.php', 7], ['philosophy.php', 8], ['display.php', 9], ['impressum.php', 10]] as [$file, $menuId]) {
                $outputs[$file] = $this->fuehreCustomlinkAus($file, $menuId);
            }
        } finally {
            \JTL\Smarty\JTLSmarty::$testFetchOutput = '';
        }

        self::assertSame([], $db->updates);
        self::assertSame([], $logger->records);
        foreach ($outputs as $file => $output) {
            self::assertNotSame('', $output, $file . ' muss im normalen JTL-GET lesbar sein.');
        }
    }

    /**
     * @param array<string, string> $overrides
     * @return array<string, string>
     */
    private function gueltigerPost(array $overrides = []): array
    {
        return $overrides + [
            'blur' => '6',
            'border_radius' => '10',
            'csrf_token' => 'csrf',
            'font_size' => '18',
            'inner_padding' => '8',
            'kPlugin' => '17',
            'kPluginAdminMenu' => '9',
            'language' => 'de',
            'outer_margin' => '12',
            'transparency' => '20',
        ];
    }

    /** @return array<string, string> */
    private function aktiveGetRoute(int $menuId): array
    {
        return ['kPlugin' => '17', 'kPluginAdminMenu' => (string) $menuId];
    }

    private function bereiteKontextVor(
        AdminAccount $account,
        DisplayEntryDatabase $db,
        DisplayEntryLogger $logger,
        ?JTLCacheInterface $cache = null,
    ): void {
        if (!defined('PFAD_ROOT')) {
            define('PFAD_ROOT', dirname(__DIR__, 3) . '/');
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_id('mgd-display-entry-point-test');
            session_start();
        }
        $_SESSION = ['jtl_token' => 'csrf'];
        Form::$validToken = 'csrf';
        http_response_code(200);
        Shop::$container = new DisplayEntryContainer($account, $db, $logger, $cache);
    }

    private function fuehreEinstiegAus(int $menuId = 9): string
    {
        return $this->fuehreCustomlinkAus('display.php', $menuId);
    }

    private function fuehreCustomlinkAus(string $file, int $menuId): string
    {
        $oPlugin = new DisplayEntryPlugin();
        $menu = (object) ['kPluginAdminMenu' => $menuId];
        ob_start();
        include dirname(__DIR__, 3) . '/plugin/MGD_AI_Kennzeichnung/adminmenu/' . $file;

        return (string) ob_get_clean();
    }
}

/** Kapselt die für den echten Einstieg erforderlichen JTL-Containerdienste. */
final class DisplayEntryContainer implements DefaultServicesInterface
{
    private readonly JTLCacheInterface $cache;

    public function __construct(
        private readonly AdminAccount $account,
        private readonly DisplayEntryDatabase $db,
        private readonly DisplayEntryLogger $logger,
        ?JTLCacheInterface $cache = null,
    ) {
        $this->cache = $cache ?? new JTLCache();
    }

    public function getDB(): DbInterface
    {
        return $this->db;
    }

    public function getCache(): JTLCacheInterface
    {
        return $this->cache;
    }

    public function getAdminAccount(): AdminAccount
    {
        return $this->account;
    }

    public function getLogService(): LoggerInterface
    {
        return $this->logger;
    }
}

/** Stellt ausschließlich den für die Anzeigeoptionen erforderlichen Plugin-Kontext bereit. */
final class DisplayEntryPlugin implements PluginInterface
{
    /** @var array<string, string> */
    private const VALUES = [
        'language' => 'auto', 'font_size' => '12', 'outer_margin' => '8', 'inner_padding' => '6',
        'border_radius' => '4', 'blur' => '0', 'transparency' => '8',
        'update_notices' => 'N',
    ];

    public function getID(): int
    {
        return 17;
    }

    public function getPaths(): Paths
    {
        return new Paths('/plugin/17/adminmenu/');
    }

    public function getAdminMenu(): AdminMenu
    {
        return new AdminMenu([7 => 'assets.php', 8 => 'philosophy.php', 9 => 'display.php', 10 => 'impressum.php']);
    }

    public function getConfig(): Config
    {
        return new Config(self::VALUES);
    }
}

/** Bildet genau die Sperr-, Update- und Transaktionsaufrufe des Anzeigeadapters ab. */
final class DisplayEntryDatabase implements DbInterface
{
    private readonly TransactionStatePdo $pdo;
    private readonly TransactionalDatabaseFake $assetDatabase;
    /** @var array<string, string> */
    public array $values = [];
    /** @var list<array<string, mixed>> */
    public array $updates = [];
    public bool $throwOnUpdate = false;

    public function __construct()
    {
        $this->pdo = new TransactionStatePdo();
        $this->assetDatabase = new TransactionalDatabaseFake();
        $this->assetDatabase->setMarker('xplugin_mgd_ai_asset', \Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\SchemaOwnershipGuard::OWNERSHIP_MARKER);
        $this->assetDatabase->setMarker('xplugin_mgd_ai_usage', \Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\SchemaOwnershipGuard::OWNERSHIP_MARKER);
        $this->assetDatabase->setMarker('xplugin_mgd_ai_philosophy', \Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\SchemaOwnershipGuard::OWNERSHIP_MARKER);
    }

    public function getPDO(): \PDO
    {
        return $this->pdo;
    }

    public function getSingleObject(string $stmt, array $params = []): ?\stdClass
    {
        if (str_contains($stmt, 'INFORMATION_SCHEMA') || str_contains($stmt, 'xplugin_mgd_ai_asset')) {
            return $this->assetDatabase->getSingleObject($stmt, $params);
        }

        return null;
    }

    public function getObjects(string $stmt, array $params = []): array
    {
        if (!str_contains($stmt, 'tplugineinstellungen')) {
            return $this->assetDatabase->getObjects($stmt, $params);
        }

        return [(object) ['cWert' => 'vorhanden']];
    }
    public function getAffectedRows(string $stmt, array $params = []): int
    {
        if ($this->throwOnUpdate) {
            throw new \RuntimeException('Technischer Datenbankfehler.');
        }
        $this->updates[] = $params;
        $name = $params['name'] ?? null;
        $value = $params['value'] ?? null;
        if (is_string($name) && is_string($value)) {
            $this->values[$name] = $value;
        }

        return 1;
    }

    public function beginTransaction(): bool
    {
        $this->pdo->transactionActive = true;

        return true;
    }

    public function commit(): bool
    {
        $this->pdo->transactionActive = false;

        return true;
    }

    public function rollback(): bool
    {
        $this->pdo->transactionActive = false;

        return true;
    }
}

/** Zeichnet technische Logs auf, ohne Testdaten in die Meldung einzubauen. */
final class DisplayEntryLogger extends AbstractLogger
{
    /** @var list<array{string, array<mixed>}> */
    public array $records = [];

    public function log(mixed $level, string|\Stringable $message, array $context = []): void
    {
        if ($level === 'warning' && is_string($message)) {
            $this->records[] = [$message, $context];
        }
    }
}

/** Simuliert ausschließlich das technisch fehlgeschlagene Invalidieren nach einem erfolgreichen Commit. */
final class DisplayEntryFailingCache implements JTLCacheInterface
{
    public function flushTags(array $tags): int
    {
        throw new \RuntimeException('Technischer Cachefehler.');
    }
}
