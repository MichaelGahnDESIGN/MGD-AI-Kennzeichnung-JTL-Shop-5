# Vorschaumuster und Kontrast – Umsetzungsplan

> **Für ausführende Agenten:** Mit `subagent-driven-development` umsetzen. Ein Implementierer bearbeitet den zusammenhängenden CSS-/Template-Schritt; der Hauptagent übernimmt parallel Browserabnahme und Dokumentation. Anschließend Spezifikations- und Qualitätsprüfung.

**Ziel:** Transparenz und Unschärfe anhand eines lokalen Musters sichtbar machen und die Überschrift im dunklen Backend lesbar gestalten.

**Architektur:** Bestehendes Schuhbild unverändert und mittig mit Abstand auf einer gemusterten Vorschaufläche anzeigen. Eigene CSS-Datei für diese Fläche, bestehende CSS-Variablen für das Label weiterverwenden. Header erhält eine helle Fläche mit dunkler Schrift. Keine Speicherung oder PHP-Logik ändern.

**Technik:** Smarty, CSS, vorhandene JavaScript-Module, PHPUnit und lokaler Browser; keine neuen Abhängigkeiten.

## 1. Lokale Umsetzung mit Rot-Grün-Test

Dateien:

- Neu: `plugin/MGD_AI_Kennzeichnung/adminmenu/display-preview-pattern.css`
- Ändern: `plugin/MGD_AI_Kennzeichnung/adminmenu/display.css`
- Ändern: `plugin/MGD_AI_Kennzeichnung/adminmenu/templates/display.tpl`
- Neu: `tests/Structure/DisplayPreviewPatternContractTest.php`

- [ ] Testklasse mit getrennten Tests für lokale Einbindung, Vorschauhinweis und helle Headerfläche erstellen. Kernprüfungen: `assertFileExists($admin . 'display-preview-pattern.css')`, `assertStringContainsString('display-preview-pattern.css', $template)` und `assertStringContainsString('Muster nur zur Vorschau', $template)`. Zusätzlich Muster-CSS auf fehlende `url(`, `@import` und unbeschränkte Selektoren prüfen. Header-Regel muss Hintergrund- und Textfarbe ausdrücklich setzen.
- [ ] `vendor/bin/phpunit --filter DisplayPreviewPatternContractTest` ausführen; fehlendes Muster und Headerfläche müssen den Test rot machen.
- [ ] Eigene Musterdatei nach `display.css` lokal einbinden:

```smarty
<link rel="stylesheet" href="{$adminUrl|escape:'html':'UTF-8'}display-preview-pattern.css">
```

- [ ] CSS-Fläche mit `repeating-conic-gradient(#e9eeeb 0% 25%, #74867b 0% 50%)` und `background-size: 24px 24px` gestalten. Bild mit `width: 100%; height: auto; object-fit: contain` anzeigen. Muster durch Innenabstand sichtbar halten; oben/unten `calc(var(--mgd-preview-outer-margin) + var(--mgd-preview-inner-padding) + var(--mgd-preview-inner-padding) + var(--mgd-preview-font-size) * 1.2 + 1rem)` reservieren, seitlich mindestens `1rem`. Nur `.mgd-display`-Selektoren verwenden, keine neue JavaScript-Logik.
- [ ] Header um `background: var(--mgd-display-surface); color: var(--mgd-display-text); border: 1px solid var(--mgd-display-border); border-radius: .25rem; padding: 1.25rem;` ergänzen. Überschrift ausdrücklich auf die dunkle Textfarbe setzen.
- [ ] Vorschauhinweis direkt nach der Bildfläche ergänzen: „Muster nur zur Vorschau: So erkennst du Transparenz und Hintergrundunschärfe. Deine Shopbilder bleiben unverändert.“ Bestehenden Hinweis zu Position/Farbschema behalten.
- [ ] `vendor/bin/phpunit --filter 'DisplayPreviewPatternContractTest|DisplayAdminContractTest'` ausführen. Änderungen selbst prüfen und gezielt committen; keine anderen Dateien aufnehmen.

## 2. Abnahme und Abschluss

- [ ] Lokale Browserseite aus dem echten Smarty-Template rendern; Produktions-CSS und -JavaScript unverändert laden, keine Shopdaten übernehmen. Nur Loopback-Adresse verwenden.
- [ ] Vier Positionen, helle/dunkle Vorschauthemen, 50 Prozent Transparenz und 0/12 Pixel Unschärfe prüfen. Sichtbare Wirkung und fehlende horizontale Überläufe bei breiter/schmaler Fläche sowie Grenzwerten kontrollieren. Formulareingaben dürfen keine Netzwerkanfragen auslösen.
- [ ] Spezifikationsprüfung gegen den freigegebenen Entwurf durchführen lassen; anschließend separate Qualitätsprüfung. Befunde gezielt beheben und erneut prüfen.
- [ ] Gesamttests ausführen: `vendor/bin/phpunit`, `node --test tests/JavaScript/*.test.mjs`, `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/php-cs-fixer fix --dry-run --diff`, `git diff --check`.
- [ ] `Dokumentation/Darstellung.md` und `CHANGELOG.md` um die tatsächlich geprüften Änderungen und den lokalen Stand ergänzen. Nicht als veröffentlicht oder auf Shops installiert bezeichnen.
- [ ] `env -u GEMINI_API_KEY -u GOOGLE_API_KEY graphify update .` ausführen; ausschließlich lokale AST-Aktualisierung, keine kostenpflichtige API verwenden.
- [ ] Geprüften Stand in Git sichern und Michael die lokale Vorschau sowie den noch unveränderten Shopstand nennen.

## Selbstprüfung

Der Plan deckt den freigegebenen Entwurf ab. Er führt weder einen neuen Schalter noch eine Datenbankänderung, Rasterbildgenerierung, externe Ressource oder automatische Veröffentlichung ein. Falls Browsertests eine unzureichende Musterfläche bei umgebrochenem Label zeigen, ist nur die lokale Vorschaulayout-Regel anzupassen und erneut zu testen.
