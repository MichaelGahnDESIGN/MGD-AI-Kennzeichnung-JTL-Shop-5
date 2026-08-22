# JTL-Bildgalerie und OPC-Kennzeichnung – Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Die Version 1.1.0 ersetzt die technische Bildtabelle durch eine zugängliche Galerie und ermöglicht dieselbe sichere KI-Kennzeichnung direkt in der Galerie, in unterstützten OPC-Bilddialogen und ergänzend im JTL-Dateimanager.

**Architecture:** Die vorhandene servergerenderte Admin-Anwendung bleibt bestehen. Neue kleine PHP-Dienste erzeugen ausschließlich erlaubte lokale Vorschau-URLs und kapseln das Laden beziehungsweise Speichern einer Kennzeichnung. Ein eng begrenzter, von JTL authentifizierter Admin-IO-Endpunkt stellt diese Logik dem OPC zur Verfügung. Kleine JavaScript-Module kümmern sich getrennt um Dialog, Live-Vorschau, OPC-Erkennung und die fehlertolerante elFinder-Erweiterung. JTL-Core-Dateien und Bilddateien bleiben unverändert.

**Tech Stack:** PHP 8.1, JTL-Shop 5.7.2, PHPUnit 10, PHPStan Level max, PHP-CS-Fixer, JavaScript ES-Module, Node.js-Bordmitteltests, HTML/CSS, Git/GitHub.

---

## Arbeitsregeln für die Ausführung

- Jede Aufgabe beginnt mit einem fehlschlagenden Test und endet mit einem kleinen Commit.
- Der vorhandene unversionierte Stammordner `MGD_AI_Kennzeichnung-1.0.0.zip` wird weder überschrieben noch gelöscht.
- `.superpowers/`, Zugangsdaten, Dumps, Backups und temporäre Dateien gelangen nicht in Git oder das Release-ZIP.
- Das Dev-System wird vor dem Live-System aktualisiert. Ohne vollständig bestandene Dev-Abnahme gibt es keinen Live-Rollout.
- Die Zugangsdaten-Datei im Onvis-Projekt wird ausschließlich gelesen und niemals verändert.
- Es werden keine JTL-Core-Dateien verändert und keine Bilder oder Pfade an externe Dienste übertragen.

### Aufgabe 1: Releaseziel 1.1.0 und saubere Artefaktgrenzen

**Dateien:**
- Ändern: `.gitignore`
- Ändern: `plugin/MGD_AI_Kennzeichnung/info.xml`
- Ändern: `scripts/build-release.sh`
- Ändern: `tests/Structure/DocumentationAndReleaseTest.php`
- Ändern: `tests/Structure/PluginContractTest.php`

- [ ] In `DocumentationAndReleaseTest` zuerst die neue Zielversion und den eindeutigen Paketpfad festschreiben:

```php
public function test_release_script_builds_only_version_1_1_0_into_dist(): void
{
    $script = file_get_contents(__DIR__ . '/../../scripts/build-release.sh');
    self::assertIsString($script);
    self::assertStringContainsString('MGD_AI_Kennzeichnung-1.1.0.zip', $script);
    self::assertStringNotContainsString('../MGD_AI_Kennzeichnung-1.0.0.zip', $script);
}
```

- [ ] Den fokussierten Test ausführen und das erwartete Rot wegen Version 1.0.0 beobachten:

```bash
./vendor/bin/phpunit tests/Structure/DocumentationAndReleaseTest.php tests/Structure/PluginContractTest.php
```

- [ ] `info.xml` auf `1.1.0` setzen, `build-release.sh` auf `dist/MGD_AI_Kennzeichnung-1.1.0.zip` umstellen und `.superpowers/`, `*.sql`, `*.bak`, `.env*` sowie lokale SSH-Hilfsdateien explizit ignorieren.
- [ ] Im Buildskript vor dem Packen ausschließlich den exakten neuen `dist`-Zielpfad entfernen. Keine Wildcards und kein Löschen im Projektstamm verwenden.
- [ ] Tests erneut ausführen und anschließend prüfen:

```bash
git check-ignore .superpowers/visual-companion.html
git diff --check
```

- [ ] Commit erstellen:

```bash
git add .gitignore plugin/MGD_AI_Kennzeichnung/info.xml scripts/build-release.sh tests/Structure/DocumentationAndReleaseTest.php tests/Structure/PluginContractTest.php
git commit -m "chore: bereitet sichere Version 1.1.0 vor"
```

### Aufgabe 2: Portlet nach „Custom Portlets“ verschieben

**Dateien:**
- Ändern: `plugin/MGD_AI_Kennzeichnung/Portlets/AIPhilosophie/AIPhilosophie.php`
- Ändern: `plugin/MGD_AI_Kennzeichnung/info.xml`
- Ändern: `tests/Structure/PhilosophyPortletContractTest.php`

- [ ] Den Strukturtest so ändern, dass PHP und XML exakt `Custom Portlets` fordern:

```php
public function test_philosophy_portlet_is_grouped_with_custom_portlets(): void
{
    $php = file_get_contents(self::PLUGIN . '/Portlets/AIPhilosophie/AIPhilosophie.php');
    $xml = file_get_contents(self::PLUGIN . '/info.xml');

    self::assertIsString($php);
    self::assertIsString($xml);
    self::assertStringContainsString("protected string \$group = 'Custom Portlets';", $php);
    self::assertStringContainsString('<Group>Custom Portlets</Group>', $xml);
}
```

- [ ] Test ausführen und das erwartete Rot für die bisherige Gruppe `content` bestätigen.
- [ ] Die Gruppe in beiden Produktionsdateien ändern; Name, Template und bestehende Portlet-Ausgabe unverändert lassen.
- [ ] Test grün ausführen:

```bash
./vendor/bin/phpunit tests/Structure/PhilosophyPortletContractTest.php
```

- [ ] Commit erstellen:

```bash
git add plugin/MGD_AI_Kennzeichnung/Portlets/AIPhilosophie/AIPhilosophie.php plugin/MGD_AI_Kennzeichnung/info.xml tests/Structure/PhilosophyPortletContractTest.php
git commit -m "feat: ordnet AI-Philosophie den Custom Portlets zu"
```

### Aufgabe 3: Sichere lokale Vorschau-URLs und deutsche Anzeigenamen

**Dateien:**
- Neu: `plugin/MGD_AI_Kennzeichnung/Admin/Presentation/LocalPreviewUrlResolver.php`
- Neu: `plugin/MGD_AI_Kennzeichnung/Admin/Presentation/AssetDisplayMapper.php`
- Neu: `tests/Unit/Admin/Presentation/LocalPreviewUrlResolverTest.php`
- Neu: `tests/Unit/Admin/Presentation/AssetDisplayMapperTest.php`

- [ ] Parametrisierte Tests für erlaubte JTL-Bildwurzeln anlegen: `/media/image/`, `/bilder/`, `/opc/`, `/templates/` nur soweit dort tatsächlich lokal auswählbare Bilddateien liegen. Erlaubt sind ausschließlich `jpg`, `jpeg`, `png`, `gif`, `webp`, `avif` und `svg`.
- [ ] Ablehnungstests für `http:`, `https:`, `data:`, `javascript:`, `//host`, `..`, `%2e%2e`, Nullbytes, Backslashes, leere Pfade und Nicht-Bilddateien schreiben.
- [ ] Folgende öffentliche Grenze festschreiben:

```php
final class LocalPreviewUrlResolver
{
    /** Liefert für einen erlaubten lokalen Bildpfad eine same-origin URL oder null. */
    public function resolve(string $localPath, string $shopBaseUrl): ?string;

    /** Prüft dieselben Regeln ohne eine URL zu erzeugen. */
    public function accepts(string $localPath): bool;
}
```

- [ ] Mapper-Tests für alle Werte aus `LabelStatus`, `AssetSource`, `LabelPosition` und `LabelTheme` schreiben. Unbekannte Quellen müssen als `Unbekannt` erscheinen, ohne Rohwert in HTML einzuschleusen.
- [ ] Beide Tests ausführen und das erwartete Rot wegen fehlender Klassen beobachten.
- [ ] Resolver mit segmentweiser `rawurlencode`-Kodierung implementieren. Vor der Prüfung genau einmal URL-dekodieren und jede Traversierung oder ein Schema strikt ablehnen. Kein physischer Serverpfad darf angenommen oder ausgegeben werden.
- [ ] `AssetDisplayMapper` mit expliziten `match`-Ausdrücken und deutschen Rückgabewerten implementieren.
- [ ] Tests grün ausführen:

```bash
./vendor/bin/phpunit tests/Unit/Admin/Presentation
```

- [ ] Commit erstellen:

```bash
git add plugin/MGD_AI_Kennzeichnung/Admin/Presentation tests/Unit/Admin/Presentation
git commit -m "feat: erzeugt sichere lokale Bildvorschauen"
```

### Aufgabe 4: Listen- und Detaildaten für Galeriekarten erweitern

**Dateien:**
- Ändern: `plugin/MGD_AI_Kennzeichnung/Admin/Port/AdminAssetRepositoryInterface.php`
- Ändern: `plugin/MGD_AI_Kennzeichnung/Infrastructure/Database/AssetRepository.php`
- Ändern: `plugin/MGD_AI_Kennzeichnung/Admin/Action/AssetListAction.php`
- Ändern: `plugin/MGD_AI_Kennzeichnung/Admin/Action/AssetDetailAction.php`
- Ändern: `plugin/MGD_AI_Kennzeichnung/Admin/ViewModel/AssetListView.php`
- Neu: `plugin/MGD_AI_Kennzeichnung/Admin/ViewModel/AssetCardView.php`
- Ändern: `plugin/MGD_AI_Kennzeichnung/Admin/Factory/AdminRuntimeFactory.php`
- Ändern: `tests/Integration/Infrastructure/AdminAssetRepositoryTest.php`
- Neu: `tests/Unit/Admin/AssetListActionTest.php`
- Ändern: `tests/Unit/Admin/AdminRuntimeFactoryTest.php`

- [ ] Repository-Tests schreiben, die für Liste und Detail `source`, `updated_at`, `status`, `position`, `theme`, `usage_count` und `local_path` erwarten.
- [ ] Action-Test mit Fake-Repository schreiben. Er muss beweisen, dass die View ausschließlich fertige `AssetCardView`-Objekte mit sicherer `previewUrl`, deutschem `statusLabel`, deutschem `sourceLabel` und gekürztem `fileName` enthält.
- [ ] Erwartetes Rot wegen fehlender Felder und View-Klasse beobachten.
- [ ] `AssetCardView` als unveränderliches Objekt implementieren:

```php
final class AssetCardView
{
    public function __construct(
        public readonly int $id,
        public readonly string $fileName,
        public readonly ?string $previewUrl,
        public readonly string $status,
        public readonly string $statusLabel,
        public readonly string $sourceLabel,
        public readonly string $position,
        public readonly string $theme,
        public readonly int $usageCount,
    ) {}
}
```

- [ ] SQL-Auswahl um die benötigten vorhandenen Spalten erweitern; alle Filter, Sortier-Allowlist und gebundenen Parameter beibehalten.
- [ ] Die Actions erhalten Resolver und Mapper über den Konstruktor. Sie dürfen keine URL aus ungeprüften Querydaten bilden.
- [ ] Fokussierte Tests grün ausführen:

```bash
./vendor/bin/phpunit tests/Integration/Infrastructure/AdminAssetRepositoryTest.php tests/Unit/Admin/AssetListActionTest.php
```

- [ ] Commit erstellen:

```bash
git add plugin/MGD_AI_Kennzeichnung/Admin tests/Integration/Infrastructure/AdminAssetRepositoryTest.php tests/Unit/Admin/AssetListActionTest.php
git commit -m "feat: liefert sichere Daten für Galeriekarten"
```

### Aufgabe 5: Gemeinsamer Dienst für lokale Einzelkennzeichnungen

**Dateien:**
- Neu: `plugin/MGD_AI_Kennzeichnung/Admin/Service/LocalAssetLabelService.php`
- Neu: `plugin/MGD_AI_Kennzeichnung/Admin/Value/LocalAssetLabel.php`
- Ändern: `plugin/MGD_AI_Kennzeichnung/Admin/Port/AdminAssetRepositoryInterface.php`
- Ändern: `plugin/MGD_AI_Kennzeichnung/Infrastructure/Database/AssetRepository.php`
- Neu: `tests/Unit/Admin/LocalAssetLabelServiceTest.php`
- Ändern: `tests/Integration/Infrastructure/AdminAssetRepositoryTest.php`

- [ ] Tests für `load()` und `save()` schreiben. Sie müssen folgende Fälle abdecken: bekannter Pfad, unbekannter erlaubter Pfad, ungültiger Pfad, ungültiger Enumwert, fehlende Berechtigung und identisches wiederholtes Speichern.
- [ ] Festlegen, dass `load()` unbekannte erlaubte Bilder nur als ungespeicherten Default zurückgibt und keine Datenbankmutation ausführt.
- [ ] Festlegen, dass `save()` ein unbekanntes Bild atomar als Quelle `opc` oder `custom-local-manual` anlegt und danach die geprüften Werte speichert.
- [ ] Die Schnittstelle im Repository ergänzen:

```php
/** @return array<string, scalar|null>|null */
public function findByLocalPath(string $localPath): ?array;

public function ensureLocalAsset(string $localPath, string $source): int;
```

- [ ] Erwartetes Rot beobachten:

```bash
./vendor/bin/phpunit tests/Unit/Admin/LocalAssetLabelServiceTest.php tests/Integration/Infrastructure/AdminAssetRepositoryTest.php
```

- [ ] `LocalAssetLabelService` implementieren. Reihenfolge: Berechtigung prüfen, Pfad normalisieren, Vorschau-Allowlist prüfen, Enumwerte validieren, erst danach Repository aufrufen. Keine Anfragewerte loggen.
- [ ] Für `ensureLocalAsset()` den bestehenden kanonischen Asset-Key und eine transaktionssichere Upsert-Strategie wiederverwenden. Der Pfad wird mit führendem `/` normalisiert, nicht als externe URL gespeichert.
- [ ] Tests grün ausführen und Commit erstellen:

```bash
git add plugin/MGD_AI_Kennzeichnung/Admin plugin/MGD_AI_Kennzeichnung/Infrastructure/Database/AssetRepository.php tests/Unit/Admin/LocalAssetLabelServiceTest.php tests/Integration/Infrastructure/AdminAssetRepositoryTest.php
git commit -m "feat: vereinheitlicht lokale Einzelkennzeichnungen"
```

### Aufgabe 6: Geschützte JTL-Admin-IO-Grenze

**Dateien:**
- Neu: `plugin/MGD_AI_Kennzeichnung/Admin/IO/AdminIoRegistration.php`
- Neu: `plugin/MGD_AI_Kennzeichnung/Admin/IO/LoadLocalAssetLabel.php`
- Neu: `plugin/MGD_AI_Kennzeichnung/Admin/IO/SaveLocalAssetLabel.php`
- Neu: `plugin/MGD_AI_Kennzeichnung/Admin/IO/AdminIoResponse.php`
- Ändern: `plugin/MGD_AI_Kennzeichnung/Bootstrap.php`
- Ändern: `tests/Stubs/JtlPluginStubs.php`
- Neu: `tests/Unit/Admin/AdminIoRegistrationTest.php`
- Neu: `tests/Unit/Admin/AdminIoActionTest.php`
- Ändern: `tests/Integration/Frontend/BootstrapFrontendTest.php`

- [ ] JTL-Stubs minimal um `Shop::isFrontend()` und die tatsächlich verwendete `AdminIO::register()`-Signatur ergänzen.
- [ ] Registrierungstest schreiben: Backend registriert ausschließlich `mgd_ai_label_load` und `mgd_ai_label_save` am Hook `HOOK_IO_HANDLE_REQUEST_ADMIN`; Frontend registriert weiterhin nur die vorhandene Frontendintegration.
- [ ] Action-Tests schreiben für scalare, begrenzte Eingaben und JSON-Antworten mit `ok`, `code`, `message`, `data`. Fehlermeldungen dürfen weder SQL, Stacktrace noch Pfade aus nicht akzeptierten Eingaben enthalten.
- [ ] Erwartetes Rot beobachten:

```bash
./vendor/bin/phpunit tests/Unit/Admin/AdminIoRegistrationTest.php tests/Unit/Admin/AdminIoActionTest.php tests/Integration/Frontend/BootstrapFrontendTest.php
```

- [ ] `Bootstrap::boot()` nach Frontend und Backend trennen. Im Backend nur den Admin-Hook registrieren; die eigentliche `AdminIO::register()`-Ausführung erfolgt innerhalb des JTL-Hooks.
- [ ] Bei der Registrierung dieselbe Pluginberechtigung wie in der zentralen Bildverwaltung angeben. Auf JTLs bereits geprüfte Admin-Session und `jtl_token` vertrauen, zusätzlich die vorhandene Pluginberechtigung im Dienst prüfen.
- [ ] IO-Actions dürfen nur exakt benötigte Felder lesen: `localPath`, `source`, `status`, `position`, `theme`. Arrays, überlange Werte und zusätzliche verschachtelte Daten werden abgelehnt.
- [ ] Ausnahmen an der IO-Grenze in allgemeine Fehlercodes übersetzen; keine sensiblen Werte protokollieren.
- [ ] Tests grün ausführen und Commit erstellen:

```bash
git add plugin/MGD_AI_Kennzeichnung/Bootstrap.php plugin/MGD_AI_Kennzeichnung/Admin/IO tests/Stubs/JtlPluginStubs.php tests/Unit/Admin/AdminIoRegistrationTest.php tests/Unit/Admin/AdminIoActionTest.php tests/Integration/Frontend/BootstrapFrontendTest.php
git commit -m "feat: schützt Kennzeichnungen über JTL Admin-IO"
```

### Aufgabe 7: Responsive servergerenderte Galerie

**Dateien:**
- Neu: `plugin/MGD_AI_Kennzeichnung/adminmenu/templates/partials/asset-filter.php`
- Neu: `plugin/MGD_AI_Kennzeichnung/adminmenu/templates/partials/asset-card.php`
- Neu: `plugin/MGD_AI_Kennzeichnung/adminmenu/templates/partials/gallery-toolbar.php`
- Neu: `plugin/MGD_AI_Kennzeichnung/adminmenu/templates/partials/label-dialog.php`
- Ändern: `plugin/MGD_AI_Kennzeichnung/adminmenu/templates/assets-list.php`
- Neu: `plugin/MGD_AI_Kennzeichnung/adminmenu/assets.css`
- Ändern: `plugin/MGD_AI_Kennzeichnung/Admin/Action/AdminActionHandler.php`
- Ändern: `plugin/MGD_AI_Kennzeichnung/Admin/Factory/AdminRuntimeFactory.php`
- Ändern: `tests/Structure/AdminTemplateContractTest.php`

- [ ] Strukturtests zuerst auf die vereinbarte Oberfläche umstellen: Filter bleiben vollständig, Ergebniszahl und Scanaktion sind sichtbar, Karten nutzen `loading="lazy"`, Auswahlboxen haben eindeutige Labels, Status hat Text, der Dialog besitzt `role="dialog"`, `aria-modal="true"` und einen expliziten Button `Kennzeichnung speichern`.
- [ ] Test muss außerdem verhindern, dass der vollständige lokale Pfad in jeder Galeriekarte ausgegeben wird.
- [ ] Erwartetes Rot beobachten:

```bash
./vendor/bin/phpunit tests/Structure/AdminTemplateContractTest.php
```

- [ ] Die vier Partials erstellen. Sämtliche dynamischen Werte mit `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')` ausgeben. Vorschauen bekommen feste Breite/Höhe und einen neutralen Fehlerzustand.
- [ ] CSS als responsive Grid-Struktur implementieren: mindestens 1 Spalte mobil, 2 auf Tablet, 3–5 auf Desktop; kein horizontales Überlaufen durch lange Namen.
- [ ] Status nie nur über Farbe darstellen. Fokusrahmen, ausreichende Kontraste und `prefers-reduced-motion` berücksichtigen.
- [ ] `AdminActionHandler` liefert zusätzlich `assetStyleUrl`; die Factory baut die URL ausschließlich aus `getAdminURL()`.
- [ ] Strukturtest grün ausführen, PHP-Templates linten und Commit erstellen:

```bash
./vendor/bin/phpunit tests/Structure/AdminTemplateContractTest.php
find plugin/MGD_AI_Kennzeichnung/adminmenu/templates -name '*.php' -print0 | xargs -0 -n1 php -l
git add plugin/MGD_AI_Kennzeichnung/adminmenu plugin/MGD_AI_Kennzeichnung/Admin/Action/AdminActionHandler.php plugin/MGD_AI_Kennzeichnung/Admin/Factory/AdminRuntimeFactory.php tests/Structure/AdminTemplateContractTest.php
git commit -m "feat: zeigt Bildkennzeichnungen als responsive Galerie"
```

### Aufgabe 8: Galeriedialog, Live-Vorschau und Auswahlleiste

**Dateien:**
- Neu: `plugin/MGD_AI_Kennzeichnung/adminmenu/js/label-preview.mjs`
- Neu: `plugin/MGD_AI_Kennzeichnung/adminmenu/js/label-dialog.mjs`
- Neu: `plugin/MGD_AI_Kennzeichnung/adminmenu/js/gallery-selection.mjs`
- Ändern: `plugin/MGD_AI_Kennzeichnung/adminmenu/assets.js`
- Neu: `tests/JavaScript/label-preview.test.mjs`
- Neu: `tests/JavaScript/gallery-selection.test.mjs`
- Ändern: `composer.json`

- [ ] In `composer.json` ein reproduzierbares Skript ohne externe JavaScript-Abhängigkeit ergänzen:

```json
"test:js": "node --test tests/JavaScript/*.test.mjs"
```

- [ ] Pure-Funktion-Tests für die CSS-Klassen der drei Darstellungen und vier Positionen schreiben. Unbekannte Werte müssen einen Fehler liefern, nicht in Klassennamen übernommen werden.
- [ ] Auswahltest für leere, einzelne und mehrere eindeutige numerische IDs schreiben; doppelte und ungültige IDs werden verworfen.
- [ ] Rote Tests beobachten:

```bash
composer test:js
```

- [ ] `label-preview.mjs` ohne DOM-Seiteneffekte implementieren und von `label-dialog.mjs` verwenden. Der Dialog aktualisiert nur eine Vorschaukopie; gespeichert wird ausschließlich bei `Kennzeichnung speichern`.
- [ ] Dialogverhalten implementieren: Fokus beim Öffnen setzen, Fokusfalle, Escape schließt, Abbrechen entfernt Vorschau, nach Schließen Fokus zurückgeben, Speichern während Anfrage sperren.
- [ ] `gallery-selection.mjs` aktualisiert Zähler und Sammelaktionsleiste. Die bestehende serverseitige Bulk-Vorschau bleibt maßgeblich und zeigt vor Ausführung die Zusammenfassung.
- [ ] `assets.js` bleibt der kleine Einstieg und importiert die drei Module genau einmal über URLs aus `data-*`-Attributen. Keine Inline-Skripte und keine externe Bibliothek verwenden.
- [ ] JavaScript-Tests grün ausführen und Commit erstellen:

```bash
composer test:js
git add composer.json plugin/MGD_AI_Kennzeichnung/adminmenu tests/JavaScript
git commit -m "feat: ergänzt Kennzeichnungsdialog mit Live-Vorschau"
```

### Aufgabe 9: Bestehende Einzel- und Stapelaktionen mit der Galerie verbinden

**Dateien:**
- Ändern: `plugin/MGD_AI_Kennzeichnung/adminmenu/templates/partials/label-dialog.php`
- Ändern: `plugin/MGD_AI_Kennzeichnung/adminmenu/templates/partials/gallery-toolbar.php`
- Ändern: `plugin/MGD_AI_Kennzeichnung/adminmenu/js/label-dialog.mjs`
- Ändern: `plugin/MGD_AI_Kennzeichnung/Admin/Http/AdminRequestNormalizer.php`
- Ändern: `tests/Unit/Admin/AdminRequestNormalizerTest.php`
- Ändern: `tests/Unit/Admin/BulkUpdateActionTest.php`
- Neu: `tests/JavaScript/label-dialog.test.mjs`

- [ ] Normalizer-Regressionstests für exakt einen numerischen Bilddatensatz, feste Enums und die bestehende serverseitige Bulk-Bestätigung ergänzen.
- [ ] JavaScript-Test schreiben: `Kennzeichnung speichern` erzeugt genau eine Anfrage; Doppelklick erzeugt keine zweite; ein Fehler verändert die Kartendaten nicht; Erfolg aktualisiert Status und schließt den Dialog.
- [ ] Erwartetes Rot beobachten.
- [ ] Einzelkennzeichnung als bestehendes CSRF-geschütztes POST-Formular anbinden. Es darf kein neuer ungeschützter Admin-URL-Endpunkt entstehen.
- [ ] Stapelaktion weiterhin über `bulk-preview` und einmaligen Bestätigungstoken führen. Die Galerie übergibt nur ausgewählte IDs und feste Maskenwerte.
- [ ] Verständliche `aria-live`-Meldungen für Erfolg, Validierungsfehler, abgelaufene Sitzung und Serverfehler ergänzen.
- [ ] Tests grün ausführen:

```bash
./vendor/bin/phpunit tests/Unit/Admin/AdminRequestNormalizerTest.php tests/Unit/Admin/BulkUpdateActionTest.php
composer test:js
```

- [ ] Commit erstellen:

```bash
git add plugin/MGD_AI_Kennzeichnung/adminmenu plugin/MGD_AI_Kennzeichnung/Admin/Http/AdminRequestNormalizer.php tests/Unit/Admin/AdminRequestNormalizerTest.php tests/Unit/Admin/BulkUpdateActionTest.php tests/JavaScript/label-dialog.test.mjs
git commit -m "feat: verbindet Galerie mit sicheren Speicheraktionen"
```

### Aufgabe 10: Offizielle OPC-Initialisierung und Bildfelderkennung

**Dateien:**
- Neu: `plugin/MGD_AI_Kennzeichnung/Portlets/AIPhilosophie/editor_init.js`
- Neu: `plugin/MGD_AI_Kennzeichnung/Portlets/AIPhilosophie/editor/admin-io-client.mjs`
- Neu: `plugin/MGD_AI_Kennzeichnung/Portlets/AIPhilosophie/editor/image-field-detector.mjs`
- Neu: `plugin/MGD_AI_Kennzeichnung/Portlets/AIPhilosophie/editor/opc-integration.mjs`
- Neu: `plugin/MGD_AI_Kennzeichnung/Portlets/AIPhilosophie/editor/label-dialog.mjs`
- Neu: `plugin/MGD_AI_Kennzeichnung/Portlets/AIPhilosophie/editor/label-preview.mjs`
- Neu: `plugin/MGD_AI_Kennzeichnung/Portlets/AIPhilosophie/editor/editor.css`
- Neu: `tests/JavaScript/opc-image-field-detector.test.mjs`
- Ändern: `tests/Structure/PhilosophyPortletContractTest.php`

- [ ] Strukturtest schreiben, dass `editor_init.js` vorhanden ist, nur lokale Module lädt und weder fremde URLs noch `eval`, `innerHTML` mit Serverdaten oder Zugangsdaten enthält.
- [ ] Pure-Funktion-Tests für unterstützte Bildfelder schreiben: Bild-Portlet, statisches Container-Hintergrundbild, Banner/Bilderslider-URL-Felder. Externe, leere oder mehrdeutige Werte werden nicht angeboten.
- [ ] Erwartetes Rot beobachten:

```bash
./vendor/bin/phpunit tests/Structure/PhilosophyPortletContractTest.php
composer test:js
```

- [ ] `editor_init.js` als idempotenten Einstieg implementieren. Er ermittelt seine eigene lokale Basis-URL über `document.currentScript.src` und importiert nur `./editor/opc-integration.mjs`.
- [ ] `image-field-detector.mjs` liest ausschließlich sichtbare JTL-Bildfelder und normalisiert sie clientseitig vor. Die endgültige Sicherheitsentscheidung bleibt beim PHP-Dienst.
- [ ] `opc-integration.mjs` beobachtet nur den geöffneten Konfigurationsdialog, setzt je Bild genau einen Button `KI-Kennzeichnung bearbeiten` ein und räumt Observer/Listener beim Schließen auf.
- [ ] Vor jedem Öffnen des Kennzeichnungsdialogs den aktuellen versteckten Bildwert erneut lesen. Dadurch wird nie versehentlich das zuvor ausgewählte Bild gespeichert.
- [ ] `admin-io-client.mjs` sendet ausschließlich same-origin an JTLs Admin-IO-URL und fügt den vorhandenen `jtl_token` hinzu. Keine Tokens loggen oder persistent speichern.
- [ ] Dialog und Vorschau verwenden dieselben erlaubten Status-/Positions-/Darstellungswerte wie die Galerie, aber eigene Dateien für die OPC-Lebensdauer.
- [ ] Tests grün ausführen und Commit erstellen:

```bash
./vendor/bin/phpunit tests/Structure/PhilosophyPortletContractTest.php
composer test:js
git add plugin/MGD_AI_Kennzeichnung/Portlets/AIPhilosophie tests/JavaScript/opc-image-field-detector.test.mjs tests/Structure/PhilosophyPortletContractTest.php
git commit -m "feat: kennzeichnet Bilder direkt im OPC-Dialog"
```

### Aufgabe 11: Fehlertolerantes Dateimanager-Kontextmenü

**Dateien:**
- Neu: `plugin/MGD_AI_Kennzeichnung/Portlets/AIPhilosophie/editor/file-manager-compatibility.mjs`
- Neu: `plugin/MGD_AI_Kennzeichnung/Portlets/AIPhilosophie/editor/file-manager-integration.mjs`
- Ändern: `plugin/MGD_AI_Kennzeichnung/Portlets/AIPhilosophie/editor/opc-integration.mjs`
- Neu: `tests/JavaScript/file-manager-compatibility.test.mjs`
- Neu: `tests/JavaScript/file-manager-integration.test.mjs`

- [ ] Pure-Funktion-Tests für die Kompatibilitätsgrenze schreiben: nur same-origin Fenster, eindeutig erkanntes elFinder, genau eine ausgewählte lokale Bilddatei, keine Ordner, keine Nicht-Bilder, keine externen URLs.
- [ ] Integrationsnahe DOM-Fakes testen: unbekannte Struktur ergibt `false` und null Mutationen; wiederholte Initialisierung erzeugt genau einen Menüpunkt; Fensterschließen beendet Beobachter.
- [ ] Erwartetes Rot beobachten:

```bash
composer test:js
```

- [ ] `file-manager-compatibility.mjs` als einzige Datei mit den versionsabhängigen Selektoren und Prüfungen implementieren. So bleibt eine spätere JTL-Anpassung klar isoliert.
- [ ] Kontextmenüpunkt ausschließlich nach erfolgreicher vollständiger Kompatibilitätsprüfung einfügen. Bei jeder Ausnahme sofort beenden und keine bestehende elFinder-Funktion verändern.
- [ ] Die ausgewählte Datei über die offizielle sichtbare Auswahl auslesen, in einen lokalen Shop-Pfad übersetzen und serverseitig erneut validieren lassen.
- [ ] Keine JTL- oder elFinder-Core-Datei patchen, überschreiben oder ersetzen.
- [ ] Tests grün ausführen und Commit erstellen:

```bash
composer test:js
git add plugin/MGD_AI_Kennzeichnung/Portlets/AIPhilosophie/editor tests/JavaScript/file-manager-compatibility.test.mjs tests/JavaScript/file-manager-integration.test.mjs
git commit -m "feat: ergänzt optionale Dateimanager-Kennzeichnung"
```

### Aufgabe 12: Dokumentation, Changelog und Releasepaket

**Dateien:**
- Ändern: `README.md`
- Ändern: `CHANGELOG.md`
- Ändern: `docs/ADMIN-BILDVERWALTUNG.md`
- Ändern: `docs/INSTALLATION.md`
- Neu: `docs/OPC-KENNZEICHNUNG.md`
- Neu: `docs/ROLLBACK-1.1.0.md`
- Ändern: `scripts/README.md`
- Ändern: `tests/Structure/DocumentationAndReleaseTest.php`

- [ ] Dokumentationstest zuerst um Version 1.1.0, Galeriebedienung, expliziten Speichern-Button, Kompatibilitätsgrenze des Dateimanagers, Dev-vor-Live und Rollback-Anleitung erweitern.
- [ ] Erwartetes Rot beobachten.
- [ ] Deutsche, nicht-technische Bedienanleitung schreiben: Bilder neu scannen, filtern, Karte öffnen, Live-Vorschau prüfen, speichern, Stapelbestätigung, OPC-Dialog, Dateimanager-Fallback.
- [ ] Sicherheitsdokumentation ergänzen: lokale Daten, keine Bildänderung, keine externen Übertragungen, Admin/CSRF, keine automatische KI-Erkennung.
- [ ] Rollback eindeutig dokumentieren: Plugin 1.1.0 deaktivieren, gesichertes Pluginverzeichnis 1.0.0 wiederherstellen, Datenbanktabellen nicht löschen, Caches leeren, Funktionsprüfung. Keine Deinstallation mit Datenlöschung als Rollback empfehlen.
- [ ] Test grün ausführen und Paket bauen:

```bash
./vendor/bin/phpunit tests/Structure/DocumentationAndReleaseTest.php
bash scripts/build-release.sh
unzip -l dist/MGD_AI_Kennzeichnung-1.1.0.zip
```

- [ ] Sicherstellen, dass ZIP-Wurzel, `info.xml`, Module, CSS und Dokumentation enthalten sind, aber weder `.git`, `.superpowers`, Tests, lokale ZIPs, Backups noch Secrets.
- [ ] Commit erstellen:

```bash
git add README.md CHANGELOG.md docs scripts/README.md tests/Structure/DocumentationAndReleaseTest.php
git commit -m "docs: erklärt Bildgalerie und OPC-Kennzeichnung"
```

### Aufgabe 13: Vollständige lokale Qualitäts- und Sicherheitsprüfung

**Dateien:**
- Bei Fehlern nur die jeweils verantwortliche Datei und den zugehörigen Test ändern.

- [ ] Alle PHP- und JavaScript-Tests frisch ausführen:

```bash
composer test
composer test:js
```

- [ ] Statische Analyse, Stil und PHP-Syntax ausführen:

```bash
composer analyse
composer style
find plugin/MGD_AI_Kennzeichnung -name '*.php' -print0 | xargs -0 -n1 php -l
```

- [ ] Build erneut erzeugen und Inhalt prüfen:

```bash
bash scripts/build-release.sh
unzip -t dist/MGD_AI_Kennzeichnung-1.1.0.zip
unzip -l dist/MGD_AI_Kennzeichnung-1.1.0.zip
```

- [ ] Geheimnis- und Datenschutzprüfung ausführen. Treffer nur bewerten, nicht blind ersetzen:

```bash
git grep -nEi '(password|passwd|secret|token|api[_-]?key|private[_-]?key|ssh-rsa|BEGIN [A-Z ]*PRIVATE KEY)'
git status --short
git diff --check
```

- [ ] Prüfen, dass keine Core-Datei, kein Bild und kein unversioniertes Stamm-ZIP verändert wurde. `git status` darf weiterhin nur bewusst ignorierte oder bereits zuvor vorhandene lokale Artefakte zeigen.
- [ ] Falls eine Prüfung fehlschlägt: kleinsten verantwortlichen Fehler korrigieren, fokussierten Test und danach die vollständige Prüfgruppe erneut ausführen.
- [ ] Abschlusscommit nur dann erstellen, wenn nach den vorherigen Commits noch notwendige Korrekturen vorhanden sind. Dafür jede korrigierte Datei einzeln anhand von `git status --short` auswählen, erneut prüfen und anschließend mit der Meldung `fix: schließt Qualitätsprüfung für Version 1.1.0 ab` committen. Unversionierte oder sachfremde Dateien bleiben unberührt.

### Aufgabe 14: Gesicherte Installation und Abnahme auf dev.onvis-shop.de

**Dateien/Systeme:**
- Paket: `dist/MGD_AI_Kennzeichnung-1.1.0.zip`
- Ziel: ausschließlich `dev.onvis-shop.de`
- Ablage: serverseitiges Backup außerhalb des aktiven Pluginverzeichnisses und außerhalb des Webroots, soweit der Hoster dies erlaubt

- [ ] SSH-Ziel und beide Shop-Pfade aus der unveränderten Zugangsdaten-Datei nur lesend ermitteln. Vor jeder Mutation mit `pwd`, `realpath` und JTL-`config.JTL-Shop.ini.php` bestätigen, dass Dev eine eigene Datenbank verwendet und der Wartungsmodus aktiv ist. Secrets nicht ausgeben.
- [ ] Vorhandenes Dev-Pluginverzeichnis und die vier Plugin-Tabellen mit datierter, nicht öffentlich erreichbarer Ablage sichern. Dateirechte der Sicherung begrenzen.
- [ ] SHA-256 des lokalen ZIP berechnen und nach Upload auf dem Server vergleichen.
- [ ] Version 1.1.0 ausschließlich nach Dev übertragen. Wenn JTLs Plugin-Manager ein Update anbietet, diesen Weg nutzen; andernfalls das vorhandene Pluginverzeichnis atomar sichern und die geprüften Paketdateien kontrolliert ersetzen. Keine Core-Dateien anfassen.
- [ ] JTL- und Template-Caches leeren, ohne Sessions, Bestellungen oder Produktdaten zu löschen.
- [ ] Im Dev-Admin prüfen:
  1. Plugin aktiv, Version 1.1.0, keine Installationsprobleme;
  2. Portlet unter „Custom Portlets“;
  3. Filter, Pagination, Galerie, Platzhalter und Details;
  4. Einzeldialog: alle Status, vier Positionen, drei Darstellungen, Abbrechen ohne Speicherung;
  5. Stapeldialog mit Zusammenfassung und atomarer Speicherung;
  6. OPC-Bild-Portlet und statischer Containerhintergrund;
  7. Banner/Slider, soweit die Pfade eindeutig sind;
  8. Dateimanager mit einem Bild, Nicht-Bilddatei und Mehrfachauswahl;
  9. Frontend-Ausgabe der gespeicherten Kennzeichnung;
  10. Wartungsmodus und fehlende Wawi-Anbindung unverändert.
- [ ] Browserkonsole, PHP-/JTL-Logs und Netzwerkantworten auf Fehler prüfen. In Nachweisen keine Tokens, Cookies, E-Mail-Adressen oder Pfade mit personenbezogenen Daten übernehmen.
- [ ] Bei einem Fehler Rollback aus `docs/ROLLBACK-1.1.0.md` durchführen und den Live-Rollout stoppen.
- [ ] Bei vollständigem Erfolg den Dev-Abnahmenachweis mit Datum, Shopversion, Pluginversion, Prüfpunkten und anonymisierten Ergebnissen in `docs/DEV-ABNAHME-1.1.0.md` festhalten und committen:

```bash
git add docs/DEV-ABNAHME-1.1.0.md
git commit -m "docs: dokumentiert Dev-Abnahme der Version 1.1.0"
```

### Aufgabe 15: Integration in main und GitHub-Push

**Voraussetzung:** Aufgabe 13 vollständig grün und Aufgabe 14 vollständig bestanden.

- [ ] Vor dem Branchwechsel Status, Remote und Commitfolge prüfen:

```bash
git status --short --branch
git log --oneline --decorate -12
git remote -v
```

- [ ] Remote-Stand holen, ohne lokale Änderungen zu überschreiben:

```bash
git fetch origin --prune
```

- [ ] Lokalen `main` aus `origin/main` erstellen beziehungsweise aktualisieren. Bei Divergenz oder Konflikt stoppen und erst die genaue Ursache prüfen; kein Force-Push.
- [ ] Den vollständig verifizierten Feature-Stand nachvollziehbar in `main` integrieren. Danach auf `main` alle Befehle aus Aufgabe 13 erneut frisch ausführen.
- [ ] Nur bei erneut grünen Prüfungen pushen:

```bash
git push origin main
```

- [ ] GitHub-Stand anhand der ausgegebenen Commit-ID prüfen. Keine Zugangsdaten oder Artefakte nachträglich hinzufügen.

### Aufgabe 16: Gesicherter Live-Rollout und Smoke-Test

**Voraussetzung:** GitHub-`main` entspricht exakt dem geprüften Commit und Dev-Abnahme ist dokumentiert.

- [ ] Live-Ziel mit `realpath`, Domainbezug und Datenbankname eindeutig von Dev unterscheiden. Bei jeder Unklarheit stoppen.
- [ ] Aktives Live-Pluginverzeichnis und die vier Plugin-Tabellen vor der Änderung datiert sichern. Prüfen, dass das Backup lesbar und nicht öffentlich erreichbar ist.
- [ ] Exakt das bereits auf Dev geprüfte ZIP anhand derselben SHA-256-Prüfsumme hochladen. Kein erneuter lokaler Build nach der Dev-Abnahme.
- [ ] Update kontrolliert durchführen und anschließend nur Plugin-/Template-Caches leeren. Keine Bestellungen, Kunden, Sessions oder Wawi-Daten verändern.
- [ ] Live-Smoke-Test ohne echte Bestellung:
  1. Shop-Startseite und mindestens eine Produktseite laden;
  2. Admin-Bildgalerie öffnen und lesend filtern;
  3. eine vorher festgelegte, unkritische Testkennzeichnung speichern und Frontenddarstellung prüfen;
  4. Änderung wieder auf den dokumentierten Ausgangswert setzen;
  5. OPC- und Dateimanager-Schaltflächen nur auf Vorhandensein und Öffnen prüfen, keine produktive Seite veröffentlichen;
  6. Checkout höchstens bis vor den verbindlichen Bestellbutton prüfen;
  7. Logs auf neue Fehler prüfen.
- [ ] Bei Fehlern sofort Plugin-Rollback durchführen, Caches leeren und die Funktionsfähigkeit des vorherigen Standes prüfen. Die Plugin-Datenbanktabellen nicht löschen.
- [ ] Live-Ergebnis mit Datum, Commit-ID, ZIP-Prüfsumme, anonymisierten Prüfpunkten und Rollbackbereitschaft in `docs/LIVE-ROLLOUT-1.1.0.md` dokumentieren, committen und nach erneuter Kurzprüfung zu `main` pushen.

## Abschlusskriterien

- Die technische Tabelle ist durch eine responsive, bedienbare Galerie ersetzt, ohne Filter oder Pagination zu verlieren.
- Einzel- und Stapelkennzeichnung speichern erst nach ausdrücklicher Bestätigung.
- Der OPC zeigt die Kennzeichnungsaktion bei eindeutig unterstützten lokalen Bildfeldern.
- Der Dateimanager wird nur bei sicher erkannter Struktur ergänzt und bleibt sonst vollständig unangetastet.
- Portlet, Dokumentation und Paket tragen konsistent Version 1.1.0.
- PHP-Tests, JavaScript-Tests, PHPStan, Stil, Syntax und ZIP-Prüfung sind frisch grün.
- Dev wurde gesichert und vollständig abgenommen.
- Erst danach wurden `main`, GitHub und Live mit exakt demselben geprüften Artefakt aktualisiert.
