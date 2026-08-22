# Design: Nutzerfreundliche Bildverwaltung und direkte JTL-OPC-Kennzeichnung

## 1. Ausgangslage

Die Version 1.0.0 verwaltet KI-Kennzeichnungen bereits sicher und lokal. Die aktuelle Bildverwaltung zeigt gefundene Bilder jedoch als technische Tabelle mit langen Pfaden. Dadurch lassen sich Bilder nur schwer erkennen und die Bearbeitung benötigt unnötig viele Schritte.

Zusätzlich liegt das Portlet „AI-Philosophie“ derzeit in der OPC-Gruppe „Content“. Bei der Bearbeitung eines OPC-Bildes oder eines Container-Hintergrundbildes fehlt eine direkte Möglichkeit, die KI-Kennzeichnung am gerade ausgewählten Bild zu pflegen.

## 2. Ziel

Die Funktionsversion 1.1.0 erhält eine übersichtliche, responsive Bildgalerie und eine einheitliche, WordPress-ähnliche Kennzeichnungsmaske. Dieselbe Bedienlogik wird in drei Bereichen verwendet:

1. zentrale Bildverwaltung des Plugins;
2. OPC-Konfigurationsdialoge mit Bildauswahl;
3. JTL-Dateimanager, der aus dem OPC geöffnet wird.

Das Plugin verändert keine JTL-Core-Dateien, keine Bilddateien und keine vorhandenen OPC-Layouts. Alle Bilddaten bleiben lokal im Shop. Die vorhandene sichere Datenhaltung, Berechtigungsprüfung und transaktionale Stapelbearbeitung werden weiterverwendet.

## 3. Nicht-Ziele

- keine automatische Erkennung, ob ein Bild mit KI erzeugt wurde;
- kein Versand von Bildern oder Metadaten an externe Dienste;
- kein eigener Upload- oder vollständiger Dateimanager;
- kein Ersatz des JTL-OnPage-Composers;
- keine Speicherung der Kennzeichnung in der eigentlichen Bilddatei;
- keine Veränderung von Produkt-, Kategorie- oder Bilddaten der JTL-Wawi;
- keine ungeprüfte direkte Veröffentlichung im Live-Shop.

## 4. Bedienkonzept

### 4.1 Filter und Galerie

Die vorhandenen Filter bleiben vollständig erhalten:

- Status;
- Quelle;
- Fundstelle;
- Sortierung;
- Richtung;
- Einträge pro Seite.

Die Filter werden in einem responsiven Filterbereich mit klaren deutschen Bezeichnungen angeordnet. „Liste anwenden“ aktualisiert die Ergebnisliste. Die gewählte Seitengröße bleibt aus Leistungsgründen bestehen: Die gefilterten Bilder der aktuellen Seite werden als Galerie angezeigt, während die Seitennavigation Zugriff auf das vollständige Ergebnis bietet. Es werden nicht unkontrolliert sämtliche Shopbilder auf einmal in den Browser geladen.

Oberhalb der Galerie erscheinen die Gesamtzahl des gefilterten Ergebnisses sowie hilfreiche Statusangaben, insbesondere die Zahl ungeprüfter Bilder. Der vorhandene sichere Bildscan wird als gut sichtbare Aktion „Bilder neu scannen“ angeboten.

### 4.2 Galeriekarten

Jede Karte enthält:

- eine lokale, verzögert geladene Bildvorschau;
- eine Auswahlbox für die Stapelbearbeitung;
- einen verständlichen Quellhinweis wie „Produkt“, „Kategorie“, „Hersteller“, „Banner“, „OPC“, „Manuell“ oder „Unbekannt“;
- den aktuellen Status als farblich und textlich unterscheidbares Kennzeichen;
- einen gekürzten Dateinamen;
- die Aktionen „Kennzeichnen“ und „Details“.

Der vollständige lokale Pfad bleibt in der Detailansicht verfügbar, überlädt aber nicht mehr die Galerie. Kann ein Bild nicht dargestellt werden, erscheint ein neutraler Platzhalter. Andere Karten und Funktionen bleiben davon unberührt.

### 4.3 Einzelkennzeichnung

„Kennzeichnen“ öffnet einen kompakten Dialog mit:

- großer Vorschau des Bildes;
- Statusauswahl;
- Position des Labels;
- Darstellung „Automatisch“, „Hell“ oder „Dunkel“;
- „Abbrechen“;
- „Kennzeichnung speichern“.

Status, Position und Darstellung aktualisieren unmittelbar eine lokale Live-Vorschau auf dem Bild. Diese Vorschau speichert nichts und verändert weder Bilddatei noch Datenbank. Erst der ausdrückliche Klick auf „Kennzeichnung speichern“ sendet die Änderung an den Server.

Nach erfolgreicher Speicherung aktualisieren sich Karte und Statusanzeige. Bei einem Fehler bleiben die bisherigen Werte bestehen und der Dialog zeigt eine verständliche Meldung.

### 4.4 Stapelkennzeichnung

Ausgewählte Karten aktivieren eine deutlich sichtbare Sammelaktion. Ein gemeinsamer Dialog legt Status, Position und Darstellung für alle ausgewählten Bilder fest.

Vor der verbindlichen Speicherung erscheint eine Zusammenfassung, beispielsweise:

> 12 Bilder werden als „KI-generiert“, unten rechts und automatisch dargestellt.

Die bestehende einmalige Bestätigung und transaktionale Speicherung bleiben erhalten. Entweder werden alle ausgewählten Bilder geändert oder keines.

### 4.5 Detailansicht

Die technische Detailansicht bleibt als eigener Weg für Diagnose und Zusatzinformationen bestehen. Sie zeigt Vorschau, vollständigen lokalen Pfad, Quelle, Status, Position, Darstellung sowie vorhandene und veraltete Fundstellen. Die normale redaktionelle Bearbeitung erfolgt vorrangig über den kompakten Kennzeichnungsdialog.

## 5. Einbindung in den OnPage Composer

### 5.1 Portlet-Gruppe

Das Portlet „AI-Philosophie“ wird in PHP und `info.xml` der Gruppe „Custom Portlets“ zugeordnet. Dadurch erscheint es gemeinsam mit anderen projektspezifischen Portlets und nicht mehr zwischen den allgemeinen JTL-Inhaltselementen.

### 5.2 Direkte Kennzeichnung im OPC-Konfigurationsdialog

Das offizielle `editor_init.js` des Plugin-Portlets lädt eine kleine, lokal ausgelieferte OPC-Erweiterung. Sie erkennt sichtbare Bildfelder in einem geöffneten OPC-Konfigurationsdialog. Unterstützt werden insbesondere:

- Bild-Portlet;
- statisches Container-Hintergrundbild;
- Banner und Bilder-Slider mit einzeln ausgewählten Bildern;
- weitere JTL-Bildfelder, deren lokaler Bildpfad eindeutig ermittelt werden kann.

Unter der jeweiligen Bildvorschau erscheint die Aktion „KI-Kennzeichnung bearbeiten“. Sie öffnet denselben Kennzeichnungsdialog wie die zentrale Galerie. Nach der Bildauswahl wird der lokale Pfad neu eingelesen, sodass nicht versehentlich ein zuvor geöffnetes Bild geändert wird.

Die Live-Vorschau wird ausschließlich auf der sichtbaren Bildvorschau angebracht. Sie ist als nicht gespeicherte Vorschau erkennbar und wird beim Abbrechen vollständig entfernt.

## 6. Einbindung in den JTL-Dateimanager

JTL-Shop 5.7.2 stellt im separaten elFinder-Dateimanager keinen eigenen Plugin-Hook für zusätzliche Kontextmenübefehle bereit. Deshalb wird diese Komfortfunktion bewusst fehlertolerant und ohne Core-Änderung umgesetzt:

1. Die bereits im OPC geladene Erweiterung erkennt ausschließlich das gleichnamige, same-origin Dateimanagerfenster.
2. Nur bei eindeutig passender JTL-/elFinder-Struktur wird für genau eine ausgewählte lokale Bilddatei der Menüpunkt „KI-Kennzeichnung bearbeiten“ ergänzt.
3. Der Menüpunkt öffnet den gemeinsamen Kennzeichnungsdialog mit Live-Vorschau und Speichern-Button.
4. Nicht-Bilddateien, externe Adressen und mehrdeutige Auswahlen werden nicht angeboten.
5. Wird die erwartete Struktur nach einem JTL-Update nicht erkannt, findet keine DOM-Veränderung statt. Der Dateimanager funktioniert unverändert weiter. Die zentrale Galerie und die OPC-Integration bleiben nutzbar.

Die Kompatibilitätsgrenze wird in der Dokumentation ausdrücklich genannt. Eine zukünftige JTL-Version darf höchstens diese zusätzliche Komfortfunktion deaktivieren, niemals den Dateimanager blockieren.

## 7. Technische Architektur

### 7.1 Servergerenderte Galerie

Die Bildliste bleibt serverseitig gerendert. Die bestehende Aktions-, Repository- und ViewModel-Struktur wird gezielt erweitert. Es entsteht keine große Single-Page-Anwendung.

Verantwortlichkeiten werden in einzelne Dateien aufgeteilt:

- Aufbereitung sicherer Vorschauinformationen;
- Übersetzung technischer Status- und Quellwerte;
- Galeriekarte;
- Filterbereich;
- Kennzeichnungsdialog;
- Stapelaktionsleiste;
- lokale Live-Vorschau;
- OPC-Dialogintegration;
- Dateimanagerintegration;
- geschützte Admin-IO-Anfragen.

### 7.2 Sichere Vorschau-URLs

Die Datenbank speichert weiterhin nur normalisierte lokale Pfade. Ein eigener Resolver erzeugt ausschließlich für freigegebene lokale Bildwurzeln eine Vorschau-URL unter derselben Shopdomain. Pfadsegmente werden sicher kodiert.

Nicht erlaubt sind:

- Schemas wie `http:`, `https:`, `data:` oder `javascript:`;
- Hostnamen und Ports;
- Traversierung mit `..`;
- Nullbytes;
- absolute Serverdateisystempfade;
- Pfade außerhalb der bekannten lokalen JTL-Bildverzeichnisse.

Das Backend liefert keine physischen Serverpfade an den Browser. Bilder werden per `loading="lazy"` und mit festgelegtem Darstellungsrahmen geladen.

### 7.3 Gemeinsamer Kennzeichnungsdienst

Galerie, OPC und Dateimanager verwenden dieselbe serverseitige Anwendungslogik. Für OPC und Dateimanager registriert das Plugin über `HOOK_IO_HANDLE_REQUEST_ADMIN` eng begrenzte Admin-IO-Funktionen zum:

- Laden der Kennzeichnung eines lokalen Bildpfads;
- sicheren Speichern von Status, Position und Darstellung.

Der JTL-IO-Controller prüft die angemeldete Admin-Session und den JTL-CSRF-Token. Der Plugin-Dienst prüft zusätzlich die Pluginberechtigung und validiert jeden Eingabewert gegen feste erlaubte Wertelisten.

Ist ein eindeutig lokales Bild noch nicht durch den Scanner bekannt, wird beim ersten Speichern ein ungeprüfter Plugin-Datensatz erstellt und anschließend mit der bewussten Kennzeichnung aktualisiert. Die Herkunft wird nachvollziehbar als OPC beziehungsweise manuelle lokale Auswahl gespeichert. Es werden keine freien oder externen URLs übernommen.

### 7.4 JavaScript-Struktur

Das JTL-Portletsystem erwartet `editor_init.js` als Einstieg. Diese Datei bleibt klein und initialisiert getrennte Module für:

- OPC-Konfigurationsdialoge;
- Dateimanager-Kompatibilität;
- Dialogdarstellung;
- Live-Vorschau;
- Admin-IO-Kommunikation.

Die Erweiterung arbeitet ohne externe Bibliotheken oder Netzwerkanfragen. Beobachter werden auf den benötigten Dialogbereich begrenzt und beim Schließen entfernt. Wiederholtes Öffnen darf keine doppelten Schaltflächen oder mehrfachen Ereignisbindungen erzeugen.

## 8. Sicherheit und Datenschutz

- Änderungen sind ausschließlich für angemeldete JTL-Administratoren mit Pluginberechtigung möglich.
- Jede schreibende Anfrage benötigt den gültigen JTL-CSRF-Token.
- Status, Position und Darstellung werden ausschließlich aus festen Wertelisten akzeptiert.
- Externe URLs und unzulässige Pfade werden vor jedem Datenbankzugriff abgelehnt.
- SQL verwendet gebundene Parameter und vorhandene transaktionale Repository-Methoden.
- Bilddateien werden nicht verändert und nicht an Dritte übertragen.
- Es werden keine Passwörter, Tokens, Bildnamen, Pfade oder personenbezogenen Daten protokolliert.
- Fehlerprotokolle enthalten nur allgemeine Ereigniscodes und notwendige Zähler.
- Frontend-Ausgaben bleiben escaped, lokal und ohne Tracking.

## 9. Barrierefreiheit und responsive Darstellung

- Jede Karte und Auswahlbox besitzt eine eindeutige zugängliche Beschriftung.
- Status wird nicht nur durch Farbe, sondern immer zusätzlich durch Text dargestellt.
- Dialoge besitzen Überschrift, Fokusführung, Schließen per Escape und Rückgabe des Fokus an den Auslöser.
- Alle Aktionen sind per Tastatur erreichbar.
- Fehlermeldungen werden als verständliche Statusmeldung ausgegeben.
- Die Galerie passt ihre Spaltenzahl an Desktop, Tablet und Mobilgerät an.
- Lange Dateinamen werden visuell gekürzt, bleiben aber über zugänglichen Hilfetext nachvollziehbar.

## 10. Fehlerverhalten

- Fehlende Vorschau: neutraler Platzhalter, restliche Seite bleibt funktionsfähig.
- Ungültiger lokaler Pfad: keine Kennzeichnung, verständliche Meldung, keine Datenbankänderung.
- Abgelaufene Admin-Session oder CSRF-Fehler: keine Änderung, Hinweis zum Neuladen beziehungsweise erneuten Anmelden.
- Fehlende Berechtigung: HTTP-/IO-Fehler ohne interne Details.
- Datenbankfehler: bestehende Werte bleiben erhalten; Stapeländerungen werden vollständig zurückgerollt.
- Inkompatibler Dateimanager: kein zusätzlicher Menüpunkt; JTL-Dateimanager bleibt unverändert.
- Doppelte Browseraktion: Speichern-Schaltfläche wird während der Anfrage gesperrt; serverseitige Logik bleibt idempotent für identische Zielwerte.

## 11. Tests und Abnahme

Die Umsetzung erfolgt testgetrieben. Vor Produktionscode wird jeweils ein fehlschlagender Test erstellt und beobachtet.

Automatisch geprüft werden mindestens:

- sichere und unsichere Vorschaupfade;
- lokale Vorschau-URL-Erzeugung;
- deutsche Status- und Quellenbezeichnungen;
- Galeriestruktur, Lazy Loading und zugängliche Beschriftungen;
- Portlet-Gruppe „Custom Portlets“ in PHP und XML;
- Laden und Speichern über die Admin-IO-Grenze;
- Rechte- und CSRF-Prüfung;
- strikte Validierung aller Status-, Positions- und Darstellungswerte;
- erstmalige Anlage eines noch unbekannten lokalen Bildes;
- keine Änderung bei fehlerhaften Eingaben;
- transaktionale Stapelbestätigung;
- einmalige Initialisierung der OPC-Erweiterung;
- fehlertolerante Dateimanager-Kompatibilitätsprüfung;
- Versions- und Paketstruktur der Version 1.1.0.

Vor einer Freigabe werden vollständig ausgeführt:

```bash
composer test
composer analyse
composer style
bash scripts/build-release.sh
```

Zusätzlich folgen auf `dev.onvis-shop.de`:

1. Datenbank- und Pluginbackup;
2. Installation beziehungsweise Update auf 1.1.0;
3. Prüfung der Galerie mit Produkt-, Kategorie-, Banner- und OPC-Bildern;
4. Einzelkennzeichnung einschließlich Live-Vorschau und Abbrechen;
5. Stapelkennzeichnung einschließlich Zusammenfassung;
6. OPC-Test mit Bild-Portlet und Container-Hintergrundbild;
7. Dateimanager-Test mit Bild und Nicht-Bilddatei;
8. Frontendprüfung der vier Labelpositionen und drei Darstellungen;
9. Prüfung, dass Wartungsmodus und fehlende Wawi-Anbindung von Dev unverändert bleiben;
10. JTL-Dateiprüfung auf unbeabsichtigte Core-Änderungen.

## 12. Versionierung, Git und Auslieferung

- Zielversion: 1.1.0.
- Die Umsetzung erfolgt in nachvollziehbaren Commits mit deutschen Dokumentationsanpassungen.
- Nach vollständiger Verifikation wird der freigegebene Stand in `main` integriert und zu GitHub gepusht.
- `.superpowers/`, lokale Zugangsdaten, Backups, temporäre Dateien und alte lokale ZIP-Dateien gelangen weder in Git noch in das Releasepaket.
- Das installierbare Paket entsteht ausschließlich als `dist/MGD_AI_Kennzeichnung-1.1.0.zip`.
- Das bereits vorhandene, nicht versionierte Stammverzeichnis-ZIP wird nicht überschrieben oder gelöscht.

## 13. Dev- und Live-Rollout

Der Live-Shop wird nicht direkt mit ungetestetem Code verändert.

### Dev

1. Backup von Pluginordner und eigener Dev-Datenbank;
2. Update auf 1.1.0;
3. technische und visuelle Abnahme gemäß Abschnitt 11;
4. dokumentiertes Ergebnis und vorbereiteter Rückfallweg.

### Live

Nach erfolgreicher Dev-Abnahme und frischer Paketprüfung:

1. Backup der betroffenen Plugin-Dateien und Plugin-Datenbanktabellen;
2. kurzes abgesichertes Wartungsfenster;
3. Update des Plugins auf 1.1.0;
4. Cache leeren;
5. Prüfung von Startseite, Produktseite, Warenkorb und Checkout ohne Testbestellung;
6. Prüfung einer bewusst gekennzeichneten Testgrafik;
7. Wartungsfenster beenden;
8. abschließende Erreichbarkeits- und Logprüfung.

Bei einem Fehler wird auf das gesicherte Pluginpaket und den unmittelbar davor erstellten Datenbankstand zurückgegangen. Andere Shopdateien, Bestellungen, Kunden- und Zahlungsdaten werden nicht verändert.

## 14. Abnahmekriterien

Die Funktion gilt erst als fertig, wenn:

- gefilterte Bilder als erkennbare Galerie statt als Pfadtabelle erscheinen;
- eine Einzelkennzeichnung mit Live-Vorschau und bewusstem Speichern möglich ist;
- eine Stapeländerung vor dem Speichern zusammengefasst wird;
- das AI-Philosophie-Portlet unter „Custom Portlets“ erscheint;
- die direkte Kennzeichnung im OPC-Bilddialog funktioniert;
- der Dateimanager-Menüpunkt auf der getesteten JTL-Version funktioniert und bei Unverträglichkeit sicher ausfällt;
- keine JTL-Core-Datei verändert wurde;
- alle automatischen Prüfungen erfolgreich sind;
- das Dev-System erfolgreich abgenommen wurde;
- `main` den geprüften Stand enthält und zu GitHub gepusht wurde;
- der Live-Rollout mit Backup, Prüfung und dokumentiertem Rückfallweg abgeschlossen wurde.
