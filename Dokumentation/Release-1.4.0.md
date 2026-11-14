# Release 1.4.0 – gemeinsame Versionsnummer

## Was sich ändert

Für Sie ändert sich an der Bedienung fast nichts.

- Alle MGD-Themes und -Plugins tragen jetzt dieselbe Versionsnummer 1.4.0.
- Bereich **Updates**: Nach dem Download nennt die Erfolgsmeldung nur noch,
  was wirklich geprüft wurde. Im einfachen Modus sind das die
  SHA-256-Prüfsumme und der Inhalt des Pakets. Eine Signatur wird dort nicht
  geprüft, und das steht jetzt auch nicht mehr in der Meldung.
- Der Update-Client wurde mit dem aktuellen Generator neu erzeugt. Dabei fiel
  ein veralteter Aufruf (`curl_close()`) weg.
- Kennzeichnungen, Bilder und gespeicherte Designwerte bleiben unverändert.
- Es gibt keine neue Datenbankmigration.

## Paket und Sicherheit

Verwenden Sie **MGD_AI_Kennzeichnung-1.4.0.zip** aus den Release-Anhängen,
nicht den automatisch erzeugten Download **Source code (zip)**. Vergleichen
Sie die SHA-256-Prüfsumme mit der beigefügten Datei.

Das Plugin installiert keine Updates automatisch. Das Update erfolgt als
manueller ZIP-Upload im JTL-Plugin-Manager.

## Prüf- und Installationsstand

Geprüft wurde lokal am 14. November 2026:

- alle PHP-Tests (PHPUnit): 528 Tests ohne Fehler;
- JavaScript-Tests: 154 ohne Fehler;
- statische Analyse (PHPStan) und Formatprüfung ohne Befund;
- Smoke-Test des Bereichs Updates (`php tests/update-smoke.php`): 39 Prüfungen
  ohne Fehler, darunter die neue Prüfung der Erfolgsmeldung;
- Paketbau mit `scripts/build-release.sh`: ZIP ohne Fehler, zwei Builds mit
  gleicher Prüfsumme.

Noch **nicht** geprüft: Veröffentlichung, Update über theme.michael-gahn.de
und Update im JTL-Demo-Shop. Diese Schritte folgen erst nach Freigabe.

## Sicherung und Rückweg

Vor dem Update eine Sicherung von Dateien und Datenbank anlegen. Bei einer
Störung zuerst das Plugin deaktivieren und die Ursache prüfen. Nicht
deinstallieren: Das könnte Plugin-Daten entfernen. Für einen Rollback die
vorherigen Plugin-Dateien gezielt aus der Sicherung wiederherstellen.
Frühere Release-Pakete bleiben auf GitHub erhalten.
