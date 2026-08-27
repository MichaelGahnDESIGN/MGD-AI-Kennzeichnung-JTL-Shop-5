# Datenschutz und Sicherheit

## Grundsatz der Datenminimierung

Das Plugin benötigt für seine Aufgabe ausschließlich technische Bild- und Kennzeichnungsdaten. Es verarbeitet keine Kundenkonten, Bestellungen, Zahlungen, Rechnungen oder E-Mail-Adressen.

Gespeichert werden insbesondere:

- technischer Bildschlüssel;
- lokaler Bildpfad;
- erkannte Fundstelle und Quellentyp;
- Status;
- Position;
- Darstellung;
- Änderungszeitpunkt;
- deutsche und englische AI-Philosophie;
- kurzlebige technische Bestätigungen für geschützte Admin-Aktionen.

## Keine automatische KI-Analyse

Das Plugin lädt Bilder nicht zu einem KI-Anbieter hoch und führt keine externe Analyse durch. Ein berechtigter Mensch vergibt den Status.

## Admin-Schutz

Schreibende Funktionen verwenden:

- bestehende JTL-Admin-Anmeldung;
- Pluginberechtigung;
- JTL-CSRF-Prüfung;
- serverseitige Positivlisten;
- erneute Prüfung vor der Datenbankänderung;
- kurzlebige Einmalbestätigung bei Stapel- und Bereinigungsaktionen.

Die OPC- und Dateimanager-Funktionen verwenden JTLs bestehende Admin-IO-Pipeline. Es entsteht kein frei zugänglicher öffentlicher Speicherendpunkt.

## Sichere Vorschau

Die Galerie zeigt nur erlaubte lokale Rasterbilder. Externe URLs, SVG und mehrdeutige Pfade werden nicht als Vorschau geladen.

## Sichere Frontend-Ausgabe

- feste Status- und Klassenlisten;
- maskierte HTML-Ausgabe;
- begrenzte numerische Stile;
- keine freien JavaScript-Ausdrücke;
- keine externen Webfonts oder Bildressourcen;
- barrierearme Textbeschreibung;
- fehlertoleranter Rückzug bei unerwartetem Hook- oder Datenbankkontext.

## AI-Philosophie

Redaktioneller HTML-Inhalt wird auf eine kleine Positivliste reduziert. Unsichere Links, aktive Elemente und freie Attribute werden entfernt.

## Updatehinweise

Die Updateprüfung ist ein Opt-in. Sie verwendet ausschließlich den festen HTTPS-Endpunkt der öffentlichen GitHub-Release-API, prüft TLS, folgt keinen Weiterleitungen und begrenzt die Antwortgröße. Das Plugin installiert keine Updates automatisch.

## Protokollierung

Sicherheitsrelevante Admin-Abläufe sollen nur feste Ereigniscodes und Mengen protokollieren. Tokens, lokale Pfade, eingegebene Philosophie-Inhalte, SQL-Ausnahmen und personenbezogene Daten gehören nicht in Logs oder öffentliche Fehlerberichte.

## Deinstallation

Eine Datenlöschung prüft Eigentumsmarker und erwartete Tabellenstruktur. Unerwartete oder fremde Tabellen werden nicht automatisch gelöscht.

## Betreiberpflichten

Der Betreiber bleibt verantwortlich für:

- korrekte menschliche Einstufung;
- rollenbasierte Vergabe von Adminrechten;
- aktuelle JTL-, PHP- und Serverupdates;
- sichere Backups;
- Prüfung eigener Datenschutz- und Rechtsanforderungen;
- sparsamen Umgang mit Fehlerprotokollen und Supportdaten.

Das Plugin unterstützt diese Prozesse, ersetzt aber kein individuelles Datenschutz- oder Rechtskonzept.
