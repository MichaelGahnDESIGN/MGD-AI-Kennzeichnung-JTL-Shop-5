<?php

declare(strict_types=1);

// Smoke Test Updates Bereich, läuft ohne JTL und ohne Netz: php tests/update-smoke.php

use Plugin\MGD_AI_Kennzeichnung\Admin\Update\{PackageDelivery, PluginVersionReader, UpdateDataDirectory, UpdatesPageFactory};
use Plugin\MGD_AI_Kennzeichnung\UpdateClient\{ClientConfig, UpdateClient};
use Plugin\MGD_AI_Kennzeichnung\UpdateClient\Admin\UpdatesPage;
use Plugin\MGD_AI_Kennzeichnung\UpdateClient\Crypto\KeyRing;
use Plugin\MGD_AI_Kennzeichnung\UpdateClient\Http\{HttpClient, HttpResponse};
use Plugin\MGD_AI_Kennzeichnung\UpdateClient\License\NullLicenseProvider;
use Plugin\MGD_AI_Kennzeichnung\UpdateClient\Store\JsonFileStateStore;

$pluginDir = dirname(__DIR__) . '/plugin/MGD_AI_Kennzeichnung';
spl_autoload_register(static function (string $class) use ($pluginDir): void {
    $prefix = 'Plugin\\MGD_AI_Kennzeichnung\\';
    if (str_starts_with($class, $prefix)) {
        $file = $pluginDir . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

// Fake HTTP: zählt Aufrufe, liefert immer 404
final class CountingHttp implements HttpClient
{
    public int $calls = 0;

    public function request(string $method, string $url, array $headers = [], ?string $body = null, int $maxBytes = 1048576): HttpResponse
    {
        ++$this->calls;

        return new HttpResponse(404);
    }

    /** @param array<string,string> $headers */
    public function download(string $url, array $headers, string $targetFile, int $maxBytes): HttpResponse
    {
        ++$this->calls;

        return new HttpResponse(404);
    }
}

// Zähler (Fehler, Gesamt)
final class Counter
{
    public static int $failed = 0;
    public static int $total = 0;
}
function check(string $name, bool $ok): void
{
    ++Counter::$total;
    if (!$ok) {
        ++Counter::$failed;
    }
    echo ($ok ? 'OK   ' : 'FAIL ') . $name . "\n";
}

function tmpDir(): string
{
    $d = sys_get_temp_dir() . '/mgd-upd-smoke-' . bin2hex(random_bytes(4));
    mkdir($d, 0700, true);

    return $d;
}

/** @return array{0:UpdatesPage,1:CountingHttp,2:JsonFileStateStore} */
function makePage(string $dir, bool $rights, bool $csrf, bool $keys = true, int $now = 1_800_000_000): array
{
    $ring = new KeyRing();
    if ($keys) {
        // Dummy Key, nur für den Test (kein echter Schlüssel)
        $ring->add('test-root-1', 'root', str_repeat('a', 32));
    }
    $cfg = new ClientConfig('mgd-ai-kennzeichnung', 'free', '1.3.9', 'shop.example.test', $ring, $dir, '5.7.2');
    $http = new CountingHttp();
    $store = new JsonFileStateStore($dir . '/state.json');
    $client = new UpdateClient($cfg, $http, new NullLicenseProvider(), $store, static fn(): int => $now);
    $page = new UpdatesPage(
        $client,
        $store,
        static fn(): bool => $csrf,
        static fn(): bool => $rights,
        '<input type="hidden" name="jtl_token" value="t">',
        'MGD AI Kennzeichnung',
        '1.3.9',
        static fn(): int => $now,
    );

    return [$page, $http, $store];
}

// Version aus info.xml
$infoVersion = PluginVersionReader::read($pluginDir . '/info.xml');
check('Version kommt aus info.xml (' . $infoVersion . ')', preg_match('/^\d+\.\d+\.\d+$/', $infoVersion) === 1);

// Produktname Konstante
check('Produktname ist mgd-ai-kennzeichnung', strcmp(UpdatesPageFactory::PRODUCT . '/' . UpdatesPageFactory::EDITION, 'mgd-ai-kennzeichnung/free') === 0);

// ohne Recht: kein Zugriff, kein Netz, kein Schreiben
$d = tmpDir();
[$page, $http, $store] = makePage($d, false, true);
$r = $page->handle('POST', ['mgd_update_action' => 'check_now']);
check('ohne Recht: Meldung Berechtigung', str_contains($r['html'], 'Berechtigung'));
check('ohne Recht: kein Formular', !str_contains($r['html'], '<form'));
check('ohne Recht: kein Netzabruf', $http->calls === 0);
check('ohne Recht: nichts gespeichert', !is_file($d . '/state.json'));
check('ohne Recht: kein Download', $r['deliver'] === null);

// ohne CSRF: keine Aktion
$d = tmpDir();
[$page, $http] = makePage($d, true, false);
foreach (['check_now', 'save_settings', 'download', 'deliver'] as $act) {
    $r = $page->handle('POST', ['mgd_update_action' => $act, 'confirm' => '1', 'checks_enabled' => '1']);
    check("ohne CSRF: Aktion $act geblockt", str_contains($r['html'], 'Sicherheits-Token') && $r['deliver'] === null);
}
check('ohne CSRF: kein Netzabruf', $http->calls === 0);
check('ohne CSRF: nichts gespeichert', !is_file($d . '/state.json'));

// GET ohne Einschalten: kein Netz
$d = tmpDir();
[$page, $http] = makePage($d, true, true);
$r = $page->handle('GET', []);
check('GET ohne Einschalten: kein Netzabruf', $http->calls === 0);
check('GET zeigt installierte Version', str_contains($r['html'], '<strong>1.3.9</strong>'));
check('GET: Opt-in Checkbox nicht angehakt', !str_contains($r['html'], 'checked'));
check('GET: CSRF Feld im Formular', str_contains($r['html'], 'name="jtl_token"'));

// Einschalten per POST: speichert nur, ruft nichts ab
$r = $page->handle('POST', ['mgd_update_action' => 'save_settings', 'checks_enabled' => '1']);
check('Einschalten speichert ohne Abruf', $http->calls === 0 && str_contains($r['html'], 'Einstellung gespeichert'));

// eingeschaltet und GET: Abruf erlaubt (Opt-in)
$r = $page->handle('GET', []);
check('nach Einschalten: Abruf bei GET', $http->calls === 1);

// Klick auf Jetzt prüfen = ein Abruf
$d = tmpDir();
[$page, $http] = makePage($d, true, true);
$page->handle('POST', ['mgd_update_action' => 'check_now']);
check('Jetzt prüfen: genau ein Abruf', $http->calls === 1);

// Download ohne Bestätigung: kein Netz
$d = tmpDir();
[$page, $http] = makePage($d, true, true);
$r = $page->handle('POST', ['mgd_update_action' => 'download']);
check('Download ohne Bestätigung: kein Netz, Hinweis', $http->calls === 0 && str_contains($r['html'], 'Bestätigung'));

// Anzeige escaped
$d = tmpDir();
[$page, , $store] = makePage($d, true, true);
$evil = '<script>alert(1)</script>"><img src=x onerror=alert(2)>';
$store->save(['last_check' => 1_800_000_000, 'last_result' => [
    'status' => 'update_available', 'message' => $evil, 'version' => $evil, 'changelog' => $evil, 'security' => true,
]]);
$r = $page->handle('GET', []);
check('Anzeige: kein rohes script Tag', !str_contains($r['html'], '<script>') && !str_contains($r['html'], '<img'));
check('Anzeige: escaped vorhanden', str_contains($r['html'], '&lt;script&gt;'));

// echte Verdrahtung: leere Root Keys, kein Netz möglich
$compiled = tmpDir() . '/templates_c/';
mkdir($compiled, 0700, true);
$dataDir = UpdateDataDirectory::prepare($compiled);
$rk = json_decode((string) file_get_contents($pluginDir . '/update/root-keys.json'), true);
check('root-keys.json ohne Schlüssel (Blocker B1)', is_array($rk) && ($rk['keys'] ?? null) === []);
check('root-keys.json ohne Geheimnisse', !preg_match('/secret|private|token|seed/i', (string) file_get_contents($pluginDir . '/update/root-keys.json')));
$real = UpdatesPageFactory::create(
    $pluginDir,
    $dataDir,
    'https://shop.example.test/plugins/MGD_AI_Kennzeichnung/frontend/',
    '5.7.2',
    '<input type="hidden" name="jtl_token" value="t">',
    static fn(): bool => true,
    static fn(): bool => true,
);
// direkt über die Factory ohne Prüfsummen-Adresse, sonst ginge der Test ins Netz
$bare = \Plugin\MGD_AI_Kennzeichnung\UpdateClient\Factory::page($pluginDir . '/update/root-keys.json', $dataDir, UpdatesPageFactory::PRODUCT, UpdatesPageFactory::EDITION, UpdatesPageFactory::PLUGIN_NAME, $infoVersion, 'shop.example.test', '5.7.2', static fn(): bool => true, static fn(): bool => true, '');
$r = $bare->handle('POST', ['mgd_update_action' => 'check_now']);
check('ohne Schlüssel und ohne Prüfsummen-Adresse: nicht eingerichtet (B1)', str_contains($r['html'], 'noch nicht eingerichtet'));
// einfacher Modus verdrahtet, nur statisch geprüft
check('einfacher Modus: Adresse der Prüfsummen-Datei', str_contains((string) file_get_contents($pluginDir . '/Admin/Update/UpdatesPageFactory.php'), "SIMPLE_URL = 'https://theme.michael-gahn.de/plugin-updates/mgd-ai-kennzeichnung.json'"));
check('einfacher Modus: Factory gibt die Adresse weiter', str_contains((string) file_get_contents($pluginDir . '/Admin/Update/UpdatesPageFactory.php'), "['info.xml'],\n            self::SIMPLE_URL"));
// GET ohne Einschalten ruft nichts ab
$r = $real->handle('GET', []);
check('Verdrahtung: zeigt Plugin Version', str_contains($r['html'], '<strong>' . $infoVersion . '</strong>'));

// Datenordner gesperrt
check('Datenordner mit .htaccess Deny', str_contains((string) file_get_contents($dataDir . '/.htaccess'), 'Require all denied'));
check('Elternordner mit .htaccess', is_file(dirname($dataDir) . '/.htaccess'));
check('Datenordner liegt unter templates_c', str_starts_with($dataDir, rtrim($compiled, '/') . '/'));

// Erfolgsmeldung nennt nur was geprüft wurde
check('Meldung einfacher Modus ohne Signatur', !str_contains(UpdatesPage::verifiedText(new \Plugin\MGD_AI_Kennzeichnung\UpdateClient\UpdateResult('update_available', '', [], 'x', ['mode' => 'simple'])), 'Signatur'));
check('Meldung signierter Modus mit Signatur', str_contains(UpdatesPage::verifiedText(new \Plugin\MGD_AI_Kennzeichnung\UpdateClient\UpdateResult('update_available', '', [], 'x')), 'Signatur'));

// ZIP Auslieferung nur aus Quarantäne
$q = $dataDir . '/quarantine';
mkdir($q, 0700, true);
file_put_contents($q . '/ok.zip', 'PK');
file_put_contents($dataDir . '/outside.zip', 'PK');
file_put_contents($q . '/evil.php', 'x');
symlink($dataDir . '/outside.zip', $q . '/link.zip');
check('Delivery: Datei in Quarantäne ok', PackageDelivery::isDeliverable($q . '/ok.zip', $dataDir));
check('Delivery: Datei ausserhalb abgelehnt', !PackageDelivery::isDeliverable($dataDir . '/outside.zip', $dataDir));
check('Delivery: Traversal abgelehnt', !PackageDelivery::isDeliverable($q . '/../outside.zip', $dataDir));
check('Delivery: kein .php', !PackageDelivery::isDeliverable($q . '/evil.php', $dataDir));
check('Delivery: Symlink abgelehnt', !PackageDelivery::isDeliverable($q . '/link.zip', $dataDir));

echo "\n" . Counter::$total . " Prüfungen, " . Counter::$failed . " Fehler\n";
exit(Counter::$failed > 0 ? 1 : 0);
