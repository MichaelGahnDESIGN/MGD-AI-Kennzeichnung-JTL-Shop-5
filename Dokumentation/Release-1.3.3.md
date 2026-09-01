# Release 1.3.3

Version 1.3.3 korrigiert die letzte JTL-spezifische Cache-Grenze beim Update
des vollständig lokalen AI-Philosophie-Editors.

## Ursache

Der betroffene JTL-Shop arbeitet im Smarty-4-Kompatibilitätsmodus. JTL stellt
dabei eine `BackendSmarty`-Fassade bereit, kapselt die tatsächlich verwendete
Smarty-4-Engine aber intern. Die Admin-Compile-Verzeichnisse werden auf dieser
internen Engine gesetzt. Ein direkter Aufruf der von Smarty 5 geerbten
`clearCompiledTemplate()`-Methode auf der äußeren Fassade prüfte deshalb einen
nicht verwendeten Compile-Ordner.

## Korrektur

Der eng begrenzte Cache-Service fragt nun über JTLs öffentliche `getSmarty()`-
Methode die tatsächlich aktive Engine ab. Das funktioniert in beiden Modi:

- im normalen Modus liefert JTL die Fassade selbst;
- im Kompatibilitätsmodus liefert JTL die intern aktive Smarty-4-Engine.

Anschließend werden weiterhin nur kompilierte Vorlagen zu `.tpl`-Quelldateien
innerhalb des eigenen Pluginverzeichnisses entfernt. Andere Shop- oder
Template-Caches werden nicht pauschal geleert.

## Sichtbares Ergebnis

Nach einem Update auf Version 1.3.3 wird der Tab **AI-Philosophie** beim ersten
Öffnen frisch kompiliert und zeigt:

- Deutsch und Englisch als große Sprachkarten untereinander;
- die lokale Werkzeugleiste;
- die Modi **Visuell** und **HTML**;
- den gemeinsamen Speichern-Button;
- keine externen Editorbibliotheken, Fonts, Icons oder Drittinhalte.

## Sicherheit und Datenschutz

- keine Datenbankmigration;
- keine Änderung an Bildern, Kennzeichnungen oder gespeicherten Texten;
- keine Verarbeitung von Kunden-, Bestell- oder Zahlungsdaten;
- keine externe Verbindung durch die Cache-Korrektur;
- keine Telemetrie;
- weiterhin reproduzierbares Installationspaket.

Aktivierte Updatehinweise können GitHub technisch Server-IP, Zeitpunkt und den
festen User-Agent mitteilen. Positive und negative Ergebnisse werden zwölf
Stunden lokal gespeichert. Das Plugin installiert Updates nicht automatisch.

## Dev-Test und Rollback

Das Paket muss zunächst als **manueller ZIP-Upload** auf einer getrennten
Dev-Installation geprüft werden. Vorher ist ein Backup erforderlich. Nach dem
Update müssen Pluginversion, beide Editormodi, Speichern, Bildverwaltung,
Live-Vorschau, Transparenz, **supported by: Michael Gahn DESIGN** und das
Frontend kontrolliert werden.

Bei einem Fehler das Plugin deaktivieren und das gesicherte Pluginverzeichnis
wiederherstellen. Eine Deinstallation mit Datenlöschung ist für den Rollback
nicht erforderlich. Live darf erst nach erfolgreicher Dev-Abnahme aktualisiert
werden.
