<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

require_once __DIR__ . '/../../Stubs/JtlPluginStubs.php';
require_once __DIR__ . '/../../Stubs/JtlDatabaseStubs.php';
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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlAuthorizationAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlCsrfAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlDisplayConfigAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\AccessDeniedException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\CsrfException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\DisplayConfigCommittedException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlHttpRequestAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlSessionContext;
use RuntimeException;
use stdClass;
use Throwable;
use Tests\Support\TransactionStatePdo;

use const CACHING_GROUP_PLUGIN;

final class JtlRuntimeAdapterTest extends TestCase
{
    #[Test]
    public function ausgeloggter_oder_unvollstaendig_zwei_faktor_bestaetigter_admin_wird_vor_rechtepruefung_abgewiesen(): void
    {
        $account = new AdminAccount(['PLUGIN_DETAIL_VIEW_17'], 4, false);
        $adapter = new JtlAuthorizationAdapter($account, 17, 'session-a');

        $this->expectException(AccessDeniedException::class);
        try {
            $adapter->assertCanManageAssets();
        } finally {
            self::assertSame(0, $account->permissionCalls);
        }
    }

    #[Test]
    public function eingeloggter_admin_benoetigt_zusaetzlich_das_passende_pluginrecht(): void
    {
        $denied = new AdminAccount([], 4, true);
        try {
            (new JtlAuthorizationAdapter($denied, 17, 'session-a'))->assertCanManageAssets();
            self::fail('Ein Login ohne Pluginrecht muss abgewiesen werden.');
        } catch (AccessDeniedException) {
            self::assertGreaterThan(0, $denied->permissionCalls);
        }

        $allowed = new AdminAccount(['PLUGIN_DETAIL_VIEW_17'], 4, true);
        (new JtlAuthorizationAdapter($allowed, 17, 'session-a'))->assertCanManageAssets();
        self::assertGreaterThan(0, $allowed->permissionCalls);
    }

    #[Test]
    public function berechtigung_nutzt_exakt_jtls_pluginspezifisches_recht(): void
    {
        $allowed = new JtlAuthorizationAdapter(new AdminAccount(['PLUGIN_DETAIL_VIEW_17'], 4), 17, 'session-a');
        $allowed->assertCanManageAssets();

        $this->expectException(AccessDeniedException::class);
        (new JtlAuthorizationAdapter(new AdminAccount(['PLUGIN_DETAIL_VIEW_18'], 4), 17, 'session-a'))
            ->assertCanManageAssets();
    }

    #[Test]
    public function subjektbindung_ist_pseudonym_und_an_admin_und_session_gebunden(): void
    {
        $one = new JtlAuthorizationAdapter(new AdminAccount([], 4), 17, 'session-a');
        $two = new JtlAuthorizationAdapter(new AdminAccount([], 4), 17, 'session-b');

        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $one->subjectKey());
        self::assertNotSame($one->subjectKey(), $two->subjectKey());
        self::assertStringNotContainsString('session-a', $one->subjectKey());
    }

    #[Test]
    public function csrf_adapter_nutzt_jtls_explizite_tokenpruefung_und_liefert_sessiontoken(): void
    {
        Form::$validToken = 'sicher';
        $session = ['jtl_token' => 'sicher'];
        $adapter = new JtlCsrfAdapter($session);

        $adapter->assertValid('sicher');
        self::assertSame('sicher', $adapter->token());

        $this->expectException(CsrfException::class);
        $adapter->assertValid('falsch');
    }

    #[Test]
    public function http_adapter_entfernt_nur_exakt_passende_feste_jtl_route_aus_dem_fachpayload(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = [];
        $_POST = [
            'kPlugin' => '17',
            'kPluginAdminMenu' => '9',
            'action' => 'scan',
            'csrf_token' => 'csrf',
        ];

        $request = (new JtlHttpRequestAdapter())->capture(17, 9);

        self::assertSame(['action' => 'scan', 'csrf_token' => 'csrf'], $request->post);
    }

    #[Test]
    public function manipulierte_oder_nicht_kanonische_jtl_route_wird_vor_dem_controller_abgelehnt(): void
    {
        foreach (['01', '+17', ' 17', '1e1', '18', '0', '999999999999999999999'] as $routeId) {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET = ['kPlugin' => $routeId, 'kPluginAdminMenu' => '9', 'view' => 'list'];
            $_POST = [];

            try {
                (new JtlHttpRequestAdapter())->capture(17, 9);
                self::fail('Eine offene oder nicht kanonische JTL-Route muss abgewiesen werden.');
            } catch (ValidationException) {
                self::addToAssertionCount(1);
            }
        }
    }

    #[Test]
    public function aktiver_request_muss_beide_jtl_routenwerte_enthalten(): void
    {
        foreach ([[], ['kPlugin' => '17'], ['kPluginAdminMenu' => '9']] as $route) {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_GET = [];
            $_POST = $route + ['action' => 'scan', 'csrf_token' => 'csrf'];

            try {
                (new JtlHttpRequestAdapter())->capture(17, 9);
                self::fail('Eine unvollständige JTL-Route muss abgewiesen werden.');
            } catch (ValidationException) {
                self::addToAssertionCount(1);
            }
        }
    }

    #[Test]
    public function initialer_get_darf_ohne_clientroute_starten_und_einzelne_passende_route_mitbringen(): void
    {
        foreach ([[], ['kPlugin' => '17'], ['kPluginAdminMenu' => '9']] as $route) {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET = $route + ['view' => 'list'];
            $_POST = [];

            $request = (new JtlHttpRequestAdapter())->capture(17, 9);
            self::assertSame(['view' => 'list'], $request->query);
        }
    }

    #[Test]
    public function jeder_vorhandene_get_routenwert_wird_auch_ohne_den_anderen_exakt_validiert(): void
    {
        foreach ([['kPlugin' => '18'], ['kPluginAdminMenu' => '10'], ['kPlugin' => '017']] as $route) {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET = $route + ['view' => 'list'];
            $_POST = [];

            try {
                (new JtlHttpRequestAdapter())->capture(17, 9);
                self::fail('Jeder vorhandene GET-Routenwert muss exakt zum JTL-Kontext passen.');
            } catch (ValidationException) {
                self::addToAssertionCount(1);
            }
        }
    }

    #[Test]
    public function session_context_bewahrt_bestehende_referenzen_und_mutationen_in_beide_richtungen(): void
    {
        $_SESSION = ['vorhanden' => 'ja'];
        $bereitsGehalten = &$_SESSION;
        $context = &JtlSessionContext::current();

        $context['vom_context'] = 'sichtbar';
        $bereitsGehalten['von_referenz'] = 'sichtbar';
        self::assertSame([
            'vorhanden' => 'ja',
            'vom_context' => 'sichtbar',
            'von_referenz' => 'sichtbar',
        ], $context);
        self::assertSame($context, $bereitsGehalten);
    }

    #[Test]
    public function session_context_initialisiert_nur_einen_ungueltigen_sessionwert(): void
    {
        $_SESSION = 'ungueltig';

        $context = &JtlSessionContext::current();

        self::assertSame([], $context);
        self::assertSame([], $_SESSION);
    }

    #[Test]
    public function numerische_http_hauptschluessel_werden_nicht_stillschweigend_verworfen(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = [0 => ['zu' => ['tief' => ['und' => ['gross' => str_repeat('x', 1000)]]]]];
        $_POST = [];

        $this->expectException(ValidationException::class);
        (new JtlHttpRequestAdapter())->capture(17, 9);
    }

    #[Test]
    public function http_adapter_begrenzt_verschachtelung_bevor_der_fachnormalizer_laeuft(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['view' => 'list', 'nested' => ['a' => ['b' => ['c' => ['d' => 'zu tief']]]]];
        $_POST = [];

        $this->expectException(ValidationException::class);
        (new JtlHttpRequestAdapter())->capture(17, 9);
    }

    #[Test]
    public function jtl_konfigurationsadapter_laesst_nur_die_feste_anzeigepositivliste_und_speichert_atomar(): void
    {
        $db = new JtlDisplayConfigDatabaseFake();
        $cache = new JTLCache();
        $adapter = new JtlDisplayConfigAdapter($db, new DisplayConfigPluginFake(17, self::displayValues()), $cache);

        self::assertSame(self::displayValues(), $adapter->load());
        $adapter->save(self::displayValues());

        self::assertSame(1, $db->begins);
        self::assertSame(1, $db->commits);
        self::assertSame(0, $db->rollbacks);
        self::assertCount(7, $db->lockedRows);
        self::assertCount(7, $db->updates);
        self::assertSame(['lock', 'lock', 'lock', 'lock', 'lock', 'lock', 'lock'], array_slice($db->events, 0, 7));
        self::assertStringContainsString('FOR UPDATE', $db->lockedRows[0]['sql']);
        self::assertSame([
            'value' => 'de',
            'plugin_id' => 17,
            'name' => 'language',
        ], $db->updates[0]['params']);
        self::assertSame([[
            CACHING_GROUP_PLUGIN,
            CACHING_GROUP_PLUGIN . '_17',
        ]], $cache->flushedTags);
    }

    #[Test]
    public function jtl_konfigurationsadapter_weist_unvollstaendige_oder_unbekannte_speicherwerte_vor_der_transaktion_ab(): void
    {
        $db = new JtlDisplayConfigDatabaseFake();
        $adapter = new JtlDisplayConfigAdapter($db, new DisplayConfigPluginFake(17, self::displayValues()), new JTLCache());
        $values = self::displayValues();
        unset($values['blur']);

        $this->expectException(RuntimeException::class);
        try {
            $adapter->save($values + ['unbekannt' => 'wert']);
        } finally {
            self::assertSame(0, $db->begins);
            self::assertSame([], $db->updates);
        }
    }

    #[Test]
    public function jtl_konfigurationsadapter_prueft_alle_pluginoptionen_vor_dem_ersten_update(): void
    {
        $db = new JtlDisplayConfigDatabaseFake();
        $config = self::displayValues();
        unset($config['transparency']);
        $adapter = new JtlDisplayConfigAdapter($db, new DisplayConfigPluginFake(17, $config), new JTLCache());

        $this->expectException(RuntimeException::class);
        try {
            $adapter->save(self::displayValues());
        } finally {
            self::assertSame(0, $db->begins);
            self::assertSame([], $db->updates);
        }
    }

    #[Test]
    public function jtl_konfigurationsadapter_rollt_bei_mehrdeutigem_update_zurueck_und_leert_keinen_cache(): void
    {
        $db = new JtlDisplayConfigDatabaseFake();
        $db->affectedRows = 2;
        $cache = new JTLCache();
        $adapter = new JtlDisplayConfigAdapter($db, new DisplayConfigPluginFake(17, self::displayValues()), $cache);

        $this->expectException(RuntimeException::class);
        try {
            $adapter->save(self::displayValues());
        } finally {
            self::assertSame(1, $db->rollbacks);
            self::assertSame([], $cache->flushedTags);
        }
    }

    #[Test]
    public function jtl_konfigurationsadapter_rollt_bei_fehlendem_commit_zurueck_und_leert_keinen_cache(): void
    {
        $db = new JtlDisplayConfigDatabaseFake();
        $db->commitSucceeds = false;
        $cache = new JTLCache();
        $adapter = new JtlDisplayConfigAdapter($db, new DisplayConfigPluginFake(17, self::displayValues()), $cache);

        $this->expectException(RuntimeException::class);
        try {
            $adapter->save(self::displayValues());
        } finally {
            self::assertSame(1, $db->rollbacks);
            self::assertSame([], $cache->flushedTags);
        }
    }

    #[Test]
    public function jtl_konfigurationsadapter_startet_nicht_in_einer_fremden_transaktion(): void
    {
        $db = new JtlDisplayConfigDatabaseFake();
        $db->pdo->transactionActive = true;
        $adapter = new JtlDisplayConfigAdapter($db, new DisplayConfigPluginFake(17, self::displayValues()), new JTLCache());

        $this->expectException(RuntimeException::class);
        try {
            $adapter->save(self::displayValues());
        } finally {
            self::assertSame(0, $db->begins);
            self::assertSame(0, $db->rollbacks);
        }
    }

    #[Test]
    public function jtl_konfigurationsadapter_rollt_bei_fehlender_datenbankoption_vor_jedem_update_zurueck(): void
    {
        $db = new JtlDisplayConfigDatabaseFake();
        $db->rowsByName['blur'] = [];
        $cache = new JTLCache();
        $adapter = new JtlDisplayConfigAdapter($db, new DisplayConfigPluginFake(17, self::displayValues()), $cache);

        $this->expectException(RuntimeException::class);
        try {
            $adapter->save(self::displayValues());
        } finally {
            self::assertSame(1, $db->rollbacks);
            self::assertSame([], $db->updates);
            self::assertSame([], $cache->flushedTags);
        }
    }

    #[Test]
    public function jtl_konfigurationsadapter_weist_duplikate_unabhaengig_vom_bisherigen_wert_ab(): void
    {
        foreach ([
            'gleiche Werte' => [(object) ['cWert' => '18'], (object) ['cWert' => '18']],
            'unterschiedliche Werte' => [(object) ['cWert' => '18'], (object) ['cWert' => '99']],
        ] as $description => $rows) {
            $db = new JtlDisplayConfigDatabaseFake();
            $db->rowsByName['font_size'] = $rows;
            $adapter = new JtlDisplayConfigAdapter($db, new DisplayConfigPluginFake(17, self::displayValues()), new JTLCache());

            try {
                $adapter->save(self::displayValues());
                self::fail(sprintf('Doppelte Optionen mit %s müssen abgewiesen werden.', $description));
            } catch (RuntimeException) {
                self::assertSame(1, $db->rollbacks);
                self::assertSame([], $db->updates);
            }
        }
    }

    #[Test]
    public function jtl_konfigurationsadapter_akzeptiert_unveraenderte_werte_mit_null_betroffenen_zeilen(): void
    {
        $db = new JtlDisplayConfigDatabaseFake();
        $db->affectedRows = 0;
        foreach (self::displayValues() as $name => $value) {
            $db->rowsByName[$name] = [(object) ['cWert' => $value]];
        }
        $adapter = new JtlDisplayConfigAdapter($db, new DisplayConfigPluginFake(17, self::displayValues()), new JTLCache());

        $adapter->save(self::displayValues());

        self::assertSame(1, $db->commits);
        self::assertSame(0, $db->rollbacks);
        self::assertCount(7, $db->updates);
    }

    #[Test]
    public function jtl_konfigurationsadapter_meldet_fehlgeschlagenen_rollback_mit_dem_urspruenglichen_fehler(): void
    {
        $db = new JtlDisplayConfigDatabaseFake();
        $db->rowsByName['language'] = [];
        $db->rollbackSucceeds = false;
        $adapter = new JtlDisplayConfigAdapter($db, new DisplayConfigPluginFake(17, self::displayValues()), new JTLCache());

        try {
            $adapter->save(self::displayValues());
            self::fail('Ein fehlgeschlagener Rollback muss sichtbar sein.');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('zurückgenommen', $error->getMessage());
            self::assertInstanceOf(RuntimeException::class, $error->getPrevious());
            self::assertStringNotContainsString('18', $error->getMessage());
        }
    }

    #[Test]
    public function jtl_konfigurationsadapter_behaelt_den_urspruenglichen_fehler_bei_geworfenem_rollback(): void
    {
        $db = new JtlDisplayConfigDatabaseFake();
        $db->rowsByName['language'] = [];
        $db->rollbackFailure = new RuntimeException('Datenbank-Rollbackfehler.');
        $adapter = new JtlDisplayConfigAdapter($db, new DisplayConfigPluginFake(17, self::displayValues()), new JTLCache());

        try {
            $adapter->save(self::displayValues());
            self::fail('Ein geworfener Rollbackfehler muss sichtbar sein.');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('zurückgenommen', $error->getMessage());
            self::assertInstanceOf(RuntimeException::class, $error->getPrevious());
            self::assertStringNotContainsString('Datenbank-Rollbackfehler', $error->getMessage());
        }
    }

    #[Test]
    public function jtl_konfigurationsadapter_meldet_cachefehler_nach_commit_ohne_rollback(): void
    {
        $db = new JtlDisplayConfigDatabaseFake();
        $cache = new ThrowingDisplayConfigCache();
        $adapter = new JtlDisplayConfigAdapter($db, new DisplayConfigPluginFake(17, self::displayValues()), $cache);

        try {
            $adapter->save(self::displayValues());
            self::fail('Ein Cachefehler nach Commit muss ausdrücklich gemeldet werden.');
        } catch (DisplayConfigCommittedException $error) {
            self::assertSame(1, $db->commits);
            self::assertSame(0, $db->rollbacks);
            self::assertInstanceOf(RuntimeException::class, $error->getPrevious());
            self::assertStringNotContainsString('18', $error->getMessage());
        }
    }

    /** @return array<string, string> */
    private static function displayValues(): array
    {
        return [
            'language' => 'de',
            'font_size' => '18',
            'outer_margin' => '12',
            'inner_padding' => '8',
            'border_radius' => '10',
            'blur' => '6',
            'transparency' => '20',
        ];
    }
}

/** Ersetzt das minimale Pluginobjekt für die isolierte Adapterprüfung. */
final class DisplayConfigPluginFake implements PluginInterface
{
    /** @param array<string, mixed> $config */
    public function __construct(private readonly int $id, private readonly array $config) {}

    public function getID(): int
    {
        return $this->id;
    }

    public function getPaths(): Paths
    {
        return new Paths();
    }

    public function getAdminMenu(): AdminMenu
    {
        return new AdminMenu();
    }

    public function getConfig(): Config
    {
        return new Config($this->config);
    }
}

/** Simuliert nur die für atomare Pluginoptionen nötigen JTL-Datenbankaufrufe. */
final class JtlDisplayConfigDatabaseFake implements DbInterface
{
    public readonly TransactionStatePdo $pdo;
    public int $begins = 0;
    public int $commits = 0;
    public int $rollbacks = 0;
    public int $affectedRows = 1;
    public bool $commitSucceeds = true;
    public bool $rollbackSucceeds = true;
    public ?Throwable $rollbackFailure = null;

    /** @var array<string, list<stdClass>> */
    public array $rowsByName = [];

    /** @var list<array{sql: string, params: array<string, mixed>}> */
    public array $updates = [];
    /** @var list<array{sql: string, params: array<string, mixed>}> */
    public array $lockedRows = [];
    /** @var list<'lock'|'update'> */
    public array $events = [];

    public function __construct()
    {
        $this->pdo = new TransactionStatePdo();
    }

    public function getPDO(): \PDO
    {
        return $this->pdo;
    }

    /** @return list<stdClass> */
    public function getObjects(string $stmt, array $params = []): array
    {
        $this->lockedRows[] = ['sql' => $stmt, 'params' => $params];
        $this->events[] = 'lock';
        $name = $params['name'] ?? null;
        if (!is_string($name)) {
            throw new RuntimeException('Der Optionsname fehlt im Testbinding.');
        }

        return $this->rowsByName[$name] ?? [(object) ['cWert' => 'bestehend']];
    }

    public function getSingleObject(string $stmt, array $params = []): ?stdClass
    {
        return null;
    }

    public function getAffectedRows(string $stmt, array $params = []): int
    {
        $this->updates[] = ['sql' => $stmt, 'params' => $params];
        $this->events[] = 'update';

        return $this->affectedRows;
    }

    public function beginTransaction(): bool
    {
        ++$this->begins;
        $this->pdo->transactionActive = true;

        return true;
    }

    public function commit(): bool
    {
        ++$this->commits;
        if ($this->commitSucceeds) {
            $this->pdo->transactionActive = false;
        }

        return $this->commitSucceeds;
    }

    public function rollback(): bool
    {
        ++$this->rollbacks;
        $this->pdo->transactionActive = false;

        if ($this->rollbackFailure !== null) {
            throw $this->rollbackFailure;
        }

        return $this->rollbackSucceeds;
    }
}

/** Erzwingt einen Cachefehler, ohne Datenbank- oder Eingabewerte preiszugeben. */
final class ThrowingDisplayConfigCache implements JTLCacheInterface
{
    /** @param list<string> $tags */
    public function flushTags(array $tags): int
    {
        throw new RuntimeException('Interner Cachefehler.');
    }
}
