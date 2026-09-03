# Detail-Lupe: lokaler Einbau und Abnahme

Stand der ursprünglichen lokalen Abnahme: 3. September 2026, Entwicklungszweig
`codex/detail-lupe`. Der folgende Bericht hält diesen damaligen Stand fest.
Die anschließende Integration auf `main`, Versionierung und Shop-Abnahme werden
getrennt in [Release 1.3.9](Release-1.3.9.md) fortgeschrieben.

## Ergebnis und Abgrenzung

Die freigegebene separate Detail-Lupe ist in den Darstellungstab eingebaut.
Das unveränderte Schuhbild wird wieder ohne Schachbrettrand angezeigt.
Die zweifach vergrößerte Effektprobe darunter zeigt dasselbe Label auf
farbigen Flächen, feinen Linien und einer Kreisform. Sie ist kein Bildausschnitt.

Dieser Stand wurde **nur lokal** geprüft. Kein Shop wurde verändert, kein
GitHub-Release erstellt und keine veröffentlichte ZIP ersetzt. Der Einbau
ist noch nicht auf `main` integriert. Die Tests erzeugen ausschließlich ein
lokales Testpaket in `dist/`; dessen unveränderte Versionsangabe 1.3.8 macht
es ausdrücklich nicht zu einem neuen Release oder einem Paket zur Weitergabe.

Die veröffentlichte Datei im übergeordneten `plugin/`-Ordner bleibt unverändert:
`MGD_AI_Kennzeichnung-1.3.8.zip`, SHA-256
`aeaf351046009666f4017438d0c81ab9305d58e01c2c13ae4ada0c19188a679e`.

## Architektur und unveränderte Speicherung

- `adminmenu/templates/display-detail-preview.tpl`: eigene Teilansicht mit
  Überschrift, Probe, Kennwerten und Hinweisen. Keine neuen Formularfelder.
- `adminmenu/display-detail-preview.css`: lokale Farben, Linien und Kreis;
  zweifacher CSS-Zoom im Layoutfluss statt einer abgeschnittenen Skalierung.
- `adminmenu/js/display-detail-preview.mjs`: überträgt nur sechs bekannte
  CSS-Variablen, die zulässige Farbschema-Klasse und sicheren Text aus dem
  bereits validierten gemeinsamen Vorschaumodell.
- `display-controls.mjs` versorgt beide Vorschauen. Fehlende optionale
  Lupenelemente unterbrechen die bisherige Produktvorschau nicht.
- Das Produktlabel bleibt an der gewählten Ecke; die Lupe bleibt mittig.
  Die Kennwerte unter ihr bleiben tatsächliche Prozent-/Pixel-Einstellungen.
- Die sieben gespeicherten Einstellungen, Servervalidierung, Formularaktion
  und CSRF-Prüfung wurden nicht verändert. Keine Datenbankmigration.
- Die alte Muster-CSS und ihr überholter Strukturtest wurden ersetzt;
  frühere Fassungen bleiben über Git wiederherstellbar.
- Drei geänderte Ressourcen tragen SHA-256-Präfixe als Cachekennung. Der
  Strukturtest prüft die Kennungen gegen die tatsächlichen Dateiinhalte.

Es wurden keine Pakete installiert, keine Bildgenerierung oder kostenpflichtige
API genutzt und keine CI-Ausführung gestartet. Der Browser lädt für diese
Vorschau nur vorhandene lokale Plugin-Ressourcen.

## Automatische Prüfungen

Die Tests wurden vor dem Einbau um die neuen Anforderungen ergänzt:
Die Detailsteuerung hatte zunächst 10 erwartete Fehler, die neuen
Template-/CSS-Verträge zunächst fünf. Nach dem Einbau waren sie grün.

| Prüfung | Ergebnis |
|---|---|
| Gesamte PHPUnit-Suite | 564 Tests, 14.912 Assertions, erfolgreich |
| Gesamte JavaScript-Suite | 154 Tests, erfolgreich |
| PHPStan | Keine Fehler |
| PHP CS Fixer, nur lesender Trockenlauf | 0 von 215 Dateien mit Änderungsbedarf |
| Composer-Metadaten, strikt | Gültig |
| Separate Speicherregression | 15 Tests, 62 Assertions, erfolgreich |
| Neue Detail-Strukturverträge | 5 Tests, 46 Assertions, erfolgreich |
| Bisheriger Darstellungsvertrag | 7 Tests, 376 Assertions, erfolgreich |

Die Pakettests erstellen wiederholt das minimale Installationsarchiv,
prüfen Reproduzierbarkeit und Inhalt: Detail-Template, CSS und JavaScript
sind enthalten; die alte Muster-CSS nicht mehr. Vertrauliche Dateien,
Testserver und Entwicklungswerkzeuge gehören nicht ins Plugin-Paket.

PHP lief lokal mit **8.5.6**. Ein vollständiger Test unter dem Projektminimum
PHP 8.1 wurde nicht durchgeführt. Der Formatlauf war ausschließlich lesend.

## Browserprüfung

Die Loopback-Testhülle `tests/Browser/display-preview-server.mjs` verwendet
das echte Plugin-Template und dessen Produktions-CSS-/JS-Dateien. Nur
bekannte Smarty-Testwerte und der eine feste Detail-Include werden lokal
aufgelöst. Das ersetzt keine Prüfung mit dem echten JTL-Smarty-System.
Die Hülle lehnt POST ab und sperrt per CSP Netzwerkverbindungen und
Formularübermittlung. Sie enthält keine Shopzugänge oder Kundendaten.

Im Codex-Browser wurden geprüft:

- Standardwerte 8 % Transparenz und 0 px Unschärfe, unverändertes Schuhbild.
- Sichtbarer Vergleich von 0 und 12 px Unschärfe bei 50 % Transparenz:
  Linien hinter dem Label werden weich, die Schrift bleibt scharf.
- 0 und 90 % Transparenz, maximal 24 px Unschärfe; korrekte Kennwerte und
  ein-/ausgeblendeter Hinweis für den undurchsichtigen Hintergrund.
- Deutsch/Englisch und helles/dunkles Label: beide Vorschauen gleich.
- Alle vier Bildecken: jeweils 8 px Abstand zu den gewählten Seiten;
  die Detailprobe bleibt in beiden Achsen zentriert.
- Ein echter 360-px-iframe: Dokumentbreite genau 360 px, Vorschaubreite und
  Scrollbreite beide 286 px, beide Labels vollständig innerhalb ihrer Boxen.
- Dieselbe Breite mit allen oberen Grenzen zugleich: Schrift 48 px,
  Außenabstand 64 px, Innenabstand und Radius 32 px, Unschärfe 24 px,
  Transparenz 90 %. Kein horizontaler Seitenüberlauf; die Lupenbox wächst
  mit dem umgebrochenen Label. Gemessener CSS-Zoom: 2.

Die Browserprüfung ist keine separate Safari-/Firefox-Abnahme und kein
Live-Speichertest. Der Hinweis für fehlendes `backdrop-filter` ist als
lokale CSS-Rückfallebene implementiert und strukturell geprüft, nicht in
einem alten Browser ohne Filterunterstützung praktisch nachgestellt.

Im Browserprotokoll stand einmal ein `MutationObserver.observe`-Fehler ohne
Dateiangabe. Die geladenen `display-*`-Module und die Testhülle verwenden
keinen MutationObserver. Nach vollständigem Neuladen und erneutem Verstellen
der Regler kam kein neuer Eintrag hinzu; beide Ansichten blieben bedienbar.
Die Herkunft des einmaligen Eintrags ist nicht belegt. Es wurde deshalb
kein unbegründeter Eingriff in den Plugin-Code vorgenommen.

## Review und weiterer Betrieb

Ein unabhängiger Spezifikationsreview bestätigt die Übereinstimmung von
Template, Darstellung, Steuerung, Sicherheitsgrenzen und Tests. Das
anschließende Qualitätsreview meldet ebenfalls keine offenen Befunde und
bewertet den Stand als integrationsbereit. Beide Reviews unterscheiden
ausdrücklich zwischen lokalem Nachweis und noch ausstehendem Dev-Shop-Test.

Der Wissensgraph wurde mit `graphify update . --no-cluster` rein lokal
aktualisiert: 2.923 Knoten und 5.401 Kanten. Das ist eine Codeanalyse ohne
LLM-/API-Nutzung; Dokumente wurden nicht semantisch neu ausgewertet.

Vor einer Veröffentlichung folgen separat: Integration auf `main`, neue
Versionsnummer und Release-ZIP, Installation auf Dev, echter JTL-Render-
und Speichertest mit unveränderten Werten. Erst danach erfolgt bei Auftrag
ein Update der aktiven Kundenshops mit vorhandener Rückfallmöglichkeit.
