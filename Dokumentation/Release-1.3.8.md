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

Installationsstand vor der Auslieferung: Dev 1.3.7 mit geprüftem Toolbar-Hotfix,
Onvis 1.2.0 und Campingteile24 1.3.6. Auf den Live-Shops lagen bereits Dateien
von 1.3.7, aber die abschließende JTL-Aktualisierung war noch offen.
Der neue Update-Durchlauf und die abschließenden Abnahmen stehen noch aus.

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
