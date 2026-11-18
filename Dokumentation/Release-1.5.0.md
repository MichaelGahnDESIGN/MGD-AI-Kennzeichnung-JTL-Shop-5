# Release 1.5.0 – Installation melden

## Was sich ändert

- Neu: Das kostenlose Plugin meldet sich bei theme.michael-gahn.de an.
  Einmal nach Installation, Aktivierung und Update. Danach höchstens einmal
  am Tag, und zwar nur im Administrationsbereich, nie im Shop-Frontend.
- Gesendet werden nur: Plugin-Name, Version, Shopsystem, die Domain des Shops
  und eine zufällige Installations-ID. Keine Kunden-, Bestell- oder
  Besucherdaten, keine Bilder.
- Im Reiter **Updates** gibt es den Schalter **Installation bei Michael Gahn
  DESIGN melden**. Er ist ab Werk an. Wer ihn ausschaltet, löscht damit den
  Eintrag auf der Plattform. Danach sendet das Plugin nichts mehr.
- Das Plugin läuft immer voll weiter, auch wenn die Plattform nicht erreichbar
  ist oder die Meldung ausgeschaltet ist.
- Die Meldung läuft auch dann, wenn die Update-Prüfung ausgeschaltet ist. Die
  Update-Prüfung selbst bleibt aus, bis Sie sie einschalten.
- Kennzeichnungen, Bilder und gespeicherte Designwerte bleiben unverändert.
- Es gibt keine neue Datenbankmigration.

Kurztext in der Plugin-Beschreibung:
„Meldet sich höchstens einmal am Tag bei theme.michael-gahn.de (Plugin, Version, Shopsystem, Domain, zufällige ID). Abschaltbar in den Einstellungen.“

## Paket und Sicherheit

Verwenden Sie **MGD_AI_Kennzeichnung-1.5.0.zip** aus den Release-Anhängen,
nicht den automatisch erzeugten Download **Source code (zip)**. Vergleichen
Sie die SHA-256-Prüfsumme mit der beigefügten Datei.

Das Plugin installiert keine Updates automatisch. Das Update erfolgt als
manueller ZIP-Upload im JTL-Plugin-Manager.

## Prüf- und Installationsstand

Geprüft wurde lokal am 18. November 2026:

- alle PHP-Tests (PHPUnit): 531 Tests ohne Fehler, darunter neue Tests für
  Schalter und Anmeldung (ohne echtes Netz);
- JavaScript-Tests: 154 ohne Fehler;
- statische Analyse (PHPStan) und Formatprüfung ohne Befund;
- Smoke-Test des Bereichs Updates (`php tests/update-smoke.php`): 39 Prüfungen
  ohne Fehler;
- Paketbau mit `scripts/build-release.sh`: ZIP ohne Fehler, zwei Builds mit
  gleicher Prüfsumme.

Install-Client und Update-Client wurden aus dem Plattform-Stand 67d4048 erzeugt
(Hinweistext freigegeben am 18. November 2026).

Veröffentlicht und geprüft am 18. November 2026:

- GitHub-Release v1.5.0 (fertiges Release, kein Prerelease) mit ZIP und
  SHA-256-Datei. In der SHA-256-Datei steht nur der Dateiname;
- ZIP vom Release zurückgeladen: gleiche Prüfsumme wie der lokale Build
  (`e4d92cb4…af976`, 2007566 Byte);
- Prüfsummen-Datei aus diesem ZIP erzeugt und an die Plattform übergeben. Sie
  wird über das Plattform-Repository ausgerollt, nicht direkt auf den Server
  gelegt;
- JTL-Demo-Shop: vorher Plugin-Dateien und Tabellen gesichert, dann mit dem
  JTL-Updater auf 1.5.0 gebracht. Plugin aktiv, Adminmenü vollständig,
  Wartungsmodus unverändert;
- Anmeldung: Beim Update hat sich der Demo-Shop sofort angemeldet und einen
  Schlüssel von theme.michael-gahn.de erhalten. Schalter aus: Installations-ID
  und Schlüssel gelöscht, Status „Meldung ist ausgeschaltet“. Schalter wieder
  an: neue Anmeldung mit neuer Installations-ID, Status „Zuletzt gemeldet“.

## Sicherung und Rückweg

Vor dem Update eine Sicherung von Dateien und Datenbank anlegen. Bei einer
Störung zuerst das Plugin deaktivieren und die Ursache prüfen. Nicht
deinstallieren: Das könnte Plugin-Daten entfernen. Für einen Rollback die
vorherigen Plugin-Dateien gezielt aus der Sicherung wiederherstellen.
Frühere Release-Pakete bleiben auf GitHub erhalten.
