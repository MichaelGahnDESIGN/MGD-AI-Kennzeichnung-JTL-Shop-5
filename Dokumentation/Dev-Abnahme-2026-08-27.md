# Dev-Abnahme der Version 1.1.1 vom 27. August 2026

## Zweck der Patchversion

Version 1.1.1 korrigiert ausschließlich die Positionierung sichtbarer
KI-Kennzeichnungen im Shop-Frontend. Die gespeicherten Status-, Positions- und
Darstellungswerte sowie die Originalbilder bleiben unverändert.

Behoben werden zwei konkrete Ausgabefälle:

- Normale und verlinkte Bilder einschließlich responsiver `<picture>`-Ausgaben
  erhalten einen gültigen Positionsrahmen außerhalb des `<picture>`-Elements.
- Lokale OPC-Bilder, die als CSS-Hintergrund oder über `data-image-src`
  ausgegeben werden, erhalten die Kennzeichnung direkt im zugehörigen
  Container.

## Sicherheits- und Rückfallgrenzen

- Entwicklung und Installation erfolgen ausschließlich auf
  `dev.onvis-shop.de`.
- `onvis-shop.de` ist nicht Bestandteil dieser Abnahme und wird nicht
  verändert.
- Das Release enthält keine Zugangsdaten, externen Ressourcen oder
  personenbezogenen Testdaten.
- Vor dem Dev-Update werden Pluginverzeichnis und die vier eigenen
  `xplugin_mgd_ai_*`-Tabellen gesichert.
- Bei einem Fehler wird die Dev-Version deaktiviert und die datierte Sicherung
  zurückgespielt; JTL-Core und OnvisTheme bleiben unangetastet.

## Lokale Prüfbasis

Der Branch `codex/jtl-inline-label-positioning` startete auf dem dokumentierten
Stand `26f6b8c`. Vor der Änderung bestanden 423 PHP-Tests mit 13.214
Assertions. Die neuen Regressionstests prüfen sichere Selektoren, responsive
Bildrahmen, Linkrahmen, OPC-Hintergründe und den Schutz vor doppelten Labels.

Die lokale Qualitätsprüfung vom 27. August 2026 ergab:

- Composer-Metadaten: strikt gültig;
- PHPUnit: 427 Tests und 13.239 Assertions ohne Fehler oder Fehlschlag;
- JavaScript: 13 Tests ohne Fehlschlag;
- PHPStan: 185 Dateien ohne Fehler;
- PHP CS Fixer: 185 Dateien ohne erforderliche Änderung;
- ZIP-Integritätsprüfung: keine Fehler im komprimierten Inhalt.

## Releasepaket

Paket: `dist/MGD_AI_Kennzeichnung-1.1.1.zip`

SHA-256:
`6628ac33d2437273ddd1548375c71eaaa58810f805a27e4dac6c1588f3235cce`

## Dev-Laufzeitprüfung

Vor dem Update wurde unter
`BACKUPS/MGD_AI_Kennzeichnung/20260827-224903-vor-1.1.1-inline` eine getrennte,
per SHA-256 geprüfte Sicherung des Pluginverzeichnisses und der vier eigenen
Plugin-Tabellen angelegt. Kunden-, Bestell- und Zahlungsdaten waren nicht Teil
dieser Sicherung.

Die Installation wurde über JTLs regulären Plugin-Installer als Update auf
Version 1.1.1 ausgeführt. Das Plugin blieb anschließend aktiv. Shop-,
Sprach- und Plugin-Caches wurden geleert.

Nach dem Update bestanden weiterhin:

- 714 verwaltete Bilder;
- 1.704 gespeicherte Fundstellen;
- vier sichtbare Kennzeichnungen;
- bei allen vier sichtbaren Kennzeichnungen der redaktionell gewählte Status
  `generated`, die Position `bottom-right` und die Darstellung `dark`.

Ein Laufzeittest mit der von JTL-Shop 5.7.2 eingesetzten phpQuery-Version
bestätigte:

- verlinkte responsive Bilder erhalten genau einen äußeren Bildrahmen;
- das Label wird nicht als ungültiges Kindelement in `<picture>` eingefügt;
- statische OPC-Hintergrundbilder erhalten genau ein Label;
- bestehende Links und Bildquellen bleiben unverändert.

Die visuelle Prüfung der Dev-Startseite bestätigte das dunkel dargestellte
Label **„KI-GENERIERT“** unten rechts innerhalb der Karte
**„Info & Werbetafeln“**. Die Position entsprach damit der tatsächlich
gespeicherten Einstellung. Die übrigen gekennzeichneten OPC-Bilder wurden im
zugänglichen Seiteninhalt ebenfalls mit barrierearmer Beschreibung erkannt.

Der öffentliche Dev-Aufruf blieb wegen des beabsichtigten Wartungsmodus bei
HTTP 503. Plugin-CSS und Plugin-JavaScript waren über HTTPS mit HTTP 200
erreichbar. `onvis-shop.de` blieb während der gesamten Abnahme unverändert.
