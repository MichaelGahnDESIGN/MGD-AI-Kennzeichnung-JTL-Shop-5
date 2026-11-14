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

Veröffentlicht und geprüft am 14. November 2026:

- GitHub-Release v1.4.0 (fertiges Release, kein Prerelease) mit ZIP und
  SHA-256-Datei. In der SHA-256-Datei steht nur der Dateiname;
- ZIP vom Release zurückgeladen: gleiche Prüfsumme wie der lokale Build;
- Prüfsummen-Datei aus diesem ZIP erzeugt und an die Plattform übergeben. Sie
  wird über das Plattform-Repository ausgerollt, nicht direkt auf den Server
  gelegt;
- JTL-Demo-Shop: vorher Plugin-Dateien und Tabellen gesichert, dann mit dem
  JTL-Updater auf 1.4.0 gebracht. Plugin aktiv, Adminmenü vollständig,
  Wartungsmodus unverändert.

- Gegenprobe vom Server des Demo-Shops mit dem echten Update-Client 1.4.0:
  Prüfsummen-Datei auf theme.michael-gahn.de nennt 1.4.0. Als 1.3.11
  eingestellt meldet der Client „Update 1.4.0 verfügbar“; der Download
  landet in der Quarantäne, die Prüfsumme stimmt und die Meldung lautet
  „geprüft (SHA-256-Prüfsumme, Inhalt)“ ohne Signatur. Als 1.4.0 meldet er
  „Kein neueres Update verfügbar“;
- Backend-Ansicht des Plugins im Demo-Shop (angemeldeter Browser, durch den
  Koordinations-Thread): lädt ohne Fehler, alle sechs Reiter vorhanden.

Noch **nicht** geprüft: kein zusätzlicher Lauf unter PHP 8.1.

## Sicherung und Rückweg

Vor dem Update eine Sicherung von Dateien und Datenbank anlegen. Bei einer
Störung zuerst das Plugin deaktivieren und die Ursache prüfen. Nicht
deinstallieren: Das könnte Plugin-Daten entfernen. Für einen Rollback die
vorherigen Plugin-Dateien gezielt aus der Sicherung wiederherstellen.
Frühere Release-Pakete bleiben auf GitHub erhalten.
