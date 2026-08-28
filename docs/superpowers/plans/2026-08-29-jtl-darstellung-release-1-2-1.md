# JTL-Darstellung und Release 1.2.1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Das JTL-Shop-5-Plugin erhält eine sichere Darstellungsseite mit lokaler Live-Vorschau, Radius-, Unschärfe- und Transparenzreglern, die neue Herstellernennung sowie einen konsistenten GitHub-Release 1.2.1.

**Architecture:** Die global wirksamen Designwerte bleiben als JTL-Pluginoptionen erhalten und werden über `type="none"` in einem eigenen, geschützten Admin-Tab bearbeitet. Bildbezogene Position und Farbschema bleiben in der Bildverwaltung; im Darstellungstab dienen sie ausdrücklich nur der Vorschau. Domainmodell, Renderer und CSS führen Transparenz als geprüften Wert durch alle Ausgabewege; die GitHub-Prüfung wird ausschließlich im Admin-Kontext ausgeführt und erhält einen positiven wie negativen Zwölf-Stunden-Cache.

**Tech Stack:** PHP 8.1, JTL-Shop 5.7.2 Plugin-API, Smarty, CSS Custom Properties, ES Modules, PHPUnit 10, Node Test Runner, PHPStan, PHP-CS-Fixer, Bash, Git und GitHub CLI.

---

## Verbindliche Dateistruktur

### Neu anzulegen

- `plugin/MGD_AI_Kennzeichnung/Admin/Display/DisplaySettingsInput.php` – streng validiertes Formularmodell.
- `plugin/MGD_AI_Kennzeichnung/Admin/Display/DisplaySettingsAdminService.php` – Berechtigung, CSRF und koordinierte Speicherung.
- `plugin/MGD_AI_Kennzeichnung/Admin/Port/DisplayConfigPortInterface.php` – kleine Lese-/Schreibgrenze für JTL-Optionen.
- `plugin/MGD_AI_Kennzeichnung/Admin/Adapter/JtlDisplayConfigAdapter.php` – Speicherung in `tplugineinstellungen` und Cache-Invalidierung.
- `plugin/MGD_AI_Kennzeichnung/adminmenu/display.php` – geschützter Einstiegspunkt des Darstellungstabs.
- `plugin/MGD_AI_Kennzeichnung/adminmenu/templates/display.tpl` – semantisches zweispaltiges Formular.
- `plugin/MGD_AI_Kennzeichnung/adminmenu/display.css` – responsives Layout und Vorschau.
- `plugin/MGD_AI_Kennzeichnung/adminmenu/js/display-range-sync.mjs` – Kopplung von Zahlenfeld und Regler.
- `plugin/MGD_AI_Kennzeichnung/adminmenu/js/display-preview.mjs` – reine Berechnung sicherer Vorschauwerte.
- `plugin/MGD_AI_Kennzeichnung/adminmenu/js/display-controls.mjs` – DOM-Initialisierung der Live-Vorschau.
- `plugin/MGD_AI_Kennzeichnung/adminmenu/images/michael-gahn-design-schuh.png` – lokal generiertes fiktives Produktbild.
- `plugin/MGD_AI_Kennzeichnung/Infrastructure/Update/Value/ReleaseCheckState.php` – positiver oder negativer Cachezustand.
- `tests/Unit/Admin/DisplaySettingsAdminServiceTest.php` – Service- und Validierungsvertrag.
- `tests/Integration/Admin/DisplayEntryPointTest.php` – echter JTL-Admin-Einstieg mit GET/POST-Schutz.
- `tests/Structure/DisplayAdminContractTest.php` – Menü, Template, lokale Assets und Barrierefreiheit.
- `tests/JavaScript/display-range-sync.test.mjs` – Wertebereich und Feldkopplung.
- `tests/JavaScript/display-preview.test.mjs` – sichere Klassen und CSS-Werte.
- `Dokumentation/Darstellung.md` – Bedienung und technische Grenzen.
- `Dokumentation/Release-1.2.1.md` – Release- und Updatehinweise.

### Bestehend und gezielt zu ändern

- `plugin/MGD_AI_Kennzeichnung/info.xml` – Version, Menü, `type="none"`, Standardwert und Transparenz.
- `plugin/MGD_AI_Kennzeichnung/Service/DisplaySettings.php` – globales Transparenzmodell; tote globale Position/Theme entfernen.
- `plugin/MGD_AI_Kennzeichnung/Service/LabelViewResolver.php` – Transparenz normalisieren und weiterreichen.
- `plugin/MGD_AI_Kennzeichnung/Domain/LabelView.php` – geprüfte Transparenz und sichere Hintergrunddeckkraft.
- `plugin/MGD_AI_Kennzeichnung/Presentation/LabelRenderer.php` – sichere CSS-Variable ausgeben.
- `plugin/MGD_AI_Kennzeichnung/Presentation/FrontendDocumentIntegrator.php` – Transparenz in native Labels führen.
- `plugin/MGD_AI_Kennzeichnung/frontend/template/label.tpl` – Smarty-Ausgabe ergänzen.
- `plugin/MGD_AI_Kennzeichnung/frontend/css/mgd-ai-labels.css` – Theme-Hintergründe über Deckkraftvariable steuern.
- `plugin/MGD_AI_Kennzeichnung/Presentation/FooterCreditRenderer.php` und `frontend/template/layout/footer.tpl` – neue Herstellernennung.
- `plugin/MGD_AI_Kennzeichnung/Infrastructure/Update/GitHubReleaseChecker.php` – Version 1.2.1 und negativer Cache.
- `plugin/MGD_AI_Kennzeichnung/Infrastructure/Update/Port/ReleaseCacheInterface.php` – Cachezustand statt nur Erfolg laden/speichern.
- `plugin/MGD_AI_Kennzeichnung/Infrastructure/Update/Adapter/FileReleaseCache.php` – minimales JSON für Erfolg und Fehlerzeitpunkt.
- `tests/Stubs/JtlPluginStubs.php` – beobachtbare Konfiguration und Cache-Schnittstelle.
- bestehende Unit-, Struktur-, Integrations- und Release-Tests – neue Verträge ergänzen.
- `README.md`, `CHANGELOG.md`, `SECURITY.md`, einschlägige Dateien unter `Dokumentation/` und `wiki/` – Benutzerhandbuch aktualisieren.
- `scripts/build-release.sh`, `scripts/README.md`, `.github/workflows/quality.yml` – überall exakt Version 1.2.1.

---

### Task 1: Transparenz durch das vollständige Frontend-Modell führen

**Files:**
- Modify: `tests/Unit/Service/DisplaySettingsTest.php`
- Modify: `tests/Unit/Service/LabelViewResolverTest.php`
- Modify: `tests/Unit/Frontend/LabelRendererTest.php`
- Modify: `tests/Integration/Frontend/BootstrapFrontendTest.php`
- Modify: `plugin/MGD_AI_Kennzeichnung/Service/DisplaySettings.php`
- Modify: `plugin/MGD_AI_Kennzeichnung/Service/LabelViewResolver.php`
- Modify: `plugin/MGD_AI_Kennzeichnung/Domain/LabelView.php`
- Modify: `plugin/MGD_AI_Kennzeichnung/Presentation/LabelRenderer.php`
- Modify: `plugin/MGD_AI_Kennzeichnung/Presentation/FrontendDocumentIntegrator.php`
- Modify: `plugin/MGD_AI_Kennzeichnung/frontend/template/label.tpl`
- Modify: `plugin/MGD_AI_Kennzeichnung/frontend/css/mgd-ai-labels.css`
- Modify: `plugin/MGD_AI_Kennzeichnung/Bootstrap.php`

- [ ] **Step 1: Zuerst fehlende Transparenz als roten Vertrag testen**

Ergänze die vorhandenen Testfälle so, dass sie folgende kanonische Werte erwarten:

```php
$settings = DisplaySettings::fromJtlConfig([
    'font_size' => '16',
    'outer_margin' => '9',
    'inner_padding' => '7',
    'border_radius' => '5',
    'blur' => '3',
    'transparency' => '8',
]);

self::assertSame(8, $settings->transparency);

$view = (new LabelViewResolver())->resolve(
    status: 'generated',
    transparency: 8,
);
self::assertSame(8, $view->transparency);
self::assertSame('0.92', $view->backgroundOpacity);
self::assertStringContainsString('--mgd-ai-background-opacity:0.92', (new LabelRenderer())->render($view));
```

Teste zusätzlich `-1 => 0`, `91 => 90`, manipulierte Strings auf den Standard `8`, `0 => 1.00` und `90 => 0.10`.

- [ ] **Step 2: Die fokussierten Tests ausführen und den erwarteten Rotlauf bestätigen**

Run:

```bash
vendor/bin/phpunit tests/Unit/Service/DisplaySettingsTest.php \
  tests/Unit/Service/LabelViewResolverTest.php \
  tests/Unit/Frontend/LabelRendererTest.php \
  tests/Integration/Frontend/BootstrapFrontendTest.php
```

Expected: FAIL wegen der noch fehlenden Eigenschaften `transparency` beziehungsweise `backgroundOpacity`.

- [ ] **Step 3: Das minimale unveränderliche Modell ergänzen**

Verwende in `DisplaySettings` exakt den globalen Bereich 0 bis 90 und den Standard 8:

```php
private const DEFAULT_TRANSPARENCY = 8;

public readonly int $transparency,

transparency: self::boundedInteger(
    $werte['transparency'] ?? null,
    self::DEFAULT_TRANSPARENCY,
    0,
    90,
),
```

`fromJtlConfig()` mappt ausschließlich `transparency` über `jtlInteger()`. Entferne `position` und `theme` aus `DisplaySettings`, weil beide Werte im Frontend aus jedem Bilddatensatz kommen und global bisher wirkungslos waren.

Ergänze `LabelView::forVisibleLabel()` um `int $transparency` und leite ausschließlich intern die Deckkraft ab:

```php
private static function backgroundOpacity(int $transparency): string
{
    $transparency = self::boundedInteger($transparency, 0, 90);
    $opacity = 100 - $transparency;

    return sprintf('%d.%02d', intdiv($opacity, 100), $opacity % 100);
}
```

Das sichtbare Modell enthält `public readonly int $transparency` und `public readonly string $backgroundOpacity`; das versteckte Modell verwendet `0` und `'1.00'`.

- [ ] **Step 4: Renderer, Smarty und CSS ohne freie Stylewerte ergänzen**

Erweitere den festen Style-String um:

```php
--mgd-ai-background-opacity:%s
```

Der Wert stammt ausschließlich aus `$view->backgroundOpacity`. Ergänze im Smarty-Template dieselbe Variable. In CSS gilt:

```css
.mgd-ai-label {
    --mgd-ai-background-opacity: 0.92;
}

.mgd-ai-label--theme-auto,
.mgd-ai-label--theme-dark {
    color: #fff;
    background: rgba(17, 24, 39, var(--mgd-ai-background-opacity));
}

.mgd-ai-label--theme-light {
    color: #111827;
    background: rgba(255, 255, 255, var(--mgd-ai-background-opacity));
}
```

`FrontendDocumentIntegrator` reicht `$settings->transparency` an den Resolver weiter. `Bootstrap` liest `transparency`, aber nicht mehr die toten globalen Werte `position` und `theme`.

- [ ] **Step 5: Fokussierte Tests und Analyse grün ausführen**

Run:

```bash
vendor/bin/phpunit tests/Unit/Service/DisplaySettingsTest.php \
  tests/Unit/Service/LabelViewResolverTest.php \
  tests/Unit/Frontend/LabelRendererTest.php \
  tests/Integration/Frontend/BootstrapFrontendTest.php
vendor/bin/phpstan analyse plugin/MGD_AI_Kennzeichnung/Service \
  plugin/MGD_AI_Kennzeichnung/Domain \
  plugin/MGD_AI_Kennzeichnung/Presentation
```

Expected: PASS und PHPStan ohne Fehler.

- [ ] **Step 6: Den abgeschlossenen Frontend-Schnitt committen**

```bash
git add plugin/MGD_AI_Kennzeichnung/Bootstrap.php \
  plugin/MGD_AI_Kennzeichnung/Service/DisplaySettings.php \
  plugin/MGD_AI_Kennzeichnung/Service/LabelViewResolver.php \
  plugin/MGD_AI_Kennzeichnung/Domain/LabelView.php \
  plugin/MGD_AI_Kennzeichnung/Presentation/LabelRenderer.php \
  plugin/MGD_AI_Kennzeichnung/Presentation/FrontendDocumentIntegrator.php \
  plugin/MGD_AI_Kennzeichnung/frontend/template/label.tpl \
  plugin/MGD_AI_Kennzeichnung/frontend/css/mgd-ai-labels.css \
  tests/Unit tests/Integration/Frontend/BootstrapFrontendTest.php
git commit -m "feat: ergänzt globale Label-Transparenz"
```

### Task 2: Herstellernennung exakt und sicher ändern

**Files:**
- Modify: `tests/Unit/Frontend/FooterCreditRendererTest.php`
- Modify: `tests/Unit/Presentation/FrontendDocumentIntegratorTest.php`
- Modify: `plugin/MGD_AI_Kennzeichnung/Presentation/FooterCreditRenderer.php`
- Modify: `plugin/MGD_AI_Kennzeichnung/frontend/template/layout/footer.tpl`

- [ ] **Step 1: Den exakten neuen HTML-Vertrag zuerst testen**

```php
self::assertSame(
    '<p class="mgd-ai-footer-credit">supported by: <a href="https://Michael-Gahn.de" target="_blank" rel="noopener noreferrer" aria-label="Michael Gahn DESIGN – Herstellerseite in neuem Fenster öffnen">Michael Gahn DESIGN</a></p>',
    (new FooterCreditRenderer())->render(true),
);
```

Prüfe außerdem weiterhin `render(false) === ''` und dass nur der Herstellername innerhalb des Links steht.

- [ ] **Step 2: Rotlauf bestätigen**

Run:

```bash
vendor/bin/phpunit tests/Unit/Frontend/FooterCreditRendererTest.php \
  tests/Unit/Presentation/FrontendDocumentIntegratorTest.php
```

Expected: FAIL mit der bisherigen Formulierung „Plugin von …“.

- [ ] **Step 3: Renderer und Fallback-Template deckungsgleich ändern**

Verwende getrennte Konstanten:

```php
private const PREFIX = 'supported by: ';
private const LINK_TEXT = 'Michael Gahn DESIGN';
private const ACCESSIBLE_LABEL = 'Michael Gahn DESIGN – Herstellerseite in neuem Fenster öffnen';
```

Der Präfix bleibt normaler Text; nur `LINK_TEXT` wird verlinkt. Behalte `target="_blank"` und `rel="noopener noreferrer"` in PHP und Smarty exakt bei.

- [ ] **Step 4: Tests grün ausführen und committen**

```bash
vendor/bin/phpunit tests/Unit/Frontend/FooterCreditRendererTest.php \
  tests/Unit/Presentation/FrontendDocumentIntegratorTest.php
git add plugin/MGD_AI_Kennzeichnung/Presentation/FooterCreditRenderer.php \
  plugin/MGD_AI_Kennzeichnung/frontend/template/layout/footer.tpl \
  tests/Unit/Frontend/FooterCreditRendererTest.php \
  tests/Unit/Presentation/FrontendDocumentIntegratorTest.php
git commit -m "feat: aktualisiert sichere Herstellernennung"
```

### Task 3: Sicheren Schreibdienst für JTL-Pluginoptionen bauen

**Files:**
- Create: `plugin/MGD_AI_Kennzeichnung/Admin/Display/DisplaySettingsInput.php`
- Create: `plugin/MGD_AI_Kennzeichnung/Admin/Display/DisplaySettingsAdminService.php`
- Create: `plugin/MGD_AI_Kennzeichnung/Admin/Port/DisplayConfigPortInterface.php`
- Create: `plugin/MGD_AI_Kennzeichnung/Admin/Adapter/JtlDisplayConfigAdapter.php`
- Create: `tests/Unit/Admin/DisplaySettingsAdminServiceTest.php`
- Modify: `tests/Stubs/JtlPluginStubs.php`

- [ ] **Step 1: Ports, gültige Eingabe und Ablehnungen als Tests definieren**

Der Port besitzt ausschließlich diese Signatur:

```php
interface DisplayConfigPortInterface
{
    /** @return array<string, mixed> */
    public function load(): array;

    /** @param array<string, string> $values */
    public function save(array $values): void;
}
```

Teste einen gültigen POST-Wert mit exakt diesen kanonischen Speicherwerten:

```php
[
    'language' => 'de',
    'font_size' => '18',
    'outer_margin' => '12',
    'inner_padding' => '8',
    'border_radius' => '10',
    'blur' => '6',
    'transparency' => '20',
]
```

Teste separat unbekannte Felder, Arrays, Leerzeichen, Dezimalzahlen, `12px`, Werte unter/über den Grenzen, ungültige Sprache, falsches CSRF-Token und fehlende Adminberechtigung. Jede dieser Eingaben muss `ValidationException`, `CsrfException` oder `AccessDeniedException` auslösen und `save()` am Port darf nicht aufgerufen werden.

- [ ] **Step 2: Rotlauf ausführen**

```bash
vendor/bin/phpunit tests/Unit/Admin/DisplaySettingsAdminServiceTest.php
```

Expected: FAIL, weil Port, Eingabemodell und Service noch fehlen.

- [ ] **Step 3: Strenges Formularmodell implementieren**

`DisplaySettingsInput::fromPost()` akzeptiert nur Strings und verwendet für Ganzzahlen dieses Muster:

```php
private static function integer(string $name, mixed $value, int $minimum, int $maximum): int
{
    if (!is_string($value) || preg_match('/^(?:0|[1-9]\d*)$/D', $value) !== 1) {
        throw new ValidationException(sprintf('%s muss eine Ganzzahl sein.', $name));
    }
    $integer = filter_var($value, FILTER_VALIDATE_INT);
    if (!is_int($integer) || $integer < $minimum || $integer > $maximum) {
        throw new ValidationException(sprintf('%s liegt außerhalb des sicheren Bereichs.', $name));
    }

    return $integer;
}
```

Das Modell ist `final`, besitzt ausschließlich `readonly`-Eigenschaften und gibt mit `toJtlConfig()` die sieben oben aufgeführten Zeichenketten zurück. Erlaubte Sprache: `auto`, `de`, `en`.

- [ ] **Step 4: Service mit fester Reihenfolge implementieren**

```php
public function save(string $csrfToken, mixed $post): DisplaySettings
{
    $this->authorization->assertCanManageAssets();
    $this->csrf->assertValid($csrfToken);
    $input = DisplaySettingsInput::fromPost($post);
    $this->config->save($input->toJtlConfig());

    return DisplaySettings::fromJtlConfig($input->toJtlConfig());
}
```

`load()` prüft zuerst die Berechtigung und liefert `DisplaySettings::fromJtlConfig($this->config->load())`.

- [ ] **Step 5: JTL-Adapter mit Transaktion und Cache-Invalidierung implementieren**

Der Adapter besitzt eine feste Positivliste der sieben `ValueName`-Schlüssel. Vor dem ersten UPDATE muss jeder Schlüssel in `$plugin->getConfig()` vorhanden sein. Die Schreibfolge lautet:

```php
if ($this->db->getPDO()->inTransaction() || !$this->db->beginTransaction()) {
    throw new RuntimeException('Die Darstellungseinstellungen konnten nicht reserviert werden.');
}
try {
    foreach (self::KEYS as $name) {
        $affected = $this->db->getAffectedRows(
            'UPDATE `tplugineinstellungen` SET `cWert` = :value WHERE `kPlugin` = :plugin_id AND `cName` = :name',
            ['value' => $values[$name], 'plugin_id' => $this->plugin->getID(), 'name' => $name],
        );
        if ($affected < 0 || $affected > 1) {
            throw new RuntimeException('Eine Pluginoption konnte nicht eindeutig gespeichert werden.');
        }
    }
    if (!$this->db->commit()) {
        throw new RuntimeException('Die Darstellungseinstellungen konnten nicht bestätigt werden.');
    }
} catch (Throwable $error) {
    $this->db->rollback();
    throw $error;
}
```

Danach werden ausschließlich `CACHING_GROUP_PLUGIN` und `CACHING_GROUP_PLUGIN . '_' . $pluginId` invalidiert. Ergänze die JTL-Teststubs um einen beobachtbaren `JTLCache` mit `flushTags(array $tags): int` und `DefaultServicesInterface::getCache(): JTLCache`. Aktualisiere die vorhandenen anonymen Testcontainer mit genau diesem Getter; `Config` bleibt für den Adapter lesend, weil die kanonische Speicherung transaktional über `tplugineinstellungen` erfolgt und der nächste Request nach der Cache-Invalidierung neu lädt.

- [ ] **Step 6: Service- und Adaptertests grün ausführen**

```bash
vendor/bin/phpunit tests/Unit/Admin/DisplaySettingsAdminServiceTest.php \
  tests/Unit/Admin/JtlRuntimeAdapterTest.php
vendor/bin/phpstan analyse plugin/MGD_AI_Kennzeichnung/Admin/Display \
  plugin/MGD_AI_Kennzeichnung/Admin/Adapter/JtlDisplayConfigAdapter.php \
  plugin/MGD_AI_Kennzeichnung/Admin/Port/DisplayConfigPortInterface.php
```

Expected: PASS; kein Speichern ohne Berechtigung und CSRF.

- [ ] **Step 7: Commit**

```bash
git add plugin/MGD_AI_Kennzeichnung/Admin tests/Unit/Admin tests/Stubs/JtlPluginStubs.php
git commit -m "feat: speichert Darstellungsoptionen sicher in JTL"
```

### Task 4: Lokales Vorschauprodukt mit Imagegen erstellen

**Files:**
- Create: `plugin/MGD_AI_Kennzeichnung/adminmenu/images/michael-gahn-design-schuh.png`
- Modify: `tests/Structure/DisplayAdminContractTest.php`

- [ ] **Step 1: Strukturtest für ein begrenztes lokales Bild schreiben**

```php
$image = self::ROOT . '/plugin/MGD_AI_Kennzeichnung/adminmenu/images/michael-gahn-design-schuh.png';
self::assertFileExists($image);
self::assertLessThanOrEqual(2_000_000, filesize($image));
$size = getimagesize($image);
self::assertIsArray($size);
self::assertSame('image/png', $size['mime'] ?? null);
self::assertGreaterThanOrEqual(800, $size[0]);
self::assertGreaterThanOrEqual(800, $size[1]);
```

- [ ] **Step 2: Rotlauf bestätigen**

```bash
vendor/bin/phpunit tests/Structure/DisplayAdminContractTest.php
```

Expected: FAIL, weil das lokale Bild noch nicht existiert.

- [ ] **Step 3: Den Imagegen-Skill verwenden und das Bild ohne Fremdmarken erzeugen**

Verwende exakt diesen Prompt als Ausgangspunkt:

```text
Quadratische hochwertige Studio-Produktfotografie eines vollständig fiktiven modernen Premium-Sneakers. Elegante schwarze und dunkelgraue Materialien mit einem dezenten frischen grünen Akzent, klare handwerkliche Details, drei Viertel Ansicht, sauberer hellgrauer Hintergrund, weiches professionelles Produktlicht, realistische Schatten, keine Person, keine Füße, kein Text, kein Logo, keine bekannte Marke, keine Wasserzeichen. Das Motiv muss als glaubwürdiges Beispielprodukt „Michael Gahn DESIGN Schuh“ in einer Software-Live-Vorschau funktionieren und in der Bildmitte ausreichend freien Rand für ein kleines KI-Kennzeichnungslabel lassen.
```

Speichere nur die ausgewählte Ausgabe am festgelegten Pluginpfad. Keine URL und keine Generierungsmetadaten werden in das Plugin übernommen.

- [ ] **Step 4: Bild visuell und technisch prüfen**

Prüfe mit `view_image`, dass Schuh, neutraler Hintergrund und Freiraum vorhanden sind und keine fremden Marken oder fehlerhaften Texte erscheinen. Danach:

```bash
file plugin/MGD_AI_Kennzeichnung/adminmenu/images/michael-gahn-design-schuh.png
vendor/bin/phpunit tests/Structure/DisplayAdminContractTest.php
```

Expected: PNG, maximal 2 MB, Test PASS.

- [ ] **Step 5: Commit**

```bash
git add plugin/MGD_AI_Kennzeichnung/adminmenu/images/michael-gahn-design-schuh.png \
  tests/Structure/DisplayAdminContractTest.php
git commit -m "feat: ergänzt lokales Vorschauprodukt"
```

### Task 5: Zweispaltigen geschützten Darstellungstab integrieren

**Files:**
- Create: `plugin/MGD_AI_Kennzeichnung/adminmenu/display.php`
- Create: `plugin/MGD_AI_Kennzeichnung/adminmenu/templates/display.tpl`
- Create: `plugin/MGD_AI_Kennzeichnung/adminmenu/display.css`
- Create: `tests/Integration/Admin/DisplayEntryPointTest.php`
- Modify: `tests/Structure/DisplayAdminContractTest.php`
- Modify: `tests/Unit/Service/DisplaySettingsTest.php`
- Modify: `tests/Structure/PluginContractTest.php`
- Modify: `plugin/MGD_AI_Kennzeichnung/info.xml`

- [ ] **Step 1: Manifest- und Templatevertrag rot schreiben**

Prüfe mit DOM/XPath:

```php
self::assertSame('1.2.1', trim((string) $xpath->evaluate('string(/jtlshopplugin/Version)')));
self::assertSame(
    'display.php',
    trim((string) $xpath->evaluate('string(/jtlshopplugin/Install/Adminmenu/Customlink[Name="Darstellung"]/Filename)')),
);
self::assertSame(
    'Y',
    $xpath->evaluate('string(/jtlshopplugin/Install/Adminmenu/Settingslink/Setting[ValueName="update_notices"]/@initialValue)'),
);
```

Für `language`, `font_size`, `outer_margin`, `inner_padding`, `border_radius`, `blur` und `transparency` muss `type="none"` und `conf="Y"` gelten. `show_credit` und `update_notices` bleiben sichtbare Selectboxen. Es darf keine globale Option `position` oder `theme` mehr geben.

Der Strukturtest verlangt außerdem: zwei Spalten, `<form method="post">`, CSRF-Feld, lokale Bild-URL, Alt-Text „Fiktiver Michael Gahn DESIGN Schuh“, sichtbare Einheiten, einen echten Speichern-Button sowie `aria-live` für Rückmeldungen.

- [ ] **Step 2: Rotlauf ausführen**

```bash
vendor/bin/phpunit tests/Structure/DisplayAdminContractTest.php \
  tests/Integration/Admin/DisplayEntryPointTest.php \
  tests/Unit/Service/DisplaySettingsTest.php \
  tests/Structure/PluginContractTest.php
```

Expected: FAIL wegen fehlendem Menü, Template und Version 1.2.1.

- [ ] **Step 3: `info.xml` ohne konkurrierende Optionen umbauen**

Registriere die Reihenfolge:

```xml
<Customlink sort="1"><Name>Bildverwaltung</Name><Filename>assets.php</Filename></Customlink>
<Customlink sort="2"><Name>AI-Philosophie</Name><Filename>philosophy.php</Filename></Customlink>
<Customlink sort="3"><Name>Darstellung</Name><Filename>display.php</Filename></Customlink>
<Customlink sort="4"><Name>Impressum</Name><Filename>impressum.php</Filename></Customlink>
<Settingslink sort="5">
```

Setze `update_notices` auf `initialValue="Y"`. Die sieben Darstellungswerte werden als `type="none"` geführt; `transparency` erhält `initialValue="8"`. Setze `<Version>1.2.1</Version>`.

- [ ] **Step 4: Admin-Einstieg mit exakt begrenzten GET-/POST-Pfaden implementieren**

Der Einstieg folgt `philosophy.php` und akzeptiert:

```php
if ($request->method === 'POST') {
    $expected = [
        'blur', 'border_radius', 'csrf_token', 'font_size', 'inner_padding',
        'kPlugin', 'kPluginAdminMenu', 'language', 'outer_margin', 'transparency',
    ];
    $fields = array_keys($request->post);
    sort($fields, SORT_STRING);
    sort($expected, SORT_STRING);
    if ($fields !== $expected || $request->query !== []) {
        throw new ValidationException('Das Darstellungsformular enthält unerwartete Felder.');
    }
    $settings = $service->save((string) $request->post['csrf_token'], $request->post);
    $message = 'Die Darstellung wurde sicher gespeichert.';
} elseif ($request->method === 'GET' && $request->query === [] && $request->post === []) {
    $settings = $service->load();
} else {
    throw new ValidationException('Die Anfrage wird für den Darstellungstab nicht unterstützt.');
}
```

Direkter Zugriff ohne `PFAD_ROOT`, gültiges `PluginInterface`, Session und Admin-Menü-ID liefert 403. Validierungs- oder CSRF-Fehler liefern 400; unerwartete Fehler liefern 500 und loggen nur `event_code=display_request_failed`, niemals Eingabewerte.

- [ ] **Step 5: Template und responsives CSS implementieren**

Das Layout verwendet:

```css
.mgd-display-layout {
    display: grid;
    grid-template-columns: minmax(20rem, 0.9fr) minmax(22rem, 1.1fr);
    gap: 1.5rem;
    align-items: start;
}

@media (max-width: 62rem) {
    .mgd-display-layout { grid-template-columns: 1fr; }
}
```

Links stehen Sprache, Schriftgröße, Außen-/Innenabstand sowie die gekoppelten Bedienelemente für Radius, Unschärfe und Transparenz. Rechts stehen das lokale Bild, das Label „KI-GENERIERT“ und zwei ausdrücklich mit „Nur Vorschau“ bezeichnete Selectboxen für Position und Farbschema. Das Template lädt ausschließlich `display.css` und das lokale Modul `display-controls.mjs`; keine Inline-Skripte oder externen Ressourcen.

- [ ] **Step 6: Struktur und realen Einstieg grün testen**

```bash
vendor/bin/phpunit tests/Structure/DisplayAdminContractTest.php \
  tests/Integration/Admin/DisplayEntryPointTest.php \
  tests/Unit/Service/DisplaySettingsTest.php \
  tests/Structure/PluginContractTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add plugin/MGD_AI_Kennzeichnung/info.xml \
  plugin/MGD_AI_Kennzeichnung/adminmenu/display.php \
  plugin/MGD_AI_Kennzeichnung/adminmenu/display.css \
  plugin/MGD_AI_Kennzeichnung/adminmenu/templates/display.tpl \
  tests/Structure tests/Integration/Admin/DisplayEntryPointTest.php \
  tests/Unit/Service/DisplaySettingsTest.php
git commit -m "feat: ergänzt geschützten Darstellungstab"
```

### Task 6: Synchronisierte Regler und Live-Vorschau implementieren

**Files:**
- Create: `plugin/MGD_AI_Kennzeichnung/adminmenu/js/display-range-sync.mjs`
- Create: `plugin/MGD_AI_Kennzeichnung/adminmenu/js/display-preview.mjs`
- Create: `plugin/MGD_AI_Kennzeichnung/adminmenu/js/display-controls.mjs`
- Create: `tests/JavaScript/display-range-sync.test.mjs`
- Create: `tests/JavaScript/display-preview.test.mjs`
- Modify: `plugin/MGD_AI_Kennzeichnung/adminmenu/templates/display.tpl`
- Modify: `plugin/MGD_AI_Kennzeichnung/adminmenu/display.css`

- [ ] **Step 1: Reine Funktionen zuerst rot testen**

`display-range-sync.test.mjs` erwartet:

```js
assert.equal(normalizeInteger('5', 0, 32, 4), 5);
assert.equal(normalizeInteger('-1', 0, 32, 4), 4);
assert.equal(normalizeInteger('33', 0, 32, 4), 4);
assert.equal(normalizeInteger('5px', 0, 32, 4), 4);
```

`display-preview.test.mjs` erwartet:

```js
assert.deepEqual(createPreviewModel({
    language: 'de', position: 'bottom-right', theme: 'dark',
    fontSize: '12', outerMargin: '8', innerPadding: '6',
    borderRadius: '5', blur: '15', transparency: '20',
}), {
    text: 'KI-GENERIERT',
    positionClass: 'mgd-display-preview--bottom-right',
    themeClass: 'mgd-display-preview--theme-dark',
    styles: {
        '--mgd-preview-font-size': '12px',
        '--mgd-preview-outer-margin': '8px',
        '--mgd-preview-inner-padding': '6px',
        '--mgd-preview-border-radius': '5px',
        '--mgd-preview-blur': '15px',
        '--mgd-preview-background-opacity': '0.80',
    },
});
```

Unbekannte Sprache, Position oder Theme müssen auf feste sichere Werte zurückfallen; freie Klassennamen dürfen nie entstehen.

- [ ] **Step 2: Rotlauf bestätigen**

```bash
node --test tests/JavaScript/display-range-sync.test.mjs \
  tests/JavaScript/display-preview.test.mjs
```

Expected: FAIL wegen fehlender Module.

- [ ] **Step 3: Kleine reine Module implementieren**

`normalizeInteger()` akzeptiert ausschließlich `/^(?:0|[1-9]\d*)$/`; `createPreviewModel()` verwendet Positivlisten und berechnet die Deckkraft so:

```js
const opacity = 100 - normalizeInteger(values.transparency, 0, 90, 8);
const backgroundOpacity = `${Math.trunc(opacity / 100)}.${String(opacity % 100).padStart(2, '0')}`;
```

`display-controls.mjs` koppelt je `data-mgd-number` und `data-mgd-range`, reagiert auf `input` und `change`, aktualisiert nur das Vorschau-DOM und löst kein Speichern aus. Der Formular-Submit bleibt natives POST.

- [ ] **Step 4: Barrierearme Zustände ergänzen**

Zahlenfeld und Regler teilen sich eine sichtbare Bezeichnung, besitzen getrennte IDs, passende `min`, `max`, `step="1"` und `aria-describedby`. Der Vorschautext wird über `aria-live="polite"` aktualisiert. Keine Animation wird benötigt; CSS enthält weiterhin einen `prefers-reduced-motion`-Fallback.

- [ ] **Step 5: JavaScript- und Strukturtests grün ausführen**

```bash
composer test:js
vendor/bin/phpunit tests/Structure/DisplayAdminContractTest.php
```

Expected: alle JavaScript- und Strukturtests PASS.

- [ ] **Step 6: Commit**

```bash
git add plugin/MGD_AI_Kennzeichnung/adminmenu/js \
  plugin/MGD_AI_Kennzeichnung/adminmenu/templates/display.tpl \
  plugin/MGD_AI_Kennzeichnung/adminmenu/display.css \
  tests/JavaScript tests/Structure/DisplayAdminContractTest.php
git commit -m "feat: ergänzt Live-Vorschau und gekoppelte Regler"
```

### Task 7: GitHub-Prüfung an Adminbereich anbinden und Fehler negativ cachen

**Files:**
- Create: `plugin/MGD_AI_Kennzeichnung/Infrastructure/Update/Value/ReleaseCheckState.php`
- Modify: `plugin/MGD_AI_Kennzeichnung/Infrastructure/Update/Port/ReleaseCacheInterface.php`
- Modify: `plugin/MGD_AI_Kennzeichnung/Infrastructure/Update/Adapter/FileReleaseCache.php`
- Modify: `plugin/MGD_AI_Kennzeichnung/Infrastructure/Update/GitHubReleaseChecker.php`
- Modify: `plugin/MGD_AI_Kennzeichnung/adminmenu/display.php`
- Modify: `plugin/MGD_AI_Kennzeichnung/adminmenu/templates/display.tpl`
- Modify: `tests/Unit/Infrastructure/GitHubReleaseCheckerTest.php`
- Modify: `tests/Integration/Admin/DisplayEntryPointTest.php`

- [ ] **Step 1: Negative Cachefälle zuerst rot testen**

Für jede Antwort `404`, `429`, `500`, ungültiges JSON, zu große Antwort und Transport-Exception gilt:

```php
$first = $checker->check(true, '1.2.1');
$second = $checker->check(true, '1.2.1');

self::assertNull($first);
self::assertNull($second);
self::assertSame(1, $http->calls);
self::assertSame(1_700_000_000, $cache->stored?->attemptedAt);
self::assertNull($cache->stored?->release);
```

Exakt nach 43.200 Sekunden darf ein neuer Request erfolgen. Ein frischer erfolgreicher Zustand liefert weiterhin einen Hinweis, wenn der Tag neuer ist.

- [ ] **Step 2: Rotlauf bestätigen**

```bash
vendor/bin/phpunit tests/Unit/Infrastructure/GitHubReleaseCheckerTest.php
```

Expected: FAIL, weil Fehler noch keinen `attemptedAt` speichern.

- [ ] **Step 3: Minimalen Cachezustand implementieren**

```php
final class ReleaseCheckState
{
    public function __construct(
        public readonly int $attemptedAt,
        public readonly ?CachedRelease $release,
    ) {
        if ($attemptedAt < 0 || ($release !== null && $release->fetchedAt !== $attemptedAt)) {
            throw new InvalidArgumentException('Der Release-Cachezustand ist ungültig.');
        }
    }
}
```

`ReleaseCacheInterface::load()` liefert `?ReleaseCheckState`, `save()` akzeptiert `ReleaseCheckState`. Das JSON besitzt exakt die Schlüssel `attemptedAt` und `release`; `release` ist `null` oder ein Objekt mit `tag`, `url`, `fetchedAt`. Maximale Dateigröße, 0600-Rechte und Sperren bleiben unverändert.

- [ ] **Step 4: Checker mit `finally` gegen Wiederholungsanfragen absichern**

`checkExclusively()` prüft zuerst `attemptedAt`. Nach einem echten Abruf wird in jedem Fall gespeichert:

```php
$release = null;
try {
    $release = $this->fetchValidatedRelease($now);

    return $release === null ? null : $this->noticeWhenNewer($release, $currentVersion);
} finally {
    $this->cache->save(new ReleaseCheckState($now, $release));
}
```

Setze den festen User-Agent auf `MGD-AI-Kennzeichnung-JTL-Shop-5/1.2.1`. Keine Tokens, Redirects oder dynamischen URLs hinzufügen.

- [ ] **Step 5: Prüfung nur im authentifizierten Darstellungstab ausführen**

Nur beim gültigen GET des Darstellungstabs und nur bei `update_notices === 'Y'` wird der Checker zusammengesetzt. Cachepfad:

```php
$cachePath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
    . DIRECTORY_SEPARATOR
    . 'mgd-ai-release-'
    . hash('sha256', (string) PFAD_ROOT)
    . '.json';
```

Das Template zeigt einen Hinweis nur bei einem validierten neueren Release. Linkziel stammt ausschließlich aus `UpdateNotice`, öffnet sicher in einem neuen Tab und führt zur GitHub-Release-Seite. POST, Frontend und andere Adminseiten lösen keine Anfrage aus.

- [ ] **Step 6: Tests grün ausführen und committen**

```bash
vendor/bin/phpunit tests/Unit/Infrastructure/GitHubReleaseCheckerTest.php \
  tests/Integration/Admin/DisplayEntryPointTest.php
git add plugin/MGD_AI_Kennzeichnung/Infrastructure/Update \
  plugin/MGD_AI_Kennzeichnung/adminmenu/display.php \
  plugin/MGD_AI_Kennzeichnung/adminmenu/templates/display.tpl \
  tests/Unit/Infrastructure/GitHubReleaseCheckerTest.php \
  tests/Integration/Admin/DisplayEntryPointTest.php
git commit -m "feat: begrenzt Updateprüfung auf geschützten Adminbereich"
```

### Task 8: Dokumentation und reproduzierbares Releasepaket auf 1.2.1 bringen

**Files:**
- Modify: `README.md`
- Modify: `CHANGELOG.md`
- Modify: `SECURITY.md`
- Create: `Dokumentation/Darstellung.md`
- Create: `Dokumentation/Release-1.2.1.md`
- Modify: `Dokumentation/Datenschutz-und-Sicherheit.md`
- Modify: `Dokumentation/Installation-und-Livetest.md`
- Modify: `Dokumentation/Versionen.md`
- Modify: `Dokumentation/Risiken.md`
- Modify: `Dokumentation/Entscheidungen.md`
- Modify: `wiki/Home.md`
- Modify: `wiki/Einstellungen.md`
- Modify: `wiki/Status-und-Darstellung.md`
- Modify: `wiki/Installation-und-Update.md`
- Modify: `wiki/Datenschutz-und-Sicherheit.md`
- Modify: `wiki/Release-und-Rollback.md`
- Modify: `wiki/Fehlerbehebung.md`
- Modify: `wiki/FAQ.md`
- Modify: `wiki/_Sidebar.md`
- Modify: `scripts/build-release.sh`
- Modify: `scripts/README.md`
- Modify: `.github/workflows/quality.yml`
- Modify: `tests/Structure/DocumentationAndReleaseTest.php`

- [ ] **Step 1: Release- und Dokumentationsvertrag zuerst rot erweitern**

Setze in `DocumentationAndReleaseTest`:

```php
private const ZIP = self::ROOT . '/dist/MGD_AI_Kennzeichnung-1.2.1.zip';
```

Prüfe, dass Buildskript und CI ausschließlich den 1.2.1-Dateinamen verwenden, das ZIP die neue Darstellungsseite, alle drei JS-Module, CSS und Produktbild enthält und seine interne `info.xml` exakt `<Version>1.2.1</Version>` besitzt.

Die Dokumentationstests verlangen mindestens diese Begriffe: „Live-Vorschau“, „Transparenz“, „Nur Vorschau“, „privates Repository“, „manueller ZIP-Upload“, „Server-IP“, „zwölf Stunden“, „supported by: Michael Gahn DESIGN“ und „Version 1.2.1“.

- [ ] **Step 2: Rotlauf bestätigen**

```bash
vendor/bin/phpunit tests/Structure/DocumentationAndReleaseTest.php
```

Expected: FAIL wegen Version 1.2.0 und fehlender 1.2.1-Dokumentation.

- [ ] **Step 3: Benutzer- und Sicherheitsdokumentation vollständig aktualisieren**

Erkläre verständlich:

- welche Werte global gelten und welche pro Bild gespeichert werden;
- dass Vorschauposition und Vorschau-Theme nicht gespeichert werden;
- dass die Vorschau lokal bleibt und erst „Speichern“ den Shopwert ändert;
- dass `0 %` Transparenz deckend und `90 %` nahezu durchsichtig bedeutet;
- dass GitHub bei aktivierter Prüfung Server-IP, Zeitpunkt und User-Agent erhält;
- dass das private Repository anonym keine Releases liefert;
- dass 1.2.1 keinen Auto-Updater besitzt und per geprüftem ZIP im JTL-Plugin-Manager aktualisiert wird;
- wie Backup, Dev-Test, Cacheleerung und Rollback erfolgen.

Kein Dokument darf weiter behaupten, Updatehinweise seien bei Neuinstallationen standardmäßig deaktiviert.

- [ ] **Step 4: Build und CI exakt auf 1.2.1 setzen**

In `scripts/build-release.sh`:

```bash
ausgabedatei="${ausgabeordner}/MGD_AI_Kennzeichnung-1.2.1.zip"
```

In `.github/workflows/quality.yml`:

```yaml
- name: Installationspaket prüfen
  run: unzip -t dist/MGD_AI_Kennzeichnung-1.2.1.zip
```

Ergänze einen Test, der ZIP-Dateiname, `info.xml`, Buildskript und CI-Dateiname miteinander vergleicht.

- [ ] **Step 5: Dokumentations- und Pakettests grün ausführen**

```bash
vendor/bin/phpunit tests/Structure/DocumentationAndReleaseTest.php
bash scripts/build-release.sh
unzip -t dist/MGD_AI_Kennzeichnung-1.2.1.zip
unzip -p dist/MGD_AI_Kennzeichnung-1.2.1.zip MGD_AI_Kennzeichnung/info.xml | \
  grep -F '<Version>1.2.1</Version>'
```

Expected: PASS, ZIP-Test ohne Fehler, interne Version 1.2.1.

- [ ] **Step 6: Commit**

```bash
git add README.md CHANGELOG.md SECURITY.md Dokumentation wiki \
  scripts/build-release.sh scripts/README.md .github/workflows/quality.yml \
  tests/Structure/DocumentationAndReleaseTest.php
git commit -m "docs: dokumentiert Darstellung und Updateweg in 1.2.1"
```

### Task 9: Vollständige Qualitätssicherung und Releaseartefakte erzeugen

**Files:**
- Generated, not tracked: `dist/MGD_AI_Kennzeichnung-1.2.1.zip`
- Generated, not tracked: `dist/MGD_AI_Kennzeichnung-1.2.1.zip.sha256`
- Modify only if required by formatter: files already changed in Tasks 1–8

- [ ] **Step 1: Arbeitsbaum und Diff vor der Gesamtprüfung kontrollieren**

```bash
git status --short
git diff --check origin/main...HEAD
git diff --stat origin/main...HEAD
```

Expected: nur geplante Projektdateien; keine Zugangsdaten, `.env`, SQL-Dumps oder fremden Änderungen.

- [ ] **Step 2: Alle lokalen Qualitätsprüfungen ausführen**

```bash
composer validate --strict
composer test
composer test:js
composer analyse
composer style
```

Expected: alle Befehle Exit 0; PHP-CS-Fixer verlangt keine Änderungen.

- [ ] **Step 3: Falls der Formatter Änderungen verlangt, ausschließlich diese anwenden und erneut prüfen**

```bash
vendor/bin/php-cs-fixer fix
composer style
composer test
composer analyse
```

Expected: alle Befehle Exit 0. Keine fachlichen Änderungen zusammen mit reinem Formatieren verstecken.

- [ ] **Step 4: Reproduzierbarkeit und Prüfsumme erstellen**

```bash
bash scripts/build-release.sh
first_hash="$(shasum -a 256 dist/MGD_AI_Kennzeichnung-1.2.1.zip | awk '{print $1}')"
bash scripts/build-release.sh
second_hash="$(shasum -a 256 dist/MGD_AI_Kennzeichnung-1.2.1.zip | awk '{print $1}')"
test "$first_hash" = "$second_hash"
shasum -a 256 dist/MGD_AI_Kennzeichnung-1.2.1.zip > \
  dist/MGD_AI_Kennzeichnung-1.2.1.zip.sha256
unzip -t dist/MGD_AI_Kennzeichnung-1.2.1.zip
```

Expected: identische Hashes, gültiges ZIP und eine einzeilige SHA-256-Datei.

- [ ] **Step 5: Releasekandidaten mit Geheimnisscan prüfen**

```bash
zipinfo -1 dist/MGD_AI_Kennzeichnung-1.2.1.zip | \
  rg -n '(\.env|\.git|vendor/|tests/|\.DS_Store|\.pem$|\.sql$)' && exit 1 || true
unzip -p dist/MGD_AI_Kennzeichnung-1.2.1.zip MGD_AI_Kennzeichnung/info.xml | \
  rg -n '<Version>1\.2\.1</Version>'
```

Expected: keine verbotenen Einträge; Versionstreffer vorhanden.

- [ ] **Step 6: Verifikationscommit nur bei notwendigen Nacharbeiten erstellen**

Falls Formatter oder Prüfer Projektdateien verändert haben:

```bash
git add plugin tests README.md CHANGELOG.md SECURITY.md Dokumentation wiki scripts .github
git commit -m "test: finalisiert Releaseprüfung für 1.2.1"
```

Andernfalls keinen leeren Commit erzeugen.

### Task 10: Sicher nach GitHub `main` veröffentlichen und Release v1.2.1 anlegen

**Files:**
- No source-file changes expected
- Upload: `dist/MGD_AI_Kennzeichnung-1.2.1.zip`
- Upload: `dist/MGD_AI_Kennzeichnung-1.2.1.zip.sha256`

- [ ] **Step 1: Remotezustand und Fast-Forward-Bedingung prüfen**

```bash
git fetch origin
git status --short --branch
git merge-base --is-ancestor origin/main HEAD
test "$(git tag -l v1.2.1)" = ""
gh release view v1.2.1 --repo MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5 >/dev/null 2>&1 && exit 1 || true
```

Expected: sauberer Arbeitsbaum, `origin/main` ist Vorfahr von `HEAD`, Tag und Release existieren noch nicht.

- [ ] **Step 2: Letzte Kurzprüfung unmittelbar vor dem Push**

```bash
composer test
composer test:js
composer analyse
composer style
unzip -t dist/MGD_AI_Kennzeichnung-1.2.1.zip
shasum -a 256 -c dist/MGD_AI_Kennzeichnung-1.2.1.zip.sha256
```

Expected: alle Prüfungen Exit 0.

- [ ] **Step 3: Branch exakt als Fast-Forward nach `main` pushen**

```bash
git push origin HEAD:main
git branch -f main HEAD
```

Expected: GitHub `main` zeigt exakt den geprüften Commit. Falls Remote inzwischen abweicht, abbrechen und neu prüfen; niemals Force-Push verwenden.

- [ ] **Step 4: Annotierten Bezugspunkt und GitHub-Release erstellen**

```bash
git tag -a v1.2.1 -m "MGD AI Kennzeichnung 1.2.1"
git push origin v1.2.1
gh release create v1.2.1 \
  dist/MGD_AI_Kennzeichnung-1.2.1.zip \
  dist/MGD_AI_Kennzeichnung-1.2.1.zip.sha256 \
  --repo MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5 \
  --title "MGD AI Kennzeichnung 1.2.1" \
  --notes-file Dokumentation/Release-1.2.1.md
```

Expected: veröffentlichtes privates Release mit genau zwei Assets.

- [ ] **Step 5: GitHub-Release gegen lokale Artefakte verifizieren**

```bash
gh release view v1.2.1 \
  --repo MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5 \
  --json tagName,name,isDraft,isPrerelease,assets,url
git ls-remote --tags origin refs/tags/v1.2.1
git ls-remote origin refs/heads/main
```

Expected: Tag `v1.2.1`, weder Draft noch Prerelease, Assets `MGD_AI_Kennzeichnung-1.2.1.zip` und `.zip.sha256`; `main` zeigt den geprüften Releasecommit.

- [ ] **Step 6: Übergabe für den anschließenden Shop-Test dokumentieren**

Berichte:

- Commit und Tag;
- Release-URL;
- exakte SHA-256-Prüfsumme;
- erfolgreiche Testbefehle;
- klare Grenze: kein Auto-Updater, privates Repository, Updateprüfung anonym nicht funktionsfähig;
- nächster Schritt: Backup und manueller Upload des 1.2.1-ZIPs zuerst auf `dev.onvis-shop.de`, danach Funktionstest und erst nach separater Freigabe Live-Update.

---

## Selbstprüfung des Plans

- Spezifikationsabdeckung: Footer, Update-Standard, Radius/Unschärfe als Doppelbedienung, Transparenz, zweispaltige lokale Live-Vorschau, privates Repository, negativer Cache, Dokumentation, ZIP, `main`, Tag und Release sind jeweils einer konkreten Task zugeordnet.
- Typkonsistenz: Der globale Wert heißt durchgehend `transparency`; das Domainmodell stellt zusätzlich ausschließlich die intern berechnete Zeichenkette `backgroundOpacity` bereit.
- Datenquellen: Sprache und numerische Darstellung stammen aus JTL-Pluginoptionen; Position und Theme bleiben pro Bild und werden in der Darstellung nur als nicht gespeicherte Vorschauwerte verwendet.
- Sicherheit: Adminberechtigung, POST, CSRF, Positivlisten, Ganzzahlgrenzen, Transaktion, Cache-Invalidierung, TLS, keine Tokens und keine automatisierte Installation sind ausdrücklich geprüft.
- Veröffentlichung: Kein Force-Push, keine Live-Installation und kein unkontrollierter Shopzugriff sind Teil des Plans.
