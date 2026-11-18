# Datenschutz und Sicherheit

## Datenminimierung

Das Plugin speichert ausschließlich technische lokale Bildschlüssel, lokale Pfade, Fundstellen, Kennzeichnungsstatus, Anzeigeeinstellungen und die redaktionellen Philosophie-Texte. Es benötigt keine Kundenprofile, Bestellungen, Zahlungsdaten, E-Mail-Adressen oder externen KI-Konten.

Es findet keine automatische KI-Erkennung statt. Das Plugin sendet **keine Bilder**, Bildinhalte, Shoptexte oder personenbezogenen Daten an einen KI-Anbieter.

## Netzwerkzugriffe

Die frühere GitHub-Abfrage für Updatehinweise wurde entfernt. Updates werden
nur über den Backend-Reiter **Updates** (MGD Update Client) auf Ihren Klick
geprüft. Dabei kann der
Update-Server `theme.michael-gahn.de` technisch **Server-IP** und Zeitpunkt
der Anfrage erhalten. Updates erfolgen weiterhin per geprüftem, manuellem
ZIP-Upload.

### Installation melden (ab Version 1.5.0)

Das Plugin meldet sich einmal bei `theme.michael-gahn.de` an und danach
höchstens einmal am Tag. Gesendet werden nur Plugin-Name, Version,
Shopsystem, die Domain des Shops und eine zufällige Installations-ID. Dabei
erhält der Server technisch auch die Server-IP des Shops. Es werden keine
Kunden-, Bestell- oder Besucherdaten und keine Bilder gesendet.

- Die Meldung läuft nur im Administrationsbereich und bei Installation,
  Aktivierung und Update, nie im Shop-Frontend.
- Der Schalter **Installation bei Michael Gahn DESIGN melden** im Reiter
  **Updates** ist ab Werk an und jederzeit abschaltbar. Abschalten löscht den
  Eintrag auf der Plattform; danach wird nichts mehr gesendet.
- Einträge ohne Meldung seit 12 Monaten löscht die Plattform automatisch.
- Das Plugin funktioniert immer voll weiter, auch wenn die Plattform nicht
  erreichbar ist oder die Meldung ausgeschaltet ist.

## Lokaler Editor der AI-Philosophie

Der visuelle und der optionale HTML-Modus bestehen ausschließlich aus lokalen
Plugin-Dateien. Der Editor lädt **keine externen** Bibliotheken, Drittinhalte,
Fonts, Icons, Styles oder Scripts und verwendet **keine Telemetrie**. Inhalte
werden weder in `localStorage` noch in `sessionStorage`, Cookies oder externe
Dienste geschrieben.

Nur `p`, `h2`, `h3`, `ul`, `ol`, `li`, `strong`, `em` und `a` werden
übernommen. Links müssen sichere HTTPS-Ziele ohne Zugangsdaten und fremden Port
sein. Browser und Server bereinigen den Inhalt; der serverseitige PHP-Sanitizer
bleibt maßgeblich. Ohne JavaScript sind beide großen Textfelder über den
**No-JavaScript-Fallback** vollständig bedienbar.

## Administration

Schreibzugriffe verlangen eine angemeldete, berechtigte JTL-Administration, ein gültiges CSRF-Token und bei Stapel- oder Löschvorgängen eine kurzlebige Einmalbestätigung. Logs enthalten nur feste Ereigniscodes und Mengen, keine Tokens, SQL-Ausnahmen, lokalen Pfade oder eingegebenen Inhalte.

Die Galerie lädt ausschließlich lokale Vorschauen aus festen Shopwurzeln. OPC und Dateimanager verwenden JTLs bestehende Admin-IO-Strecke mit derselben Sitzung und CSRF-Prüfung. Externe Bild-URLs, SVG und mehrdeutige Auswahlen werden abgelehnt. Es entsteht kein eigener öffentlicher Upload- oder Speicherendpunkt.

Auch die Live-Vorschau im Darstellungstab bleibt lokal: Das fiktive Produktbild
liegt im Plugin, und die Regler ändern die Vorschau nur im Browser. Position und
Farbschema sind dort **Nur Vorschau** und werden nicht gespeichert. Erst der
Knopf **Speichern** übermittelt die geprüften globalen Werte an JTL.

## Ausgabe und Deinstallation

HTML, Klassen und numerische Stile stammen aus Positivlisten. Philosophie-Inhalte erlauben nur wenige semantische Elemente und HTTPS-Links ohne Zugangsdaten. Bei Deinstallation werden fremde oder veränderte Tabellen nie gelöscht.
