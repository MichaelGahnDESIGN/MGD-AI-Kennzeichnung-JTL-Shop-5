# Release 1.3.11 – Wartungsversion

## Was sich ändert

Für Sie ändert sich nichts an der Bedienung. Version 1.3.11 ist eine
Wartungsversion ohne Funktionsänderung.

- Der Bereich **Updates** arbeitet wie in 1.3.10.
- Kennzeichnungen, Bilder und gespeicherte Designwerte bleiben unverändert.
- Die Angaben zum Update-Server theme.michael-gahn.de und zum manuellen
  ZIP-Upload in [Datenschutz und Sicherheit](Datenschutz-und-Sicherheit.md)
  sind abgenommen.
- Es gibt keine neue Datenbankmigration.

## Paket und Sicherheit

Verwenden Sie **MGD_AI_Kennzeichnung-1.3.11.zip** aus den Release-Anhängen,
nicht den automatisch erzeugten Download **Source code (zip)**. Vergleichen
Sie die SHA-256-Prüfsumme mit der beigefügten Datei.

Das Plugin installiert keine Updates automatisch. Das Update erfolgt als
manueller ZIP-Upload im JTL-Plugin-Manager.

## Prüf- und Installationsstand

Geprüft wurde lokal am 11. November 2026:

- alle PHP-Tests (PHPUnit);
- Smoke-Test des Bereichs Updates (`php tests/update-smoke.php`);
- Paketbau mit `scripts/build-release.sh` und ZIP-Integrität;
- Ergebnis: 528 PHP-Tests und 37 Smoke-Prüfungen ohne Fehler, zwei Builds
  mit gleicher Prüfsumme.

Veröffentlicht und geprüft am 11. November 2026:

- GitHub-Release v1.3.11 mit ZIP und SHA-256-Datei;
- Prüfsummen-Datei auf theme.michael-gahn.de nennt 1.3.11. Sie wurde aus dem
  vom Release zurückgeladenen ZIP erzeugt;
- vom Server des JTL-Demo-Shops aus: Prüfsummen-Datei abrufbar, ZIP von GitHub
  geladen, Prüfsumme stimmt;
- Plugin-Dateien 1.3.11 liegen im JTL-Demo-Shop unter `plugins/`.

Noch **nicht** geprüft:

- Installation und Aktivierung im JTL-Demo-Shop. Das Plugin war dort vorher
  nicht installiert; die Installation erfolgt von Hand im Plugin-Manager;
- Bereich Updates im Backend des Demo-Shops;
- kein zusätzlicher Lauf unter PHP 8.1.

## Sicherung und Rückweg

Vor dem Update eine Sicherung von Dateien und Datenbank anlegen. Bei einer
Störung zuerst das Plugin deaktivieren und die Ursache prüfen. Nicht
deinstallieren: Das könnte Plugin-Daten entfernen. Für einen Rollback die
vorherigen Plugin-Dateien gezielt aus der Sicherung wiederherstellen.
Frühere Release-Pakete bleiben auf GitHub erhalten.
