# Release 1.3.8 – kompakte Editor-Werkzeuge

## Was sich für Sie verbessert

Die Werkzeuge im Tab AI-Philosophie nehmen deutlich weniger Platz ein:
32 statt 44 px Mindesthöhe bei normaler Basisschrift, engere Abstände und
weiterhin automatischer Umbruch. Beide großen Textfelder, alle Formatierungen,
der HTML-Modus und der große Speichern-Button bleiben erhalten.

Deutsch und English erhalten auf den weißen Karten eine gut lesbare dunkle
Überschrift. Eine lokale CSS-Inhaltskennung verhindert veraltete Darstellung
aus dem Browsercache. Die Verbesserungen aus Version 1.3.7 sind ebenfalls enthalten.

## Paket und Sicherheit

Verwenden Sie `MGD_AI_Kennzeichnung-1.3.8.zip`, nicht Source code (zip).
Die SHA-256-Datei gehört zum selben Release. Fertige ZIP-Dateien liegen lokal
weiterhin im Hauptprojekt im Ordner `plugin/`.

Keine neue Datenbankmigration, keine externen Editor-Ressourcen, keine Änderung
an Bildern, Kennzeichnungen oder gespeicherten Designwerten. Das Plugin
installiert keine Updates automatisch. Aktualisieren erfolgt über den
JTL-Plugin-Manager, ohne Deinstallation. Keine automatischen GitHub-Actions-Läufe.

## Prüfung und Installationsstand

Die ursprüngliche CSS-Korrektur wurde auf Dev tatsächlich gemessen und visuell
geprüft: 13 Werkzeuge pro Sprache, 32 px Leistenhöhe bei 603 px verfügbarer
Breite, große Textflächen und funktionierender Wechsel Visuell/HTML.
Siehe [Dev-Abnahme der Toolbar](Dev-Toolbar-Korrektur-2026-09-03.md).

Lokal bestanden: 565 PHP-Tests mit 14.904 Assertions, 146 JavaScript-Tests,
PHPStan ohne Fehler, Formatprüfung ohne Änderungsbedarf, Composer-Validierung
und Paketbau. PHP-Laufzeit 8.5.6; kein zusätzlicher Lauf unter PHP 8.1.
Pakettests kontrollieren Inhalt und reproduzierbare Builds.

SHA-256 des Installationspakets:
`aeaf351046009666f4017438d0c81ab9305d58e01c2c13ae4ada0c19188a679e`

Nach der Bestätigung durch den Betreiber wurde Dev erneut geprüft:
Version **1.3.8 aktiviert**, alle 192 Paketdateien stimmen bytegenau mit dem
Release-ZIP überein. Beide Editorleisten haben jeweils 13 Werkzeuge mit
32 px Höhe; Sprachüberschriften sind dunkel. Visuell/HTML-Umschaltung und
Design-Speichern funktionieren. Die Erfolgsmeldung erscheint ohne weiße Seite;
alle sieben gespeicherten Designwerte bleiben unverändert. Die Galerie zeigt
744 Einträge. Damit ist die paketbezogene Dev-Abnahme bestanden.

Die beiden Live-Shops haben zunächst das vorher vorbereitete Update auf
**1.3.7** abgeschlossen. Nach erneuter Plugin-Sicherung wurde dieselbe geprüfte
1.3.8-ZIP auf Onvis und Campingteile24 erfolgreich hochgeladen. Beide
Plugin-Manager zeigen jetzt **1.3.7 → 1.3.8** an. Der abschließende
JTL-Updateklick durch den Betreiber und die anschließende Live-Abnahme sind
noch offen. Dateiupload allein gilt ausdrücklich nicht als abgeschlossenes
JTL-Update. Keine Deinstallation durchführen.

Die OPC-Galerie von Campingteile24 wurde vor dem 1.3.8-Upload mit allen Status
geprüft: **371 Ergebnisse**. Die drei zuvor vermissten Startseitenbilder für
Campingbeleuchtung, Campingmöbel und Druckwasserpumpen sind unter
`media/image/storage/opc/banner/2026/` vorhanden. Es wurde dabei kein erneuter
Scan und keine Änderung von Bildkennzeichnungen ausgelöst.

GitHub `main` enthält die Releasevorbereitung (`e42eb7a`) und diese Abnahme.
Der GitHub-Digest des hochgeladenen ZIPs stimmt mit der lokalen Prüfsumme
überein. Die öffentliche Freigabe folgt auf die bestandene Dev-Abnahme;
ein vollständiger Installationsgleichstand aller drei Shops wird damit
noch nicht behauptet.

Zusatzsicherungen vor 1.3.8 sind vollständig erstellt, verschlüsselt und durch
Entschlüsselung/Bytevergleich geprüft: Dev 196 Plugin-Dateien und vier eigene
Plugin-Tabellen; Onvis 194 Plugin-Dateien und vier eigene Plugin-Tabellen;
Campingteile24 197 Plugin-Dateien. Dort beruht die Datenbanksicherung auf der
ausdrücklichen Betreiberbestätigung. Alle Sicherungen bleiben ausschließlich
lokal. Bestehende Sicherungen und frühere Rollback-Stände wurden nicht gelöscht.
Unmittelbar vor dem erneuten Live-Upload wurden Onvis (194 Dateien und vier
eigene Plugin-Tabellen) sowie Campingteile24 (197 Dateien) zusätzlich erneut
verschlüsselt gesichert und durch Entschlüsselung/Bytevergleich geprüft.

## Sicherung und Rückweg

Der Betreiber hat aktuelle vollständige Datei- und Datenbanksicherungen
bestätigt, einschließlich Hoster-Backup. Für Dev und Onvis liegen zusätzlich
vollständig geprüfte verschlüsselte Backups vor. Die Campingteile24-Sicherung
ist durch den Betreiber bestätigt, aber nicht für die Assistenz zugänglich.
Vor diesem Update werden die Plugin-Dateien zusätzlich lokal gesichert und
geprüft; Backups, Schlüssel und Testartefakte werden nicht veröffentlicht.

Bei Fehlern das Update stoppen. Nur die betroffenen Plugin-Dateien und
gegebenenfalls deren Plugin-Metadaten gezielt aus der Sicherung zurückführen.
Nie eine komplette Live-Datenbank unbesehen zurückspielen: neu eingegangene
Bestellungen müssen erhalten bleiben. Vollständige Betreiber-Backups können
nur der Betreiber beziehungsweise Hoster wiederherstellen.
