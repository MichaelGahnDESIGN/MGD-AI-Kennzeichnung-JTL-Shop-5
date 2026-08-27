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
`a6232fcc279d2c25245e54ff1eebfa61aa5691636e95d7138a0ad0089a771ba3`

## Dev-Laufzeitprüfung

Geprüft werden die markierten Airlineschienen-, Garten-, Werbemittel- und
Kategorie-Bilder auf der Dev-Startseite. Die gespeicherte Position ist jeweils
`top-right`, die Darstellung `dark`.

Die Laufzeitprüfung beginnt erst nach der datierten Dev-Sicherung und wird in
einem eigenen Abschlusscommit dokumentiert. Dieser Zwischenstand behauptet
noch keine erfolgreiche Installation oder visuelle Abnahme.
