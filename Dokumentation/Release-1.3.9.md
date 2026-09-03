# Release 1.3.9 – Detail-Lupe im Design-Editor

## Was sich für Sie verbessert

Das Schuh-Beispielbild hat wieder seinen neutralen Hintergrund ohne
Schachbrettrand. Direkt darunter finden Sie eine separate **Detail-Lupe**:
Das Label erscheint zweifach vergrößert auf bunten Flächen, feinen Linien
und einem Kreis. Dadurch erkennen Sie Transparenz und Hintergrundunschärfe
deutlicher, während das normale Produktbild die tatsächliche Position zeigt.

Beide Vorschauen übernehmen unmittelbar dieselben geprüften Designwerte.
Unter der Lupe lesen Sie die tatsächliche Transparenz in Prozent und die
Unschärfe in Pixeln ab. Bei 0 % Transparenz erklärt ein Hinweis, weshalb der
deckende Hintergrund die Unschärfe verdeckt. Die Lupenbox wächst bei großen
oder mehrzeiligen Labels mit. Ihre Schrift bleibt scharf.

Die Lupe ist eine Effektprobe, kein echter Ausschnitt des Produktbilds.
Position und Außenabstand beurteilen Sie weiterhin am Schuhbild; die Probe
bleibt mittig. Erst **Speichern** ändert globale Einstellungen.

## Paket und Sicherheit

Verwenden Sie **MGD_AI_Kennzeichnung-1.3.9.zip** aus den Release-Anhängen,
nicht den automatisch erzeugten Download **Source code (zip)**. Vergleichen
Sie die SHA-256-Prüfsumme mit der beigefügten Datei. Fertige ZIPs liegen lokal
im Hauptprojekt im Ordner `plugin/`.

Die Änderung benötigt keine neue Datenbankmigration und verändert keine
Shopbilder, Kennzeichnungen oder gespeicherten Designwerte. Es gibt keine
neue Einstellung und keine automatische Speicherung. Bild, JavaScript und
CSS bleiben vollständig lokal im Plugin; es kommen keine externen Fonts,
Bibliotheken oder Dienste hinzu. Die vorhandenen optionalen GitHub-
Updatehinweise bleiben unverändert. Keine automatischen GitHub-Actions-Läufe.

## Prüf- und Installationsstand

Die lokale Funktionsabnahme der Detail-Lupe ist im
[Abnahmeprotokoll](Detail-Lupe-Abnahme.md) dokumentiert. Das versionierte
1.3.9-Paket hat anschließend alle lokalen Prüfungen bestanden:

- 564 PHP-Tests mit 14.912 Assertions;
- 154 JavaScript-Tests;
- PHPStan ohne Fehler, Formatprüfung ohne Änderungsbedarf;
- strenge Composer-Validierung, reproduzierbarer Paketbau und ZIP-Integrität.

Die lokale PHP-Laufzeit war 8.5.6; es erfolgte kein zusätzlicher Lauf unter
PHP 8.1. Die Pakettests prüfen erlaubte Inhalte und identische Ergebnisse
wiederholter Builds. Die ergänzende Backend-Abnahme im echten JTL-Shop steht
noch aus; sie ist Voraussetzung für die anschließenden Live-Shop-Updates.

SHA-256 des Installationspakets:
`7afde24a22f354c79fd962a32a28e607d43dfdae57a4a3f76aecf97bed9bcf0a`

| Shop | Stand für 1.3.9 |
|---|---|
| dev.onvis-shop.de | Installation und Backend-Abnahme noch ausstehend |
| onvis-shop.de | Update erst nach bestandener Dev-Abnahme |
| campingteile24.de | Update erst nach bestandener Dev-Abnahme |

Veröffentlichung, Dateiupload und abgeschlossene JTL-Aktualisierung sind
getrennte Schritte. Ein Update erfolgt im Plugin-Manager ohne Deinstallation.
Die Abnahme prüft die installierte aktive Version, alle Paketdateien,
beide Vorschauen und einmaliges Speichern unveränderter Designwerte.

## Sicherung und Rückweg

Vorbereitung am 3. September 2026: Zusätzliche Plugin-Sicherungen wurden
verschlüsselt und durch Entschlüsselung/Bytevergleich geprüft: Dev 197 Dateien
und vier eigene Plugin-Tabellen; Onvis 195 Dateien und vier eigene Tabellen;
Campingteile24 198 Dateien. Alle Sicherungen und Schlüssel bleiben lokal
außerhalb von Repository, Installationspaket und Webroot.

Vollständige Datei- und Datenbanksicherungen sowie Hoster-Backups wurden
zusätzlich vom Betreiber bestätigt. Bei Campingteile24 ist die vollständige
Datenbanksicherung für die Assistenz nicht zugänglich und daher nicht selbst
geprüft. Diese Grenze wird nicht als geprüfter Datenbank-Restore ausgegeben.

Bei einer Störung zuerst das betroffene Plugin deaktivieren und die Ursache
prüfen. Nicht deinstallieren: Das könnte Plugin-Daten entfernen. Die vorherigen
Plugin-Dateien und technischen Plugin-Daten können gezielt aus der Sicherung
wiederhergestellt werden. Niemals die gesamte Live-Datenbank pauschal
zurückspielen, da zwischenzeitlich neue Bestellungen eingegangen sein können.
Ein Downgrade allein durch Upload eines älteren ZIPs ist kein bestätigter
Rollback. Frühere Sicherungen und Release-Pakete bleiben erhalten.
