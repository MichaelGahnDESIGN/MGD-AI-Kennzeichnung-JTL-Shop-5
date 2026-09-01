# Release 1.3.2

Version 1.3.2 ist ein gezielter Hotfix für die Aktualisierung der
Plugin-Oberfläche in JTL-Shop. Sie behebt den Fall, dass nach einem formal
erfolgreichen Update weiterhin die alte, bereits kompilierte Ansicht des Tabs
**AI-Philosophie** geladen wurde.

## Sichtbares Ergebnis

Nach dem Update und dem ersten Öffnen des Tabs zeigt das Backend wieder die in
Version 1.3.0 eingeführte Oberfläche:

- Deutsch und Englisch als große Sprachkarten untereinander;
- lokale Werkzeugleiste für Absätze, Überschriften, Listen und Textformatierung;
- Umschaltung zwischen **Visuell** und **HTML**;
- gemeinsamer Button **Beide Sprachversionen speichern**;
- vollständig lokale Bedienung ohne CDN, externe Schrift, externe Icons oder
  Drittanbieter-Editor.

## Technische Ursache

Version 1.3.1 nutzte im frühen JTL-Update-Lifecycle die allgemeine
Smarty-Instanz. Wenn das Backend zu diesem Zeitpunkt noch nicht vollständig
initialisiert war, konnte JTL eine allgemeine Instanz mit dem
Frontend-Compile-Ordner liefern. Die Bereinigung war damit fehlerfrei
ausgeführt, traf aber nicht den Admin-Templatecache, in dem die alte
Philosophie-Ansicht lag.

## Korrektur

Version 1.3.2 erzeugt nach dem JTL-Update ausdrücklich einen
`BackendSmarty`-Renderer mit der von JTL bereitgestellten Datenbank- und
Cache-Verbindung. Der vorhandene, eng begrenzte Cache-Service entfernt danach
nur kompilierte Vorlagen, deren Quelldatei innerhalb des eigenen
Pluginverzeichnisses liegt.

- keine pauschale Löschung fremder Shop- oder Template-Caches;
- keine Änderung an Bildern, Kennzeichnungen oder Philosophie-Inhalten;
- keine Datenbankmigration;
- keine externe Verbindung und keine Telemetrie;
- weiterhin reproduzierbares Installationspaket;
- Regressionstest für den echten JTL-Backend-Renderer.

## Dev-Test vor Live

Version 1.3.2 muss zuerst auf einer getrennten Dev- oder
Staging-Installation als **manueller ZIP-Upload** aktualisiert werden. Vor dem
Update ist ein Backup erforderlich. Danach sind mindestens zu prüfen:

1. Plugin-Manager zeigt Version 1.3.2 als aktiviert;
2. AI-Philosophie zeigt beide Sprachkarten untereinander;
3. beide Karten besitzen die Modi **Visuell** und **HTML**;
4. Speichern erhält beide bereinigten Sprachfassungen;
5. Bildverwaltung, Live-Vorschau und Frontend-Kennzeichnungen funktionieren;
6. Updatehinweise, **supported by: Michael Gahn DESIGN** und der
   Darstellungstab bleiben erhalten.

## Datenschutz und Rückfall

Die Korrektur verarbeitet keine Kunden-, Bestell- oder Zahlungsdaten. Bei
aktivierten Updatehinweisen kann GitHub technisch Server-IP, Zeitpunkt und den
festen User-Agent sehen; positive und negative Ergebnisse werden zwölf Stunden
lokal zwischengespeichert. Das Plugin installiert Updates nicht automatisch.

Bei einem Fehler das Plugin deaktivieren und das vor dem Dev-Test gesicherte
Pluginverzeichnis wiederherstellen. Deinstallation mit Datenlöschung ist für
einen Rollback nicht erforderlich. Live darf erst nach erfolgreicher
Dev-Abnahme aktualisiert werden.
