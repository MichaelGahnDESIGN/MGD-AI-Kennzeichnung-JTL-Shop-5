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

Die Live-Vorschau im Darstellungstab verwendet ein lokales Plugin-Bild und
arbeitet ausschließlich im Browser. Reglerwerte werden erst nach **Speichern**
an JTL übergeben. Position und Farbschema sind dort **Nur Vorschau** und werden
nicht gespeichert.

## Sichere Frontend-Ausgabe

- feste Status- und Klassenlisten;
- maskierte HTML-Ausgabe;
- begrenzte numerische Stile;
- keine freien JavaScript-Ausdrücke;
- keine externen Webfonts oder Bildressourcen;
- barrierearme Textbeschreibung;
- fehlertoleranter Rückzug bei unerwartetem Hook- oder Datenbankkontext.

## AI-Philosophie

Redaktioneller HTML-Inhalt wird auf die Positivliste `p`, `h2`, `h3`, `ul`,
`ol`, `li`, `strong`, `em` und `a` reduziert. Unsichere Links, aktive Elemente
und freie Attribute werden entfernt. Der PHP-Sanitizer bleibt beim Speichern
die maßgebliche Sicherheitsgrenze.

Der visuelle und der HTML-Modus bestehen ausschließlich aus lokalen
Plugin-Dateien. Sie laden **keine externen** Bibliotheken, **keine Drittinhalte**,
Fonts, Icons oder CDN-Ressourcen und verwenden **keine
Telemetrie**. Texte werden nicht in Browser-Speicher oder an Dritte geschrieben.
Ohne JavaScript bleiben die großen Textfelder als **No-JavaScript-Fallback**
vollständig bedienbar.

## Updatehinweise

Bei Neuinstallationen ist die Updateprüfung standardmäßig aktiviert und kann
jederzeit ausgeschaltet werden. Sie läuft nur beim adressierten Darstellungstab,
verwendet ausschließlich den festen HTTPS-Endpunkt der öffentlichen
GitHub-Release-API, prüft TLS, folgt keinen Weiterleitungen und begrenzt die
Antwortgröße.

GitHub kann technisch die **Server-IP**, den Zeitpunkt und den festen
**User-Agent** `MGD-AI-Kennzeichnung-JTL-Shop-5/1.3.1` erhalten. Bilder,
Tokens, Shop-, Kunden- und Formulardaten werden nicht übertragen. Ein Erfolg
oder Fehler wird zwölf Stunden lokal zwischengespeichert. Das Plugin installiert
keine Updates automatisch; erforderlich bleibt der manuelle ZIP-Upload des
geprüften Release-Pakets.

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
