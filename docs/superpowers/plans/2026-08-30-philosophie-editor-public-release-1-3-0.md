# Philosophie-Editor und öffentliches Release 1.3.0 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Der Tab „AI-Philosophie“ erhält zwei übersichtliche, große und vollständig lokale WYSIWYG-/HTML-Editoren; das Repository wird nach einer Prüfung der gesamten Git-Historie öffentlich und als Version 1.3.0 mit verständlicher Monetarisierungs- und Marketplace-Dokumentation veröffentlicht.

**Architecture:** Die vorhandenen Textareas bleiben als serverseitig autoritative Formularfelder und No-JavaScript-Fallback bestehen. Kleine ES-Module stellen je Sprachfassung einen visuellen Editor, einen HTML-Modus, eine klar begrenzte Werkzeugleiste und eine lokale Live-Synchronisierung bereit. Eingaben werden vor der Anzeige clientseitig über dieselbe Positivliste wie im bestehenden PHP-Sanitizer bereinigt; beim Speichern bleibt der PHP-Sanitizer die maßgebliche Sicherheitsgrenze. Es gibt keine externen Assets, Netzaufrufe, Telemetrie, Browser-Speicherung oder Drittanbieter-Bibliotheken.

**Tech Stack:** PHP 8.1, JTL-Shop 5.7.2 Plugin-API, Smarty, CSS, native ES Modules, DOMParser/Selection/Range, PHPUnit 10, Node Test Runner, PHPStan, PHP-CS-Fixer, Bash, Git und GitHub CLI.

---

## Verbindliche Dateistruktur

### Neu anzulegen

- `plugin/MGD_AI_Kennzeichnung/adminmenu/js/philosophy-sanitizer.mjs` – kleine clientseitige HTML-Positivliste.
- `plugin/MGD_AI_Kennzeichnung/adminmenu/js/philosophy-source-sync.mjs` – Synchronisierung von Visual-, HTML- und Formularwert.
- `plugin/MGD_AI_Kennzeichnung/adminmenu/js/philosophy-link-dialog.mjs` – sicherer HTTPS-Linkdialog ohne externe UI.
- `plugin/MGD_AI_Kennzeichnung/adminmenu/js/philosophy-toolbar.mjs` – lokal erzeugte Werkzeugleiste und Formatbefehle.
- `plugin/MGD_AI_Kennzeichnung/adminmenu/js/philosophy-editor.mjs` – Einstiegspunkt und progressive Initialisierung beider Sprachen.
- `plugin/MGD_AI_Kennzeichnung/adminmenu/philosophy.css` – zweispaltige Karten, große Felder, responsive Werkzeugleisten und Fokuszustände.
- `tests/JavaScript/philosophy-sanitizer.test.mjs` – Positivliste, Links und aktive Inhalte.
- `tests/JavaScript/philosophy-source-sync.test.mjs` – Moduswechsel und autoritativer Formularwert.
- `tests/JavaScript/philosophy-toolbar.test.mjs` – Befehle, Linkdialog und Tastaturbedienung.
- `tests/JavaScript/philosophy-editor.test.mjs` – progressive Initialisierung und Formular-Synchronisierung.
- `Dokumentation/Monetarisierung-und-Marketplaces.md` – Geschäftsmodelle und offizielle Plattformregeln.
- `Dokumentation/Release-1.3.0.md` – verständliche Release-, Update- und Rückfallhinweise.

### Bestehend und gezielt zu ändern

- `plugin/MGD_AI_Kennzeichnung/adminmenu/philosophy.php` – ausschließlich lokale Asset-URL an Smarty übergeben.
- `plugin/MGD_AI_Kennzeichnung/adminmenu/templates/philosophy.tpl` – Sprachkarten, Fallback-Textareas und lokale Assets.
- `plugin/MGD_AI_Kennzeichnung/info.xml` – Version 1.3.0 und öffentliche Updatebeschreibung.
- `plugin/MGD_AI_Kennzeichnung/adminmenu/display.php` – lokale Version der Updateprüfung auf 1.3.0.
- `plugin/MGD_AI_Kennzeichnung/Infrastructure/Update/GitHubReleaseChecker.php` – User-Agent 1.3.0.
- `tests/Structure/PhilosophyPortletContractTest.php` – Editor-, Fallback- und Datenschutzvertrag.
- `tests/Structure/DocumentationAndReleaseTest.php` – Version, öffentliche Dokumentation und Releasepaket.
- `tests/Integration/Admin/AdminEntryPointTest.php` – lokale Assetzuweisung des Philosophie-Tabs.
- `README.md`, `README.en.md`, `CHANGELOG.md`, `SECURITY.md`, `Dokumentation/README.md`, einschlägige `wiki/`-Seiten – Bedienung, Datenschutz, Updateweg und Geschäftsmodell.
- `scripts/build-release.sh`, `scripts/README.md`, `.github/workflows/quality.yml` – Release 1.3.0 und reproduzierbares ZIP.

---

### Task 1: Serverseitigen Vertrag für lokale Editor-Assets und No-JavaScript-Fallback festschreiben

**Files:**
- Modify: `tests/Structure/PhilosophyPortletContractTest.php`
- Modify: `tests/Integration/Admin/AdminEntryPointTest.php`
- Modify: `plugin/MGD_AI_Kennzeichnung/adminmenu/philosophy.php`
- Modify: `plugin/MGD_AI_Kennzeichnung/adminmenu/templates/philosophy.tpl`
- Create: `plugin/MGD_AI_Kennzeichnung/adminmenu/philosophy.css`

- [ ] **Step 1: Den fehlenden Strukturvertrag zuerst rot testen**

Ergänze `PhilosophyPortletContractTest` um einen Test, der lokale Assets, gestapelte Sprachbereiche und unveränderte Textareas fordert:

```php
#[Test]
public function philosophie_editor_ist_lokal_progressiv_und_ohne_externe_assets(): void
{
    $template = (string) file_get_contents(self::ROOT . '/adminmenu/templates/philosophy.tpl');
    $css = (string) file_get_contents(self::ROOT . '/adminmenu/philosophy.css');

    self::assertStringContainsString('class="mgd-philosophy-language"', $template);
    self::assertStringContainsString('name="content_de"', $template);
    self::assertStringContainsString('name="content_en"', $template);
    self::assertStringContainsString('philosophy.css', $template);
    self::assertStringContainsString('js/philosophy-editor.mjs', $template);
    self::assertStringContainsString('type="module"', $template);
    self::assertDoesNotMatchRegularExpression('~(?:src|href)="https?://~i', $template);
    self::assertStringContainsString('min-height: 22.5rem', $css);
}
```

Erweitere den Entry-Point-Test so, dass Smarty `adminUrl` aus `getPaths()->getAdminURL()` erhält und keine fremde URL zugewiesen wird.

- [ ] **Step 2: Rotlauf bestätigen**

Run:

```bash
vendor/bin/phpunit tests/Structure/PhilosophyPortletContractTest.php \
  tests/Integration/Admin/AdminEntryPointTest.php
```

Expected: FAIL, weil CSS, Modulreferenz, Sprachkarten und `adminUrl` noch fehlen.

- [ ] **Step 3: Den minimalen lokalen Template-Vertrag umsetzen**

Weise in `philosophy.php` nur den bekannten Pluginpfad zu:

```php
->assign('adminUrl', rtrim($oPlugin->getPaths()->getAdminURL(), '/') . '/')
```

Baue das Formular semantisch um. Die Textareas bleiben sichtbar und vollständig nutzbar, bis JavaScript den jeweiligen Editor erfolgreich initialisiert:

```smarty
<link rel="stylesheet" href="{$adminUrl|escape:'html':'UTF-8'}philosophy.css">
<form method="post" class="mgd-philosophy-form" data-philosophy-form>
    {* bestehende versteckte Felder unverändert *}
    <section class="mgd-philosophy-language" data-philosophy-language="de">
        <h2>Deutsch</h2>
        <label for="mgd-ai-philosophy-de">Deutscher Inhalt</label>
        <textarea id="mgd-ai-philosophy-de" name="content_de" rows="18"
            data-philosophy-source>{$contentDe|escape:'html':'UTF-8'}</textarea>
    </section>
    <section class="mgd-philosophy-language" data-philosophy-language="en">
        <h2>Englisch</h2>
        <label for="mgd-ai-philosophy-en">Englischer Inhalt</label>
        <textarea id="mgd-ai-philosophy-en" name="content_en" rows="18"
            data-philosophy-source>{$contentEn|escape:'html':'UTF-8'}</textarea>
    </section>
    <button type="submit">Beide Sprachfassungen speichern</button>
</form>
<script type="module" src="{$adminUrl|escape:'html':'UTF-8'}js/philosophy-editor.mjs"></script>
```

Die Textareas stehen durch `display: block`, `width: 100%` und `min-height: 22.5rem` untereinander. Setze lesbare Fokusrahmen, große Klickflächen und einen Einspalten-Fallback; Editor-Werkzeugleisten dürfen erst ab ausreichend Platz umbrechen.

- [ ] **Step 4: Fokusprüfungen grün ausführen und committen**

```bash
vendor/bin/phpunit tests/Structure/PhilosophyPortletContractTest.php \
  tests/Integration/Admin/AdminEntryPointTest.php
git add plugin/MGD_AI_Kennzeichnung/adminmenu/philosophy.php \
  plugin/MGD_AI_Kennzeichnung/adminmenu/templates/philosophy.tpl \
  plugin/MGD_AI_Kennzeichnung/adminmenu/philosophy.css \
  tests/Structure/PhilosophyPortletContractTest.php \
  tests/Integration/Admin/AdminEntryPointTest.php
git commit -m "feat: bereitet lokalen Philosophie-Editor vor"
```

Expected: PASS.

### Task 2: Clientseitigen Sanitizer spiegelgleich und fail-closed entwickeln

**Files:**
- Create: `tests/JavaScript/philosophy-sanitizer.test.mjs`
- Create: `plugin/MGD_AI_Kennzeichnung/adminmenu/js/philosophy-sanitizer.mjs`
- Reference: `plugin/MGD_AI_Kennzeichnung/Service/PhilosophySanitizer.php`

- [ ] **Step 1: Gefährliche und erlaubte Eingaben zuerst testen**

Die Tests müssen mindestens diese Fälle abdecken:

```js
assert.equal(
  sanitizePhilosophyHtml('<h2 style="color:red">Hallo</h2><script>alert(1)</script>'),
  '<h2>Hallo</h2>',
);
assert.equal(
  sanitizePhilosophyHtml('<a href="https://example.org/path" onclick="x()">Text</a>'),
  '<a href="https://example.org/path" rel="noopener noreferrer">Text</a>',
);
assert.equal(sanitizePhilosophyHtml('<a href="javascript:alert(1)">Text</a>'), 'Text');
assert.equal(sanitizePhilosophyHtml('<a href="https://user:pass@example.org">Text</a>'), 'Text');
assert.equal(sanitizePhilosophyHtml('<img src=x onerror=alert(1)>Text'), 'Text');
```

Prüfe außerdem HTTP, fremde Ports, `iframe`, `svg`, `style`, Kommentare, Ereignisattribute, unbekannte Format-Tags und Nullbytes.

- [ ] **Step 2: Erwarteten Rotlauf bestätigen**

```bash
node --test tests/JavaScript/philosophy-sanitizer.test.mjs
```

Expected: FAIL wegen des noch fehlenden Moduls.

- [ ] **Step 3: Kleine Positivliste ohne `innerHTML`-Vertrauen implementieren**

Exportiere reine, testbare Funktionen:

```js
export const ALLOWED_PHILOSOPHY_ELEMENTS = new Set([
  'p', 'h2', 'h3', 'ul', 'ol', 'li', 'strong', 'em', 'a',
]);

export function isSafeHttpsUrl(value) {
  try {
    const url = new URL(value);
    return url.protocol === 'https:'
      && url.username === ''
      && url.password === ''
      && (url.port === '' || url.port === '443');
  } catch {
    return false;
  }
}
```

Parse ausschließlich mit dem injizierbaren `DOMParser`. Erzeuge die bereinigte Ausgabe in einem separaten Dokument durch `createElement`, `createTextNode` und `append`; kopiere nie beliebige Attribute. Entferne den gesamten Inhalt aktiver Elemente. Unbekannte passive Elemente werden ausgewickelt. Begrenze die Eingabe auf 10.000 Zeichen wie der PHP-Sanitizer.

- [ ] **Step 4: JavaScript-Sicherheitstest grün ausführen und committen**

```bash
node --test tests/JavaScript/philosophy-sanitizer.test.mjs
git add plugin/MGD_AI_Kennzeichnung/adminmenu/js/philosophy-sanitizer.mjs \
  tests/JavaScript/philosophy-sanitizer.test.mjs
git commit -m "feat: ergänzt lokalen Philosophie-Sanitizer"
```

Expected: PASS.

### Task 3: Visual-, HTML- und Formularwert verlustarm synchronisieren

**Files:**
- Create: `tests/JavaScript/philosophy-source-sync.test.mjs`
- Create: `plugin/MGD_AI_Kennzeichnung/adminmenu/js/philosophy-source-sync.mjs`

- [ ] **Step 1: Den Synchronisierungsvertrag zuerst testen**

Nutze kleine DOM-Fakes nach dem Muster der bestehenden JavaScript-Tests. Prüfe:

```js
const sync = createPhilosophySourceSync({ source, visual, html, sanitize });
sync.showVisual();
assert.equal(visual.serializedHtml, '<p>Sicher</p>');

html.value = '<p>Neu <strong>formatiert</strong></p><script>x()</script>';
sync.showVisual();
assert.equal(source.value, '<p>Neu <strong>formatiert</strong></p>');

visual.serializedHtml = '<h2>Titel</h2>';
sync.prepareSubmit();
assert.equal(source.value, '<h2>Titel</h2>');
```

Prüfe auch leeren Inhalt, wiederholte Moduswechsel und zwei vollständig unabhängige Sprachinstanzen.

- [ ] **Step 2: Rotlauf bestätigen**

```bash
node --test tests/JavaScript/philosophy-source-sync.test.mjs
```

Expected: FAIL wegen des fehlenden Moduls.

- [ ] **Step 3: Explizite Zustandsmaschine implementieren**

Das Modul kennt nur `visual` oder `html`. Jeder Wechsel sanitisiert die Quelle, aktualisiert alle drei Repräsentationen und setzt `aria-pressed` außerhalb des Moduls über einen Callback. Beim `submit` wird immer der aktuell sichtbare Modus zuerst synchronisiert. Keine Daten werden in `localStorage`, `sessionStorage`, Cookies oder Netzwerke geschrieben.

```js
export function createPhilosophySourceSync({ source, visual, html, sanitize }) {
  let mode = 'visual';
  const synchronize = () => {
    const raw = mode === 'html' ? html.value : visual.serialize();
    const safe = sanitize(raw);
    source.value = safe;
    html.value = safe;
    visual.render(safe);
    return safe;
  };
  return { showVisual, showHtml, prepareSubmit: synchronize, currentMode: () => mode };
}
```

- [ ] **Step 4: Tests grün ausführen und committen**

```bash
node --test tests/JavaScript/philosophy-source-sync.test.mjs
git add plugin/MGD_AI_Kennzeichnung/adminmenu/js/philosophy-source-sync.mjs \
  tests/JavaScript/philosophy-source-sync.test.mjs
git commit -m "feat: synchronisiert Philosophie-Editor-Modi"
```

### Task 4: Lokale Werkzeugleiste und sicheren HTTPS-Linkdialog bauen

**Files:**
- Create: `tests/JavaScript/philosophy-toolbar.test.mjs`
- Create: `plugin/MGD_AI_Kennzeichnung/adminmenu/js/philosophy-link-dialog.mjs`
- Create: `plugin/MGD_AI_Kennzeichnung/adminmenu/js/philosophy-toolbar.mjs`
- Modify: `plugin/MGD_AI_Kennzeichnung/adminmenu/philosophy.css`

- [ ] **Step 1: Bedien- und Sicherheitsvertrag rot testen**

Teste die vollständige, absichtlich kleine Befehlsmenge:

```js
assert.deepEqual(commandIds, [
  'paragraph', 'heading-2', 'heading-3', 'bold', 'italic',
  'unordered-list', 'ordered-list', 'link', 'remove-format', 'undo', 'redo',
]);
```

Fordere echte `<button type="button">`-Elemente, deutschsprachige `aria-label`-Texte, `aria-pressed` für Visual/HTML und keine Iconfonts/SVG-Downloads. Der Linkdialog akzeptiert ausschließlich `https://` ohne Zugangsdaten und ohne fremden Port; Abbrechen verändert die Auswahl nicht.

- [ ] **Step 2: Rotlauf bestätigen**

```bash
node --test tests/JavaScript/philosophy-toolbar.test.mjs
```

Expected: FAIL wegen der fehlenden Module.

- [ ] **Step 3: Befehle über injizierbaren Adapter implementieren**

Verkapsele Browserbefehle, damit der DOM-Code testbar bleibt. Blockbefehle verwenden nur `p`, `h2` oder `h3`; Hervorhebungen nur `strong` und `em`. Nach jedem Befehl wird der Inhalt erneut sanitisiert und der Fokus in den visuellen Bereich zurückgesetzt. Die Schaltflächen verwenden kurze lokale Textzeichen wie `B`, `I`, `H2`, `H3`, `•`, `1.` und erhalten vollständige zugängliche Namen.

Für Links gilt:

```js
const href = normalizeSecureLink(dialogValue);
if (href === null) {
  showInlineError('Bitte verwenden Sie eine vollständige sichere HTTPS-Adresse.');
  return;
}
commands.createLink(href);
```

Verwende einen lokalen `<dialog>` mit Eingabefeld, Fehlertext, „Abbrechen“ und „Link einfügen“. Falls `<dialog>` fehlt, bleibt der Linkbefehl deaktiviert; alle übrigen Funktionen bleiben nutzbar.

- [ ] **Step 4: Fokus-, Kontrast- und Umbruchregeln ergänzen**

Werkzeugleisten müssen per Flex-Wrap umbrechen. Schaltflächen erhalten mindestens 2.75rem Höhe, klaren `:focus-visible`-Ring und sichtbare aktive Zustände. CSS enthält keine `url(http...)`, `@import` oder externe Schriftfamilie.

- [ ] **Step 5: Tests grün ausführen und committen**

```bash
node --test tests/JavaScript/philosophy-toolbar.test.mjs
git add plugin/MGD_AI_Kennzeichnung/adminmenu/js/philosophy-link-dialog.mjs \
  plugin/MGD_AI_Kennzeichnung/adminmenu/js/philosophy-toolbar.mjs \
  plugin/MGD_AI_Kennzeichnung/adminmenu/philosophy.css \
  tests/JavaScript/philosophy-toolbar.test.mjs
git commit -m "feat: ergänzt lokale Philosophie-Werkzeugleiste"
```

### Task 5: Beide Editoren progressiv initialisieren und das Speichern absichern

**Files:**
- Create: `tests/JavaScript/philosophy-editor.test.mjs`
- Create: `plugin/MGD_AI_Kennzeichnung/adminmenu/js/philosophy-editor.mjs`
- Modify: `plugin/MGD_AI_Kennzeichnung/adminmenu/templates/philosophy.tpl`
- Modify: `plugin/MGD_AI_Kennzeichnung/adminmenu/philosophy.css`
- Modify: `tests/Structure/PhilosophyPortletContractTest.php`

- [ ] **Step 1: Initialisierung und Fallback zuerst rot testen**

Prüfe, dass:

- exakt zwei `data-philosophy-language`-Bereiche unabhängig initialisiert werden;
- die Original-Textarea erst nach erfolgreicher Initialisierung visuell verborgen wird;
- ein Fehler in einem Editor den anderen und beide Textareas nicht unbenutzbar macht;
- `paste`, Wechsel in den HTML-Modus und `submit` sanitisiert werden;
- kein `fetch`, `XMLHttpRequest`, `WebSocket`, Storage oder externer Assetpfad vorkommt;
- ein Live-Status „Visuelle Bearbeitung“ beziehungsweise „HTML-Quelltext“ für Screenreader gesetzt wird.

- [ ] **Step 2: Rotlauf bestätigen**

```bash
node --test tests/JavaScript/philosophy-editor.test.mjs
```

Expected: FAIL wegen des fehlenden Einstiegspunkts.

- [ ] **Step 3: Editor-DOM lokal erzeugen**

`initializePhilosophyEditor(root)` erzeugt pro Sprache:

```html
<div class="mgd-philosophy-editor" data-editor-enhancement>
  <div class="mgd-philosophy-toolbar" role="toolbar"></div>
  <div class="mgd-philosophy-visual" contenteditable="true" role="textbox"
       aria-multiline="true"></div>
  <textarea class="mgd-philosophy-html" aria-label="HTML-Quelltext"></textarea>
  <p class="mgd-philosophy-editor-status" role="status" aria-live="polite"></p>
</div>
```

Übernimm nur bereits serverseitig bereinigte Textarea-Inhalte. Beim Einfügen lese `text/html` oder `text/plain`, sanitisiere lokal und füge ausschließlich die bereinigten Knoten ein. Registriere einen einzelnen Formular-Submit-Handler, der beide Instanzen `prepareSubmit()` aufrufen lässt.

- [ ] **Step 4: Strukturtest gegen externe Laufzeitabhängigkeiten verschärfen**

Lies alle neuen Editor-Module zusammen und verbiete mindestens:

```php
self::assertDoesNotMatchRegularExpression('~https?://~i', $allEditorCode);
self::assertDoesNotMatchRegularExpression('/\b(?:fetch|XMLHttpRequest|WebSocket)\b/', $allEditorCode);
self::assertStringNotContainsString('localStorage', $allEditorCode);
self::assertStringNotContainsString('sessionStorage', $allEditorCode);
self::assertStringNotContainsString('@import', $css);
```

Die erlaubte Zeichenfolge `https:` im URL-Prüfer wird im Test gezielt als Protokollvergleich akzeptiert; verboten sind vollständige externe URLs und Netz-APIs.

- [ ] **Step 5: Alle Editor-Tests grün ausführen und committen**

```bash
node --test tests/JavaScript/philosophy-*.test.mjs
vendor/bin/phpunit tests/Structure/PhilosophyPortletContractTest.php \
  tests/Integration/Admin/AdminEntryPointTest.php
git add plugin/MGD_AI_Kennzeichnung/adminmenu/js/philosophy-editor.mjs \
  plugin/MGD_AI_Kennzeichnung/adminmenu/templates/philosophy.tpl \
  plugin/MGD_AI_Kennzeichnung/adminmenu/philosophy.css \
  tests/JavaScript/philosophy-editor.test.mjs \
  tests/Structure/PhilosophyPortletContractTest.php
git commit -m "feat: aktiviert lokalen Philosophie-WYSIWYG-Editor"
```

Expected: PASS.

### Task 6: Benutzerhandbuch, Datenschutz und Monetarisierung nachvollziehbar dokumentieren

**Files:**
- Create: `Dokumentation/Monetarisierung-und-Marketplaces.md`
- Create: `Dokumentation/Release-1.3.0.md`
- Modify: `README.md`
- Modify: `README.en.md`
- Modify: `CHANGELOG.md`
- Modify: `SECURITY.md`
- Modify: `Dokumentation/README.md`
- Modify: relevante Dateien unter `wiki/`
- Modify: `tests/Structure/DocumentationAndReleaseTest.php`

- [ ] **Step 1: Dokumentationsvertrag zuerst rot testen**

Fordere in `DocumentationAndReleaseTest` mindestens:

```php
self::assertFileExists($root . '/Dokumentation/Monetarisierung-und-Marketplaces.md');
self::assertStringContainsString('Version 1.3.0', $changelog);
self::assertStringContainsString('Visuell', $readme);
self::assertStringContainsString('HTML', $readme);
self::assertStringContainsString('keine externen', strtolower($security));
self::assertStringNotContainsString('privates Repository', $updateDocumentation);
```

- [ ] **Step 2: Rotlauf bestätigen**

```bash
vendor/bin/phpunit tests/Structure/DocumentationAndReleaseTest.php
```

Expected: FAIL wegen Version 1.2.1, privater Repository-Hinweise und fehlender Monetarisierungsseite.

- [ ] **Step 3: Editor für Nicht-Programmierer erklären**

Dokumentiere mit kurzen Anleitungen:

1. Deutsch oder Englisch auswählen.
2. Im visuellen Modus schreiben und formatieren.
3. Optional in den HTML-Modus wechseln.
4. Erlaubte Elemente: `p`, `h2`, `h3`, `ul`, `ol`, `li`, `strong`, `em`, `a`.
5. Ausschließlich sichere HTTPS-Links; Scripts, Styles, Bilder, Iframes und fremde Attribute werden entfernt.
6. „Beide Sprachfassungen speichern“ speichert beide Inhalte gemeinsam.
7. Ohne JavaScript bleiben die großen Textfelder vollständig bedienbar.

Erkläre, dass der Editor keinerlei Drittinhalte, Fonts, Icons, Telemetrie oder CDN-Ressourcen lädt.

- [ ] **Step 4: Monetarisierungsmodelle klar von Version 1.3.0 trennen**

Empfehle als risikoarme erste Modelle:

- bezahlte Installation, Einrichtung, Migration und Schulung;
- Wartungs- und Prioritäts-Supportverträge;
- ein späteres separates Pro-Add-on für Freigabeworkflows, Rollen, Audit-Historie, Massenbearbeitung und plattformübergreifende Verwaltung;
- eine eigenständige, inhaltlich substanzielle SaaS-Leistung;
- Beratung zu AI Governance und Kennzeichnungsprozessen;
- Sponsoring und freiwillige Unterstützung.

Halte ausdrücklich fest: Version 1.3.0 enthält keine Lizenzschlüssel, Zahlung, Sperren, Telemetrie oder Pro-Freischaltung.

- [ ] **Step 5: Marketplace-Regeln nur anhand offizieller Primärquellen dokumentieren**

Verlinke direkt und datiere die Prüfung auf 30.08.2026:

- WordPress: `https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/` und `https://developer.wordpress.org/plugins/plugin-basics/including-a-software-license/`. Erläutere: keine Trialware im kostenlosen Directory-Plugin; substanzielle SaaS-Dienste und separates extern vertriebenes Premium-Add-on sind möglich; ein reiner externer Lizenzprüfdienst für lokale Funktionalität ist nicht zulässig.
- Shopify: `https://shopify.dev/docs/apps/launch/distribution`, `https://shopify.dev/docs/apps/launch/billing/shopify-app-pricing/plans` und `https://shopify.dev/docs/apps/launch/billing/manual-pricing/support-one-time-purchases`. Erläutere: App-Store-Abrechnung läuft über Shopify; Custom Distribution ist eng begrenzt und unterstützt Shopify App Billing nicht.
- Shopware: `https://docs.shopware.com/en/account-en/extension-partner/extensions` und `https://docs.shopware.com/en/account-en/extension-partner/sales`. Erläutere: Store-Verkäufe und Lizenzen laufen über Shopware; nachgelagerte Service-/Transaktionskosten können eine schriftliche Technology-Partner-Vereinbarung erfordern.
- JTL: `https://www.jtl-software.de/extension-store/Seller-werden`, `https://guide.jtl-software.com/jtl-shop/shop-erweitern/extension-store/` und `https://www.jtl-software.de/extension-store/faq`. Erläutere: Der Store stellt Checkout und Lizenzverwaltung bereit; öffentliche Unterlagen beantworten externe Lizenzschlüssel für Store-Listings nicht eindeutig, daher vor Umsetzung schriftliche Freigabe unter `extensions@jtl-software.de` einholen.

Kennzeichne diese Auswertung als technische Plattformrecherche, nicht als Rechtsberatung. Unterscheide freie Direktverteilung auf GitHub sauber von einem Listing im jeweiligen Marketplace.

- [ ] **Step 6: Dokumentation testen und committen**

```bash
vendor/bin/phpunit tests/Structure/DocumentationAndReleaseTest.php
git add README.md README.en.md CHANGELOG.md SECURITY.md Dokumentation wiki \
  tests/Structure/DocumentationAndReleaseTest.php
git commit -m "docs: erklärt Editor und nachhaltige Monetarisierung"
```

Expected: PASS.

### Task 7: Plugin und reproduzierbares Paket konsistent auf 1.3.0 anheben

**Files:**
- Modify: `plugin/MGD_AI_Kennzeichnung/info.xml`
- Modify: `plugin/MGD_AI_Kennzeichnung/adminmenu/display.php`
- Modify: `plugin/MGD_AI_Kennzeichnung/Infrastructure/Update/GitHubReleaseChecker.php`
- Modify: `tests/Unit/Infrastructure/GitHubReleaseCheckerTest.php`
- Modify: `tests/Structure/DocumentationAndReleaseTest.php`
- Modify: `scripts/build-release.sh`
- Modify: `scripts/README.md`
- Modify: `.github/workflows/quality.yml`

- [ ] **Step 1: Versionsvertrag zuerst rot auf 1.3.0 stellen**

Fordere überall exakt:

```php
self::assertStringContainsString('<Version>1.3.0</Version>', $infoXml);
self::assertStringContainsString('MGD-AI-Kennzeichnung-JTL-Shop-5/1.3.0', $checker);
self::assertStringContainsString('MGD_AI_Kennzeichnung-1.3.0.zip', $buildScript);
```

- [ ] **Step 2: Rotlauf bestätigen**

```bash
vendor/bin/phpunit tests/Unit/Infrastructure/GitHubReleaseCheckerTest.php \
  tests/Structure/DocumentationAndReleaseTest.php
```

Expected: FAIL mit den bisherigen 1.2.1-Werten.

- [ ] **Step 3: Alle Laufzeit-, Build- und CI-Versionen gemeinsam ändern**

Setze `info.xml`, lokale Updateprüfung, GitHub-User-Agent, ZIP-Dateinamen, CI-Integritätsprüfung und Skriptdokumentation auf 1.3.0. Entferne veraltete Aussagen, nach denen das private Repository anonyme Releaseabfragen verhindert. Behalte die optionale Prüfung, den Zwölf-Stunden-Cache und das Fehlen eines Auto-Installers unverändert bei.

- [ ] **Step 4: Paket zweimal reproduzierbar bauen**

```bash
rm -f dist/MGD_AI_Kennzeichnung-1.3.0.zip
bash scripts/build-release.sh
sha256sum dist/MGD_AI_Kennzeichnung-1.3.0.zip > /tmp/mgd-ai-1.3.0-first.sha256
bash scripts/build-release.sh
sha256sum -c /tmp/mgd-ai-1.3.0-first.sha256
unzip -t dist/MGD_AI_Kennzeichnung-1.3.0.zip
```

Expected: `OK` und keine ZIP-Fehler. Das Löschen betrifft ausschließlich das neu erzeugbare 1.3.0-Artefakt im expliziten `dist`-Pfad.

- [ ] **Step 5: Versionierung committen**

```bash
git add plugin/MGD_AI_Kennzeichnung/info.xml \
  plugin/MGD_AI_Kennzeichnung/adminmenu/display.php \
  plugin/MGD_AI_Kennzeichnung/Infrastructure/Update/GitHubReleaseChecker.php \
  tests/Unit/Infrastructure/GitHubReleaseCheckerTest.php \
  tests/Structure/DocumentationAndReleaseTest.php \
  scripts/build-release.sh scripts/README.md .github/workflows/quality.yml
git commit -m "build: bereitet Release 1.3.0 vor"
```

### Task 8: Gesamte Git-Historie vor der öffentlichen Freigabe auf Geheimnisse prüfen

**Files:**
- Inspect only: all commits, trees and blobs reachable from every local ref
- Do not modify: `/Users/michaelgahn/AKTUELLE PROJEKTE/Onvis-Shop.de/ZoAnUvBiEsRsWhOoRpT/ZoAnUvBiEsRsWhOoRpT.md`

- [ ] **Step 1: Sichtbarkeit und vollständigen Ref-Umfang erfassen**

```bash
gh repo view MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5 \
  --json visibility,isPrivate,defaultBranchRef
git for-each-ref --format='%(refname)' refs/heads refs/remotes refs/tags
git rev-list --objects --all > /tmp/mgd-ai-all-objects.txt
```

Expected: Repository ist noch privat; die Objektliste enthält alle erreichbaren Historienobjekte.

- [ ] **Step 2: Historische Dateinamen fail-closed prüfen**

Suche ohne Inhalte auszugeben nach verdächtigen Pfaden wie `.env`, privaten Schlüsseln, Zugangsdaten, Backups, Dumps, Token- und Passwortdateien. Gib ausschließlich Commit-ID, Pfad und Trefferklasse aus, niemals gefundene Werte:

```bash
git log --all --name-only --pretty=format: \
  | LC_ALL=C sort -u \
  | rg -n -i '(^|/)(\.env|id_(rsa|ed25519)|.*\.(pem|p12|pfx|key|sql|dump)|.*(?:secret|credential|password|token).*)$'
```

Expected: keine Treffer. Bei einem Treffer die Veröffentlichung stoppen und erst eine abgestimmte Historienbereinigung planen.

- [ ] **Step 3: Alle historischen Blobs in einem isolierten temporären Ordner scannen**

Verwende nach Möglichkeit einen lokal verfügbaren etablierten Scanner (`gitleaks detect --source . --log-opts=--all --no-banner --redact`). Falls keiner installiert ist, iteriere alle Blob-IDs aus `git rev-list --objects --all`, prüfe Binärdateien nur auf Dateityp/Name und Textblobs mit redigierenden regulären Ausdrücken für private Schlüssel, GitHub-Tokens, Cloud-Schlüssel, Basic-Auth-URLs, Passwörter und hochentropische Secret-Zuweisungen. Die Ausgabe darf nur Objekt-ID, Pfad und Regelname enthalten.

Expected: keine Findings. Jeder plausible Fund blockiert die Sichtbarkeitsänderung; es wird kein automatisches Umschreiben der Historie vorgenommen.

- [ ] **Step 4: Aktuellen Baum zusätzlich auf personenbezogene oder lokale Betriebsdaten prüfen**

```bash
git ls-files -z | xargs -0 rg -l -i \
  '(BEGIN (RSA |OPENSSH |EC )?PRIVATE KEY|gh[pousr]_[A-Za-z0-9_]{20,}|password\s*[:=]|passwd\s*[:=]|api[_-]?key\s*[:=])'
```

Prüfe Findings manuell und redigiert. Dokumentierte Test-Platzhalter dürfen nur nach nachvollziehbarer Klassifizierung bestehen bleiben.

- [ ] **Step 5: Scan-Ergebnis knapp protokollieren**

Halte Datum, geprüfte Ref-Anzahl, Commit-Anzahl, Blob-Anzahl, eingesetzte Regeln/Scanner und Ergebnis lokal in der Arbeitsnotiz fest. Keine Hashes, Secret-Kandidaten oder private Pfade in öffentliche Release Notes aufnehmen.

### Task 9: Vollständige Qualitätsprüfung, öffentliches Repository und GitHub-Release 1.3.0

**Files:**
- Verify: complete repository
- Create: `dist/MGD_AI_Kennzeichnung-1.3.0.zip`
- Create: `dist/MGD_AI_Kennzeichnung-1.3.0.zip.sha256`

- [ ] **Step 1: Vollständige lokale Qualitätssuite ausführen**

```bash
composer validate --strict
composer test
composer test:js
composer analyse
composer style
bash scripts/build-release.sh
unzip -t dist/MGD_AI_Kennzeichnung-1.3.0.zip
```

Expected: alle Befehle mit Exit-Code 0.

- [ ] **Step 2: Paketinhalt, Fremdbezüge und Arbeitsbaum prüfen**

```bash
unzip -Z1 dist/MGD_AI_Kennzeichnung-1.3.0.zip | LC_ALL=C sort > /tmp/mgd-ai-1.3.0-files.txt
rg -n -i 'https?://|@import|fetch\(|XMLHttpRequest|WebSocket' \
  plugin/MGD_AI_Kennzeichnung/adminmenu/js/philosophy-*.mjs \
  plugin/MGD_AI_Kennzeichnung/adminmenu/philosophy.css \
  plugin/MGD_AI_Kennzeichnung/adminmenu/templates/philosophy.tpl
git status --short
```

Expected: nur der beabsichtigte `https:`-Protokollvergleich im Linkprüfer, keine externe Asset- oder Netzabhängigkeit und kein unbeabsichtigter Arbeitsbaumrest.

- [ ] **Step 3: Optionalen Wissensgraphen aktualisieren**

```bash
if test -f graphify-out/graph.json; then graphify update .; fi
```

Falls dadurch erwartete Graphdateien geändert werden, prüfe und committe sie separat mit `docs: aktualisiert Projektwissensgraph`.

- [ ] **Step 4: Abschließenden Release-Commit erstellen**

```bash
git add -A
git diff --cached --check
git commit -m "release: veröffentlicht Version 1.3.0"
```

Falls nach den vorigen Teil-Commits nichts mehr offen ist, entfällt dieser leere Commit.

- [ ] **Step 5: Branch ohne Force-Push nach `main` übertragen**

```bash
git fetch origin
git rebase origin/main
git push origin HEAD:main
```

Expected: Fast-Forward oder erfolgreicher normaler Push; niemals `--force` verwenden.

- [ ] **Step 6: Repository erst nach bestandenem Historien-Scan öffentlich machen**

```bash
gh repo edit MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5 \
  --visibility public \
  --accept-visibility-change-consequences
gh repo view MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5 \
  --json visibility,isPrivate,url
```

Expected: `visibility` ist `PUBLIC` und `isPrivate` ist `false`. Schlägt der Scan aus Task 8 fehl, darf dieser Schritt nicht ausgeführt werden.

- [ ] **Step 7: Prüfsumme erzeugen, Tag und Release veröffentlichen**

```bash
sha256sum dist/MGD_AI_Kennzeichnung-1.3.0.zip \
  > dist/MGD_AI_Kennzeichnung-1.3.0.zip.sha256
git tag -a v1.3.0 -m "MGD AI-Kennzeichnung JTL-Shop 5 v1.3.0"
git push origin v1.3.0
gh release create v1.3.0 \
  dist/MGD_AI_Kennzeichnung-1.3.0.zip \
  dist/MGD_AI_Kennzeichnung-1.3.0.zip.sha256 \
  --repo MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5 \
  --title "MGD AI-Kennzeichnung JTL-Shop 5 v1.3.0" \
  --notes-file Dokumentation/Release-1.3.0.md
```

Expected: veröffentlichtes Release mit exakt zwei Assets.

- [ ] **Step 8: Veröffentlichung anonym und technisch verifizieren**

```bash
gh release view v1.3.0 \
  --repo MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5 \
  --json isDraft,isPrerelease,tagName,url,assets
curl --fail --silent --show-error \
  https://api.github.com/repos/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/releases/latest \
  | php -r '$j=json_decode(stream_get_contents(STDIN), true); exit(($j["tag_name"] ?? null) === "v1.3.0" ? 0 : 1);'
```

Expected: kein Draft, kein Prerelease, Tag `v1.3.0`, zwei Assets und anonyme API-Abfrage erfolgreich.

- [ ] **Step 9: Ergebnis und bewusste Grenze an Michael übergeben**

Berichte knapp:

- öffentliche Repository-URL und Release-URL;
- Commit- und Tag-ID;
- ZIP-Name und SHA-256-Prüfsumme;
- bestandene PHP-, JavaScript-, Analyse-, Style-, Build- und Historienprüfungen;
- keine extern geladenen Editorbestandteile;
- keine Installation auf `dev.onvis-shop.de` oder `onvis-shop.de` in diesem Auftrag;
- Update im Shop kann anschließend bewusst separat getestet werden.
