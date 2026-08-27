# JTL-Impressum-Tab Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Das JTL-Shop-5-Plugin erhält einen sicheren, rein lesenden Backend-Tab mit den freigegebenen Hersteller- und Impressumsangaben.

**Architecture:** `info.xml` registriert einen neuen Customlink. Ein kleiner, geschützter PHP-Einstiegspunkt prüft JTL-Kontext, HTTP-Methode und Plugin-Berechtigung und rendert danach ein eigenes Smarty-Template; Datenbank, Formulare und externe Anfragen bleiben vollständig außen vor.

**Tech Stack:** PHP 8.1, JTL-Shop 5.7.2 Plugin-API, Smarty, PHPUnit 10, PHPStan, PHP-CS-Fixer, Bash/ZIP

---

### Task 1: Struktur- und Sicherheitsvertrag testgetrieben festlegen

**Files:**
- Create: `tests/Structure/ImpressumAdminContractTest.php`
- Modify: `plugin/MGD_AI_Kennzeichnung/info.xml`
- Create: `plugin/MGD_AI_Kennzeichnung/adminmenu/impressum.php`
- Create: `plugin/MGD_AI_Kennzeichnung/adminmenu/templates/impressum.tpl`

- [ ] **Step 1: Write the failing test**

Der neue Test prüft Menüposition, Dateistruktur, Pflichtangaben, sichere Links, direkten Zugriffsschutz sowie das Fehlen von Formularen, Datenbankzugriff und externen Ressourcen:

```php
<?php

declare(strict_types=1);

namespace Tests\Structure;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ImpressumAdminContractTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../plugin/MGD_AI_Kennzeichnung';

    #[Test]
    public function adminmenue_registriert_das_impressum_vor_den_einstellungen(): void
    {
        $xml = simplexml_load_file(self::ROOT . '/info.xml');
        self::assertNotFalse($xml);
        $links = $xml->xpath('/jtlshopplugin/Install/Adminmenu/*');
        self::assertIsArray($links);

        $menue = [];
        foreach ($links as $link) {
            $menue[(int) $link['sort']] = trim((string) $link->Name);
        }
        self::assertSame('Impressum', $menue[3] ?? null);
        self::assertSame('Einstellungen', $menue[4] ?? null);
        self::assertFileExists(self::ROOT . '/adminmenu/impressum.php');
        self::assertFileExists(self::ROOT . '/adminmenu/templates/impressum.tpl');
    }

    #[Test]
    public function template_zeigt_nur_freigegebene_geschaeftsangaben_ohne_datenerfassung(): void
    {
        $template = (string) file_get_contents(self::ROOT . '/adminmenu/templates/impressum.tpl');
        foreach (['§ 5 DDG', 'Michael Gahn DESIGN', 'Dr.-Theodor-Brugsch Str. 12',
            '+49 (0) 151 59156639', 'Anfrage@Michael-Gahn.de', '223/222/02451', 'DE288143343'] as $wert) {
            self::assertStringContainsString($wert, $template);
        }
        self::assertStringContainsString('href="tel:+4915159156639"', $template);
        self::assertStringContainsString('href="mailto:Anfrage@Michael-Gahn.de"', $template);
        self::assertStringNotContainsString('<form', strtolower($template));
        self::assertStringNotContainsString('<script', strtolower($template));
        self::assertDoesNotMatchRegularExpression('~(?:src|href)="https?://~i', $template);
    }

    #[Test]
    public function einstiegspunkt_bleibt_lesend_und_geschuetzt(): void
    {
        $php = (string) file_get_contents(self::ROOT . '/adminmenu/impressum.php');
        self::assertStringContainsString("defined('PFAD_ROOT')", $php);
        self::assertStringContainsString('assertCanManageAssets()', $php);
        self::assertStringContainsString("\$request->method !== 'GET'", $php);
        self::assertStringNotContainsString('getDB()', $php);
        self::assertStringNotContainsString('$_COOKIE', $php);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Structure/ImpressumAdminContractTest.php`

Expected: FAIL, weil Menüeintrag, Einstiegspunkt und Template noch fehlen.

- [ ] **Step 3: Implement the minimal protected endpoint and template**

`info.xml` erhält nach der AI-Philosophie:

```xml
<Customlink sort="3">
    <Name>Impressum</Name>
    <Filename>impressum.php</Filename>
</Customlink>
<Settingslink sort="4">
```

`adminmenu/impressum.php` prüft denselben JTL-Menü- und Berechtigungskontext wie die bestehenden Adminseiten:

```php
<?php

declare(strict_types=1);

use JTL\Plugin\PluginInterface;
use JTL\Shop;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlAuthorizationAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\Adapter\JtlHttpRequestAdapter;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\AccessDeniedException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;

if (!defined('PFAD_ROOT') || !isset($oPlugin) || !$oPlugin instanceof PluginInterface) {
    http_response_code(403);
    echo 'Das Impressum ist nur im JTL-Administrationsbereich verfügbar.';
    return;
}

$container = Shop::Container();
try {
    $sessionId = session_id();
    $adminMenuId = is_object($menu ?? null) ? ($menu->kPluginAdminMenu ?? null) : null;
    if (!is_string($sessionId) || $sessionId === ''
        || !is_int($adminMenuId) || $adminMenuId < 1
        || $oPlugin->getAdminMenu()->getItemByID($adminMenuId) === null
    ) {
        throw new ValidationException('Der JTL-Admin-Menükontext ist ungültig.');
    }

    $request = (new JtlHttpRequestAdapter())->capture($oPlugin->getID(), $adminMenuId);
    if ($request->method !== 'GET' || $request->query !== [] || $request->post !== []) {
        throw new ValidationException('Das Impressum unterstützt ausschließlich den lesenden Aufruf.');
    }

    (new JtlAuthorizationAdapter(
        $container->getAdminAccount(),
        $oPlugin->getID(),
        $sessionId,
    ))->assertCanManageAssets();

    echo Shop::Smarty()->fetch(__DIR__ . '/templates/impressum.tpl');
} catch (AccessDeniedException) {
    http_response_code(403);
    echo 'Sie besitzen keine Berechtigung für das Plugin-Impressum.';
} catch (ValidationException) {
    http_response_code(400);
    echo 'Das Plugin-Impressum konnte die Anfrage nicht sicher verarbeiten.';
} catch (Throwable) {
    http_response_code(500);
    $container->getLogService()->warning('mgd_ai_admin_event', ['event_code' => 'imprint_request_failed', 'count' => 0]);
    echo 'Das Plugin-Impressum konnte die Anfrage nicht abschließen.';
}
```

Das Template enthält ein semantisches `address`-Element, die freigegebenen Angaben und ausschließlich `tel:`- und `mailto:`-Links.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Structure/ImpressumAdminContractTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add tests/Structure/ImpressumAdminContractTest.php plugin/MGD_AI_Kennzeichnung/info.xml plugin/MGD_AI_Kennzeichnung/adminmenu/impressum.php plugin/MGD_AI_Kennzeichnung/adminmenu/templates/impressum.tpl
git commit -m "feat: ergänzt geschützten Impressum-Tab"
```

### Task 2: Dokumentation und Versionsvertrag auf 1.2.0 aktualisieren

**Files:**
- Modify: `tests/Structure/PluginContractTest.php`
- Modify: `tests/Structure/DocumentationAndReleaseTest.php`
- Modify: `tests/Unit/Service/DisplaySettingsTest.php`
- Modify: `plugin/MGD_AI_Kennzeichnung/info.xml`
- Modify: `scripts/build-release.sh`
- Modify: `scripts/README.md`
- Modify: `README.md`
- Modify: `README.en.md`
- Modify: `CHANGELOG.md`
- Modify: `Dokumentation/README.md`
- Modify: `Dokumentation/Versionen.md`
- Create: `Dokumentation/Impressum.md`
- Create: `Dokumentation/Release-1.2.0.md`
- Modify: `wiki/Home.md`
- Create: `wiki/Impressum.md`
- Modify: `wiki/_Sidebar.md`
- Modify: `wiki/_Footer.md`
- Modify: `wiki/Release-und-Rollback.md`
- Modify: `wiki/Fuer-Entwickler.md`

- [ ] **Step 1: Write failing version, package, and documentation assertions**

Die bestehenden Verträge werden zuerst auf `1.2.0`, `MGD_AI_Kennzeichnung-1.2.0.zip`, `wiki/Impressum.md` und die Begriffe `§ 5 DDG`, `keine Datenbank` sowie `nur im Administrationsbereich` umgestellt.

- [ ] **Step 2: Run affected tests and verify they fail**

Run: `vendor/bin/phpunit tests/Structure/PluginContractTest.php tests/Structure/DocumentationAndReleaseTest.php tests/Unit/Service/DisplaySettingsTest.php`

Expected: FAIL mit den noch vorhandenen 1.1.1-Werten und fehlenden Impressumsseiten.

- [ ] **Step 3: Bump the release and write user-facing documentation**

Die Pluginversion und alle aktuellen Installations-/Paketbefehle wechseln auf 1.2.0. Historische 1.1.1-Abnahme- und Releasedokumente bleiben unverändert. README und Wiki erklären Zweck, Zugriff, Kontaktdaten, Datenschutzgrenze und ausdrücklich, dass der Tab kein öffentliches Shop-Impressum ersetzt.

- [ ] **Step 4: Run affected tests and verify they pass**

Run: `vendor/bin/phpunit tests/Structure/PluginContractTest.php tests/Structure/DocumentationAndReleaseTest.php tests/Unit/Service/DisplaySettingsTest.php`

Expected: PASS und reproduzierbares Paket `dist/MGD_AI_Kennzeichnung-1.2.0.zip`.

- [ ] **Step 5: Commit**

```bash
git add plugin/MGD_AI_Kennzeichnung/info.xml scripts tests README.md README.en.md CHANGELOG.md Dokumentation wiki
git commit -m "docs: veröffentlicht Impressum-Funktion in Version 1.2.0"
```

### Task 3: Gesamtprüfung und Release

**Files:**
- Verify: all tracked project files
- Build: `dist/MGD_AI_Kennzeichnung-1.2.0.zip`

- [ ] **Step 1: Run the complete verification suite**

```bash
composer test
composer test:js
vendor/bin/phpstan analyse --memory-limit=512M
composer style
bash scripts/build-release.sh
unzip -t dist/MGD_AI_Kennzeichnung-1.2.0.zip
shasum -a 256 dist/MGD_AI_Kennzeichnung-1.2.0.zip
git diff --check origin/main...HEAD
```

Expected: alle Tests, Analyse-, Stil-, ZIP- und Diff-Prüfungen ohne Fehler.

- [ ] **Step 2: Inspect the release payload**

Das ZIP muss genau einen Stammordner `MGD_AI_Kennzeichnung/` enthalten, darunter `adminmenu/impressum.php` und `adminmenu/templates/impressum.tpl`, aber weder Tests, Git-Daten, `.env` noch Abhängigkeiten.

- [ ] **Step 3: Integrate on main and publish**

Nach erfolgreicher Prüfung werden die Commits ohne Force-Push auf `main` übernommen, `main` gepusht, Tag `v1.2.0` erstellt und ein GitHub-Release mit ZIP und SHA-256-Datei veröffentlicht. Eine Installation auf `dev.onvis-shop.de` erfolgt nur als getrennte, nachvollziehbare Dev-Aktualisierung; `onvis-shop.de` bleibt unangetastet.

