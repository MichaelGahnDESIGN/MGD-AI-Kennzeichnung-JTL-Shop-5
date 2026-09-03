# Separate Detail-Lupe – Umsetzungsplan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox syntax for tracking.

**Ziel:** Die am 03.09.2026 freigegebene Detail-Lupe ersetzt den Schachbrettrand, ohne Speichern oder Shopbilder zu verändern.

**Architektur:** Das vorhandene sichere Vorschaumodell versorgt zwei Ansichten. Ein kleines zusätzliches DOM-Modul steuert die optionale Detailprobe. Template und CSS bleiben eigenständig, die Testhülle verwendet echte Plugin-Dateien.

**Technik:** PHP/Smarty, native JavaScript-Module, CSS mit `backdrop-filter`, PHPUnit und Node-Testläufer. Keine neuen Pakete oder externen Ressourcen.

## Aufgabe 1: Gemeinsame Vorschauwerte

Dateien: `adminmenu/js/display-controls.mjs`, neu `adminmenu/js/display-detail-preview.mjs` unter `plugin/MGD_AI_Kennzeichnung/`; Tests in `tests/JavaScript/display-controls.test.mjs`.

- [ ] Vor dem Produktcode Tests ergänzen: Detailansicht initialisiert sich mit denselben sechs CSS-Variablen, Theme und Sprachtext; Input/Change synchronisieren Zahlen und Regler; fehlende Detailansicht blockiert das Produkt nicht; Cleanup entfernt die Listener.
- [ ] `node --test tests/JavaScript/display-controls.test.mjs` ausführen und erwartete fehlende Detailwerte nachweisen.
- [ ] Export `updateDetailPreview(root, model)` implementieren. Vertrag: `[data-mgd-detail-preview]`, darin `[data-mgd-detail-label]`; optionale Werte `[data-mgd-detail-transparency]`, `[data-mgd-detail-blur]`, Hinweis `[data-mgd-detail-opaque]`. Nur Theme-Klassen, keine Positionsklassen übertragen.

```js
// model stammt ausschließlich aus createPreviewModel; keinerlei neue Formulareingaben.
updateDetailPreview(root, model);
// Transparenz für die Anzeige aus dem normalisierten Alpha ableiten:
const transparency = Math.round((1 - Number(model.styles['--mgd-preview-background-opacity'])) * 100);
```

- [ ] Tests für Deutsch/Englisch, 0/90 % Transparenz, 0/24 px Blur, falsche Eingaben, fehlende optionale Elemente und keinen Submit ausführen. Nur `textContent`, feste Klassen und bekannte CSS-Eigenschaften schreiben.
- [ ] Eigene JS-Dateien und Tests nach erfolgreicher Prüfung getrennt committen.

## Aufgabe 2: Template und lokale Darstellung

Dateien: `adminmenu/templates/display.tpl`, neu `adminmenu/templates/display-detail-preview.tpl`, neu `adminmenu/display-detail-preview.css`; `display.css` für überlaufsichere Produktdarstellung; entfallend `display-preview-pattern.css`. Tests: bisheriger `tests/Structure/DisplayPreviewPatternContractTest.php` wird auf den neuen Vertrag umgestellt.

- [ ] Zuerst Verträge für entfernten Mustereinbau, lokalen Template-Include, separate Lupenfläche, zentriertes Label, lokale Ressourcen und Inhalts-Cachekennungen schreiben. `vendor/bin/phpunit tests/Structure/DisplayPreviewPatternContractTest.php` muss gegen den bisherigen Stand rot sein.
- [ ] Produktbild bleibt proportional und ohne Muster; Label und Bild teilen die gleiche Grid-Zelle. Gewählte Ecke und Außenabstand bleiben sichtbar. Das Label darf die Zeilenhöhe bei extremen Werten vergrößern.
- [ ] Template unter dem Produkt einbinden:

```smarty
{include file='./display-detail-preview.tpl'}
```

- [ ] Die Detailprobe verwendet `.mgd-display__label` für identische Farben/Filter. Die Position wird ausschließlich dort zentriert. Die Szene wächst mit dem Label; statt fester Höhe gilt mindestens 180 px. Zweifache Vergrößerung muss auch Zeilenumbruch und die reservierte Fläche verdoppeln.
- [ ] CSS erzeugt diagonale feine Linien über farbigen Flächen und eine Kreisform. Keine `url()`/`@import`-Ressourcen. Ohne `backdrop-filter` zeigt `@supports not` einen Hinweis statt vorgetäuschter Unschärfe.
- [ ] SHA-256-Präfixe der tatsächlichen CSS-/Controller-Dateien im Template eintragen; Strukturtest gleicht sie ab. Die alte CSS-Datei ist nicht mehr eingebunden und nicht mehr im neuen Paket.
- [ ] Strukturtests erneut grün prüfen; Formularaktion, CSRF und sieben persistente Felder bleiben unverändert.

## Aufgabe 3: Browserabnahme, Paket und Dokumentation

Dateien: `tests/Browser/display-preview-server.mjs`, `tests/Browser/display-preview-measure.mjs`, `tests/Structure/DocumentationAndReleaseTest.php`, `Dokumentation/Darstellung.md`, `wiki/Status-und-Darstellung.md`, Abnahmeprotokoll `Dokumentation/Detail-Lupe-Abnahme.md`.

- [ ] Die lokale Testhülle löst nur den festen Detail-Include auf; Asset-Positivliste um CSS und JS ergänzen. POST bleibt 405, CSP bleibt `connect-src 'none'; form-action 'none'`.
- [ ] Über Browsersteuerung Normalansicht, hell/dunkel, vier Ecken, 360-px-iframe und maximale Werte prüfen. Gemessen werden beide Labelrechtecke, Skalierung und Dokumentbreite. 0/50/90 % und 0/12/24 px sowie Sprachwechsel prüfen.
- [ ] `composer test`, `composer test:js`, `composer analyse`, `composer style` und `composer validate --strict` vollständig ausführen. Pakettest auf neue Dateien statt alte Muster-CSS aktualisieren; Buildprüfung erfolgt nur lokal, keine vorhandene veröffentlichte ZIP austauschen.
- [ ] Speicherregression über `tests/Integration/Admin/DisplayEntryPointTest.php` prüfen. Einen nicht durchgeführten echten Shop-Speichertest ausdrücklich vom lokalen Integrationstest unterscheiden.
- [ ] Spezifikationsreview, danach Qualitätsreview durchführen; Befunde vor Abschluss lösen. AST-Wissensgraph kostenfrei aktualisieren, falls Werkzeug verfügbar.
- [ ] Dokumentation unterscheidet den neuen Entwicklungsstand vom veröffentlichten 1.3.8. Bestehende Sicherungen, Live-Shops und Releases bleiben unverändert. Geprüften Entwicklungsstand in Git committen; Integration/Veröffentlichung ist ein nachgelagerter Schritt.
