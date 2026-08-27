# Entwurf: Impressum-Tab im JTL-Shop-5-Plugin

## Ziel

Das Plugin erhält im JTL-Administrationsbereich einen eigenen Tab **Impressum**. Er informiert Shop-Betreiber transparent über den Plugin-Hersteller, ohne das öffentliche Shop-Impressum zu verändern.

## Abgrenzung

- Der Tab ist ausschließlich im geschützten Plugin-Backend sichtbar.
- Das Plugin legt keine öffentliche Impressumsseite an und verändert keine vorhandene Shop-Seite.
- Die Angaben sind statisch Bestandteil der veröffentlichten Plugin-Version.
- Es gibt kein Formular, keine Datenbankspeicherung, kein Tracking und keine externe Netzwerkanfrage.

## Inhalt

Der Tab zeigt folgende vom Hersteller freigegebene Angaben:

- Angaben gemäß § 5 DDG (Digitale-Dienste-Gesetz)
- Michael Gahn DESIGN
- Michael Gahn
- Dr.-Theodor-Brugsch Str. 12
- 08529 Plauen
- Sachsen
- Deutschland
- Telefon: +49 (0) 151 59156639
- E-Mail: Anfrage@Michael-Gahn.de
- Steuernummer: 223/222/02451
- USt-ID: DE288143343

Telefon und E-Mail werden mit sicheren `tel:`- beziehungsweise `mailto:`-Links ausgegeben. Die sichtbare Schreibweise bleibt menschenfreundlich.

## Bedienung und Darstellung

Der Eintrag **Impressum** erscheint nach **AI-Philosophie** und vor **Einstellungen**. Die Seite verwendet semantische Überschriften und ein `address`-Element. Die Darstellung orientiert sich an den vorhandenen JTL-Admin-Komponenten und benötigt keine eigene JavaScript-Logik.

## Technische Struktur

- `plugin/MGD_AI_Kennzeichnung/info.xml` registriert den zusätzlichen `Customlink`.
- `plugin/MGD_AI_Kennzeichnung/adminmenu/impressum.php` ist der geschützte, ausschließlich lesende Einstiegspunkt.
- `plugin/MGD_AI_Kennzeichnung/adminmenu/templates/impressum.tpl` enthält die semantische Ausgabe.
- `tests/Structure/ImpressumAdminContractTest.php` prüft Menüregistrierung, Zugriffsschutz, Inhalt und die Abwesenheit unnötiger Datenerfassung.

Der Einstiegspunkt akzeptiert nur den von JTL bereitgestellten Plugin-Kontext. Ein direkter Aufruf wird mit HTTP 403 beantwortet. Da keine Eingaben verarbeitet werden, sind weder POST noch CSRF-Token erforderlich; der Endpunkt bleibt ausdrücklich lesend.

## Datenschutz und Sicherheit

- Es werden nur die ausdrücklich freigegebenen geschäftlichen Angaben veröffentlicht.
- Es werden keine Besucher-, Kunden- oder Administratordaten verarbeitet.
- Es werden keine Inhalte aus Anfrageparametern ausgegeben.
- Es werden keine Drittanbieter geladen.
- Links verwenden ausschließlich lokale Protokolle für Telefon und E-Mail; es öffnet sich kein fremdes Skript.

## Versionierung und Dokumentation

Die sichtbare neue Funktion wird als Version **1.2.0** veröffentlicht. README, Wiki, Versionsübersicht, Changelog und Release-Dokumentation erklären den neuen Tab und die aktualisierten Kontaktdaten.

## Abnahme

Die Umsetzung ist abgenommen, wenn:

1. der Tab im Plugin-Menü an der vorgesehenen Stelle erscheint;
2. direkter Zugriff ohne JTL-Plugin-Kontext verweigert wird;
3. alle freigegebenen Angaben korrekt und ohne zusätzliche personenbezogene Daten erscheinen;
4. Telefon und E-Mail anklickbar sind;
5. der Tab keine Eingabe, Speicherung oder externe Anfrage ausführt;
6. alle automatisierten Prüfungen und der Release-Paketcheck erfolgreich sind.

