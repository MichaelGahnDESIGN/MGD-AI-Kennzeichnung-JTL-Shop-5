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
- Paketbau mit `scripts/build-release.sh` und ZIP-Integrität.

## Sicherung und Rückweg

Vor dem Update eine Sicherung von Dateien und Datenbank anlegen. Bei einer
Störung zuerst das Plugin deaktivieren und die Ursache prüfen. Nicht
deinstallieren: Das könnte Plugin-Daten entfernen. Für einen Rollback die
vorherigen Plugin-Dateien gezielt aus der Sicherung wiederherstellen.
Frühere Release-Pakete bleiben auf GitHub erhalten.
