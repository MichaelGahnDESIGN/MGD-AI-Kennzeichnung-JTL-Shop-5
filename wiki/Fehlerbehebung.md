# Fehlerbehebung

## Plugin-ZIP lässt sich nicht hochladen

Prüfen Sie:

- wurde das installierbare Release-ZIP verwendet?
- wurde versehentlich **Source code (zip)** heruntergeladen?
- ist die Datei vollständig und stimmt der SHA-256-Wert?
- erfüllt der Shop JTL 5.7.2 und PHP 8.1?
- erlaubt der Server die Dateigröße des Uploads?
- enthält das ZIP auf oberster Ebene genau den Pluginordner?

## Fehlercode 421 bei Installation oder Update

Ein allgemeiner JTL-Fehlercode reicht nicht für eine Diagnose. Gehen Sie kontrolliert vor:

1. keine erneuten blinden Installationsversuche auf Live;
2. JTL- und PHP-Fehlerprotokoll im gleichen Zeitfenster prüfen;
3. Paket-Hash kontrollieren;
4. Version und Aktivstatus im Plugin-Manager notieren;
5. auf einer getrennten Dev-Installation reproduzieren;
6. bei Supportanfrage nur bereinigte technische Meldungen weitergeben.

Keine Zugangsdaten, Kundendaten oder vollständigen Datenbankauszüge veröffentlichen.

## Galerie ist leer

- ersten sicheren Bildscan starten;
- alle Filter auf **Alle** stellen;
- Fundstelle nicht versehentlich auf **Veraltet** begrenzen;
- Shop- und Pluginberechtigung prüfen;
- Datenbankverbindung und Pluginaktivstatus kontrollieren;
- nach einem größeren JTL-Update erneut scannen.

## Vorschau fehlt

Mögliche Ursachen:

- Datei ist nicht mehr vorhanden;
- Dateityp ist nicht als sichere Rastervorschau zugelassen;
- Pfad liegt außerhalb erlaubter lokaler Wurzeln;
- SVG oder externe URL;
- veraltete Fundstelle.

Öffnen Sie **Details** und prüfen Sie die technische Zuordnung.

## Speichern-Button reagiert nicht

- Browserkonsole auf JavaScriptfehler prüfen;
- Admin-Sitzung eventuell abgelaufen – Backend neu anmelden;
- Seite vollständig neu laden;
- Shop-/Plugin-Cache leeren;
- prüfen, ob andere Admin-Erweiterungen JavaScript überschreiben;
- keine Tokens oder Formulardaten in ein öffentliches Issue kopieren.

## Live-Vorschau ändert sich nicht

- Seite vollständig neu laden;
- prüfen, ob JavaScript im Browser blockiert wird;
- Zahlenfeld innerhalb des angezeigten Bereichs verwenden;
- Browserkonsole auf Fehler anderer Admin-Erweiterungen prüfen;
- Shop- und Plugin-Cache leeren.

Position und Farbschema sind im Darstellungstab **Nur Vorschau**. Sie werden
nicht gespeichert. Echte Positionen und Farbschemata ändern Sie pro Bild im
Kennzeichnungsdialog.

## „Füllen Sie alle Felder aus“ oder Validierungsfehler

Prüfen Sie, ob Status, Position und Darstellung vollständig ausgewählt sind. Wenn die Meldung trotz sichtbarer Auswahl erscheint:

1. Dialog schließen und neu öffnen;
2. Seite vollständig neu laden;
3. Admin-Sitzung erneuern;
4. Browserkonsole und bereinigtes Serverprotokoll prüfen;
5. betroffene Plugin- und JTL-Version notieren.

## Label erscheint nicht im Frontend

- Status **Ungeprüft** und **Keine Kennzeichnung** sind absichtlich unsichtbar;
- Fundstelle muss vorhanden sein;
- Plugin muss aktiv sein;
- Shop- und Template-Cache leeren;
- Browser vollständig neu laden;
- prüfen, ob das Bild lokal und der Dateiname eindeutig ist;
- bei mehr als 500 sichtbaren Kennzeichnungen auf einer Seite Leistungsgrenze beachten;
- Templatekompatibilität auf einer Testseite prüfen.

## Label liegt außerhalb des Bildes

Version 1.1.1 und neuer korrigiert normale, verlinkte und responsive Bilder sowie lokale OPC-Hintergründe. Prüfen Sie zuerst:

- wirklich die aktuelle Version 1.3.4 aktiv?
- Plugin-CSS mit HTTP 200 erreichbar?
- alter Browser- oder Template-Cache?
- stark abweichendes eigenes HTML/CSS?
- Bild als ungewöhnliche CSS-Struktur statt unterstütztem Bild/Hintergrund ausgegeben?

Erstellen Sie für einen Fehlerbericht ein minimales HTML-Beispiel ohne Kundendaten und nennen Sie Template- und JTL-Version.

## Position stimmt nicht mit Erwartung überein

Position wird pro Bild gespeichert. Öffnen Sie den Kennzeichnungsdialog des konkreten Bildes und prüfen Sie den gewählten Wert. Die globale Einstellungsseite überschreibt vorhandene Bildwerte nicht.

## OPC-Schaltfläche fehlt

- Bildfeld muss lokal und eindeutig sein;
- externe URL, SVG, Video oder verstecktes Feld wird abgelehnt;
- Pluginberechtigung prüfen;
- OPC nach Cacheleerung neu öffnen;
- bei einem JTL-Update kann die Komfortintegration kontrolliert ausfallen.

Nutzen Sie in diesem Fall die zentrale Bildverwaltung.

## Dateimanager-Menüpunkt fehlt

Der Menüpunkt erscheint nur bei genau einer lokalen Rasterbilddatei und eindeutig erkannter elFinder-Struktur. Ordner, Mehrfachauswahl und nicht unterstützte Dateien erhalten bewusst keinen Eintrag.

## Shopfehler nach einem Update

1. Plugin deaktivieren.
2. Cache leeren.
3. Shop erneut prüfen.
4. gesichertes Pluginverzeichnis wiederherstellen.
5. bei Bedarf Plugin-Tabellensicherung zurückspielen.
6. Live nicht weiter verändern, bis der Fehler auf Dev verstanden ist.

Siehe auch [Release und Rollback](Release-und-Rollback.md).

## Kein GitHub-Updatehinweis sichtbar

Das kann auch bei Version 1.3.0 korrekt sein:

- ein negatives Ergebnis wird zwölf Stunden zwischengespeichert;
- die Prüfung läuft nur im adressierten Darstellungstab;
- die Einstellung kann deaktiviert sein.

Das Plugin installiert keine Updates automatisch. Laden Sie das geprüfte
Release-ZIP manuell herunter und verwenden Sie den manuellen ZIP-Upload im
Plugin-Manager. GitHub kann bei einer aktiven Prüfung Server-IP, Zeitpunkt und
User-Agent sehen; senden Sie niemals Tokens oder Zugangsdaten in einen
Fehlerbericht.
