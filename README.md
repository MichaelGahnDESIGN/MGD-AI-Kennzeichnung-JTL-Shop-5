# MGD AI Kennzeichnung für JTL-Shop 5

Transparente Kennzeichnungen für KI-generierte und KI-bearbeitete Bilder – direkt in JTL-Shop 5, ohne die Originalbilder zu verändern und ohne Bilddaten an externe KI-Dienste zu senden.

> **Aktuelle Version:** 1.3.4
> **Getestet mit:** JTL-Shop 5.7.2, PHP 8.1 oder neuer, NOVA und NOVA-basierten Templates
> **Wichtig:** Das Plugin erkennt KI-Inhalte nicht automatisch. Die fachliche Prüfung und Einstufung erfolgt bewusst durch einen berechtigten Menschen.

Es gibt **keine automatische KI-Erkennung**. Dadurch bleibt jede veröffentlichte Einstufung eine nachvollziehbare redaktionelle Entscheidung.

## Wofür ist das Plugin gedacht?

Online-Shops verwenden zunehmend Bilder, die vollständig oder teilweise mit künstlicher Intelligenz erstellt wurden. Das Plugin hilft Shopbetreibern dabei, diese Bilder nachvollziehbar zu verwalten und im Shop sichtbar zu kennzeichnen.

Es verbindet drei Aufgaben:

1. **Bilder finden:** Lokale Bilder aus Artikeln, Kategorien, Herstellern, Bannern und dem OnPage Composer werden in einer zentralen Galerie zusammengeführt.
2. **Bilder bewerten:** Ein berechtigter Mitarbeiter vergibt pro Bild einen eindeutigen Status, eine Position und eine Darstellungsvariante.
3. **Kennzeichnung anzeigen:** Sichtbare Hinweise werden als barrierearmes Overlay innerhalb der Bildfläche ausgegeben.

Das Plugin verändert keine Bilddatei, brennt keinen Text in ein Bild ein und veröffentlicht keine OPC-Seite automatisch. Technische Bildzuordnungen speichert es in eigenen Plugin-Tabellen für Bildzuordnungen; globale Darstellungswerte liegen getrennt davon in der JTL-Plugin-Konfiguration für Darstellungswerte.

## Die wichtigsten Vorteile

- zentrale, responsive Bildgalerie statt unübersichtlicher technischer Pfadlisten;
- visuelle Prüfung mit lokalen Vorschaubildern;
- sechs klar getrennte Prüf- und Kennzeichnungsstatus;
- direkte Einzelbearbeitung mit Live-Vorschau und eindeutigem Speichern-Button;
- eigener zweispaltiger Darstellungstab mit lokaler Live-Vorschau, Zahlenfeldern und Schiebereglern;
- abgesicherte Stapelbearbeitung für bis zu 500 ausgewählte Einträge;
- Ausgabe an normalen Bildern, verlinkten Bildern, responsiven `picture`-Elementen und lokalen OPC-Hintergrundbildern;
- Unterstützung für Produkt-, Kategorie-, Hersteller-, Banner-, Slider- und OPC-Bilder;
- Integration in den OnPage Composer und optional in dessen JTL-Dateimanager;
- deutsch- und englischsprachige, barrierearme Kennzeichnungstexte;
- eigene AI-Philosophie als OPC-Portlet unter **Custom Portlets**;
- vollständig lokaler Editor für die AI-Philosophie mit visuellem Modus,
  optionalem HTML-Modus und großen deutschen sowie englischen Textfeldern;
- eigener, rein lesender Impressum-Tab mit transparenten Herstellerangaben;
- keine automatische Übertragung von Bildern oder Kundendaten an externe Dienste;
- sichere JTL-Admin-Integration mit Berechtigungs- und CSRF-Prüfung;
- kontrollierter Scan, Vorschau vor Stapeländerungen und sicherer Umgang mit veralteten Fundstellen;
- optionale Herstellernennung **supported by: Michael Gahn DESIGN**;
- abschaltbare GitHub-Updatehinweise mit positivem und negativem Zwölf-Stunden-Cache.

## Kennzeichnungsstatus

| Status | Sichtbar im Shop? | Bedeutung |
|---|---:|---|
| Ungeprüft | Nein | Das Bild wurde erfasst, aber noch nicht fachlich bewertet. |
| Keine Kennzeichnung | Nein | Das Bild wurde geprüft und benötigt nach der redaktionellen Entscheidung keine sichtbare Kennzeichnung. |
| KI-generiert | Ja | Das Bild wurde vollständig mit KI erzeugt. |
| Teilweise KI-generiert | Ja | Wesentliche Bildbestandteile wurden mit KI erzeugt oder zusammengesetzt. |
| KI-bearbeitet | Ja | Ein vorhandenes Bild wurde mit KI verändert oder erweitert. |
| Deepfake | Ja | Das Bild stellt einen KI-generierten oder manipulierten Deepfake dar. |

Das Plugin trifft diese Entscheidung nicht selbst. So bleibt die Bewertung nachvollziehbar und unter menschlicher Kontrolle.

## Unterstützte Bildquellen

Der sichere Bildscan kann lokale Referenzen aus folgenden Bereichen erfassen:

- **Artikel:** Produkt- und Artikeldarstellungen;
- **Kategorien:** Kategorie- und Navigationsbilder;
- **Hersteller:** lokale Herstellerbilder;
- **Banner oder Slider:** über JTL verwaltete Bannerbilder;
- **OnPage Composer:** lokale Bilder und Hintergrundbilder aus OPC-Inhalten;
- **Manuell hinzugefügt:** lokale Rasterbilder, die über die OPC- oder Dateimanager-Integration gekennzeichnet wurden;
- **Unbekannte Quelle:** neutraler technischer Fallback für ältere oder nicht eindeutig zuordenbare Datensätze.

Externe URLs, SVG-Dateien, Videos und unsichere oder nicht eindeutig lokale Pfade werden bewusst nicht zur direkten Kennzeichnung angeboten.

## So sieht die Kennzeichnung aus

Für jedes Bild lassen sich vier Positionen wählen:

- oben links;
- oben rechts;
- unten links;
- unten rechts.

Außerdem stehen drei Darstellungen bereit:

- **Automatisch:** orientiert sich am Farbschema des Endgeräts;
- **Hell:** heller Hintergrund mit dunkler Schrift;
- **Dunkel:** dunkler Hintergrund mit heller Schrift.

Schriftgröße, Außenabstand, Innenabstand, Eckenradius, Hintergrundunschärfe und Transparenz können innerhalb sicherer Grenzen angepasst werden. **0 % Transparenz** bedeutet einen deckenden Hintergrund, **90 %** einen nahezu durchsichtigen Hintergrund. Das Label bleibt innerhalb des erkannten Bildrahmens. Bei verlinkten Bildern bleibt das Linkziel unverändert.

Die sichtbaren Texte werden je nach Shop- oder Plugin-Sprache auf Deutsch oder Englisch ausgegeben. Zusätzlich erhält das Label eine ausführliche Beschreibung für assistive Technologien.

## Schnellstart

### 1. Paket herunterladen

Laden Sie das ZIP aus dem Bereich [GitHub Releases](https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/releases) herunter. Verwenden Sie nicht den automatisch von GitHub erzeugten Quellcode-Download, sondern das installierbare Paket `MGD_AI_Kennzeichnung-1.3.4.zip`.

Das öffentlich zugängliche Repository stellt Release-Hinweise bereit, aber
keinen Auto-Updater. Die Aktualisierung erfolgt als **manueller ZIP-Upload** im
JTL-Plugin-Manager; das Plugin installiert keine Updates automatisch.

### 2. Vorher sichern

Erstellen Sie vor Installation oder Update mindestens:

- eine vollständige Datenbanksicherung;
- eine Sicherung des Shop-Dateisystems;
- einen dokumentierten Wiederherstellungspunkt für das Pluginverzeichnis;
- ein Wartungsfenster, in dem ein Rollback möglich ist.

### 3. Plugin installieren

1. JTL-Backend öffnen.
2. **Plugins → Plugin-Manager → Upload** wählen.
3. `MGD_AI_Kennzeichnung-1.3.4.zip` hochladen.
4. Das Plugin installieren beziehungsweise aktualisieren.
5. Plugin aktivieren.
6. Plugin öffnen und die gewünschte Ansicht prüfen. Version 1.3.4 verwendet über den ausdrücklich erzeugten JTL-Backend-Renderer dessen tatsächlich aktive Smarty-Engine, verwirft dort gezielt kompilierte Vorlagen dieses Plugins und startet den lokalen Philosophie-Editor JTL-kompatibel im nachgeladenen AJAX-Tab; andere Shop-Caches bleiben unberührt.

### 4. Bilder einlesen

1. **Plugins → MGD AI Kennzeichnung → Bildverwaltung** öffnen.
2. **Sicheren Bildscan starten** wählen.
3. Nach Abschluss zur Galerie zurückkehren.
4. Mit Filtern und Sortierung die gewünschten Bilder eingrenzen.

### 5. Ein Bild kennzeichnen

1. Auf der Bildkarte **Kennzeichnen** wählen.
2. Status, Position und Darstellung festlegen.
3. Live-Vorschau prüfen.
4. **Kennzeichnung speichern** anklicken.
5. Die zugehörige Shopseite im Frontend kontrollieren.

## Bildverwaltung im Detail

Die Galerie bietet:

- Filter nach Status, Quelle und vorhandener beziehungsweise veralteter Fundstelle;
- Sortierung nach ID, Status oder Änderungsdatum;
- auf- und absteigende Reihenfolge;
- 10, 25, 50 oder 100 Einträge pro Seite;
- sichere lokale Vorschau oder neutralen Platzhalter;
- Dateiname, Status, Quelle, Fundstellenzahl und Änderungsdatum pro Karte;
- technische Detailansicht;
- Einzelauswahl und Stapelbearbeitung;
- erneuten Bildscan;
- getrennte Prüfung veralteter Fundstellen.

### OPC-Uploads aus Unterordnern – aktueller Entwicklungsstand

Die nächste, noch nicht veröffentlichte Erweiterung findet zusätzlich alle
unterstützten Uploads im OPC-Dateispeicher – auch ohne Verwendung auf einer Seite.
Das schließt beispielsweise `opc/banner/2026`, `opc/bilder/2026` und tiefere
Unterordner ein. Nach **Sicheren Bildscan starten** wählen Sie die Quelle
**OnPage Composer** und klicken auf **Galerie anzeigen**. Die bisherigen
Status-, Fundstellen- und Sortierfilter bleiben erhalten.

Gleiche Dateinamen in verschiedenen Ordnern bleiben getrennte Bilder. Dieselbe
Datei wird dagegen nur einmal angezeigt, auch wenn der Scan sie im Speicher
und auf OPC-Seiten findet. Ihre bereits gespeicherten Kennzeichnungen bleiben
unverändert. Grenzen, sichere Fehlerbehandlung und Hinweise zu Umbenennungen
finden Sie unter [OPC-Dateispeicherscan](Dokumentation/OPC-Dateispeicherscan.md).

### Stapelbearbeitung

Bei einer Stapelbearbeitung werden nur die ausdrücklich aktivierten Felder geändert. Vor der Ausführung zeigt das Plugin eine Zusammenfassung. Die Bestätigung ist an die aktuelle Admin-Sitzung gebunden, kurzlebig und nur einmal verwendbar.

### Veraltete Fundstellen

Wird ein zuvor gefundenes Bild an einer Stelle nicht mehr verwendet, markiert der nächste Scan diese konkrete Fundstelle als veraltet. Die Bilddatei und der Bilddatensatz werden nicht automatisch gelöscht. Über die Bereinigungsansicht können ausschließlich ausgewählte, nachweislich veraltete Plugin-Fundstellen entfernt werden.

## OnPage Composer und Dateimanager

Im OnPage Composer erscheint bei eindeutig erkannten lokalen Bildfeldern die Funktion **KI-Kennzeichnung bearbeiten**. Unterstützt werden insbesondere:

- Bild-Portlets;
- statische Container-Hintergrundbilder;
- eindeutig erkannte Banner- und Slider-Bildfelder.

Die Kennzeichnung wird separat gespeichert. Die OPC-Seite selbst wird dadurch weder gespeichert noch veröffentlicht.

Die optionale Dateimanager-Erweiterung erscheint nur, wenn JTLs lokaler elFinder-Dateimanager sicher erkannt wird, genau eine lokale Rasterbilddatei ausgewählt ist und die bestehende Admin-Sitzung verwendet werden kann. Nach inkompatiblen JTL-Updates fällt diese Komfortfunktion kontrolliert aus; die zentrale Bildverwaltung bleibt verfügbar.

## AI-Philosophie

Unter **Plugins → MGD AI Kennzeichnung → AI-Philosophie** können eine deutsche und eine englische Fassung der eigenen KI-Grundsätze gepflegt werden. Das zugehörige OPC-Portlet befindet sich unter **Custom Portlets → AI-Philosophie**.

So funktioniert die Bearbeitung:

1. Sprachkarte **Deutsch** oder **Englisch** öffnen.
2. Im Modus **Visuell** schreiben und mit der lokalen Werkzeugleiste
   formatieren.
3. Optional in den Modus **HTML** wechseln, um den bereinigten Quelltext zu
   bearbeiten.
4. **Beide Sprachfassungen speichern** wählen. Dadurch werden Deutsch und
   Englisch in einem geschützten Vorgang gespeichert.

Erlaubt sind Absätze, Überschriften, Listen, Hervorhebungen und sichere
HTTPS-Links. Die technische Positivliste umfasst `p`, `h2`, `h3`, `ul`, `ol`,
`li`, `strong`, `em` und `a`. Scripts, Styles, Bilder, Iframes, Formulare,
eingebettete Objekte und fremde Attribute werden entfernt. Links mit unsicherem
Protokoll, Zugangsdaten oder fremdem Port werden nicht übernommen.

Der Editor lädt **keine externen** Drittinhalte, Bibliotheken, Fonts, Icons,
CDN-Ressourcen oder Telemetrie. Alle Bestandteile liegen lokal im Plugin. Ohne
JavaScript bleiben die großen Textfelder als **No-JavaScript-Fallback**
vollständig nutzbar.

Typische Inhalte einer AI-Philosophie sind:

- wofür KI im Unternehmen eingesetzt wird;
- wie menschliche Kontrolle sichergestellt wird;
- welche Inhalte gekennzeichnet werden;
- welche Datenschutzgrundsätze gelten;
- wie Kunden Fragen oder Hinweise melden können.

## Plugin-Impressum

Unter **Plugins → MGD AI Kennzeichnung → Impressum** finden Betreiber die
geschäftlichen Kontaktdaten des Plugin-Herstellers. Der Tab ist nur im
Administrationsbereich erreichbar und dient ausschließlich der transparenten
Herstellerinformation.

Die Seite ist statisch und rein lesend: Sie verwendet keine Datenbank, nimmt
keine Eingaben entgegen, lädt keine Drittanbieter und verarbeitet keine Kunden-
oder Administratordaten. Sie ersetzt nicht das öffentliche Impressum des Shops.
Telefonnummer und E-Mail-Adresse sind für berechtigte Administratoren direkt
anklickbar.

## Plugin-Einstellungen

| Einstellung | Standard | Zweck |
|---|---:|---|
| Herstellernennung im Footer | Aus | Zeigt optional „supported by: Michael Gahn DESIGN“. |
| Updatehinweise über GitHub | An | Prüft beim adressierten Darstellungstab höchstens alle zwölf Stunden öffentliche Release-Metadaten. |
| Sprache der Kennzeichnung | Automatisch | Verwendet Shopsprache, Deutsch oder Englisch. |
| Schriftgröße | 12 px | Zulässig: 8 bis 48 px. |
| Außenabstand | 8 px | Zulässig: 0 bis 64 px. |
| Innenabstand | 6 px | Zulässig: 0 bis 32 px. |
| Eckenradius | 4 px | Zulässig: 0 bis 32 px. |
| Hintergrundunschärfe | 0 px | Zulässig: 0 bis 24 px. |
| Transparenz | 8 % | Zulässig: 0 bis 90 %. |

### Darstellung mit Live-Vorschau

Unter **Plugins → MGD AI Kennzeichnung → Darstellung** stehen links die
globalen Werte und rechts ein lokales Beispielbild. Eckenradius,
Hintergrundunschärfe und Transparenz besitzen ein Zahlenfeld und einen
Schieberegler. Jede Änderung wird sofort in der **Live-Vorschau** gezeigt.

Position und Farbschema am Beispielbild sind ausdrücklich **Nur Vorschau**. Sie
werden in diesem Tab nicht gespeichert. Position, Darstellung und Status werden
weiterhin pro Bild im Kennzeichnungsdialog oder in der Stapelbearbeitung
festgelegt. Die Vorschau bleibt vollständig lokal; erst **Speichern** ändert
globale Shopwerte.

## Datenschutz und Sicherheit

Das Plugin wurde nach dem Prinzip der Datenminimierung entwickelt:

- keine automatische KI-Analyse;
- kein Upload von Bildern zu KI- oder Cloud-Diensten;
- keine Verarbeitung von Kunden-, Bestell- oder Zahlungsdaten;
- keine eigenen öffentlichen Admin-Endpunkte;
- Nutzung der bestehenden JTL-Admin-Sitzung und Pluginberechtigung;
- CSRF-Schutz für schreibende Aktionen;
- geschlossene Positivlisten für Status, Position, Darstellung und Quellen;
- ausschließlich lokale Vorschaupfade aus erlaubten Rasterbildbereichen;
- begrenzte Scan-, Listen- und Stapelgrößen;
- HTML-Bereinigung für die AI-Philosophie;
- keine Drittinhalte, externen Editorbibliotheken, Webfonts, Icons oder
  Telemetrie im Philosophie-Editor;
- Updateabfrage nur im adressierten Darstellungstab und mit lokalem positiven wie negativen Zwölf-Stunden-Cache;
- keine Secrets, Tokens oder Bildinhalte in der Updateabfrage.

Bei aktivierter Updateprüfung erhält GitHub technisch die Server-IP, den
Zeitpunkt und den festen User-Agent
`MGD-AI-Kennzeichnung-JTL-Shop-5/1.3.4`. Bilder, Kunden-, Shop- und
Formulardaten werden nicht übertragen. Auch ein Fehler oder ein Ergebnis ohne
neue Version wird zwölf Stunden zwischengespeichert.

Weitere Details stehen in [Datenschutz und Sicherheit](Dokumentation/Datenschutz-und-Sicherheit.md) und im [vollständigen GitHub-Handbuch](https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/blob/main/wiki/Home.md).

## Voraussetzungen

- JTL-Shop 5.7.2 oder neuer;
- PHP 8.1 oder neuer;
- HTTPS;
- Schreibrechte für die regulären JTL-Plugin- und Cacheverzeichnisse;
- ein berechtigtes JTL-Admin-Konto;
- empfohlen: Standardtemplate NOVA oder ein sauber abgeleitetes NOVA-Child-Theme.

Die Bildausgabe wurde mit Version 1.1.1 unter NOVA sowie OnvisTheme auf Basis NOVA 1.7.1 geprüft. Version 1.2.0 ergänzte den geschützten Impressum-Tab, Version 1.2.1 die globale Darstellung und Transparenz, Version 1.3.0 den vollständig lokalen Philosophie-Editor, Version 1.3.3 die Neucompilierung über JTLs tatsächlich aktive Backend-Smarty-Engine und Version 1.3.4 den zuverlässigen Editorstart im AJAX-Tab. Andere Templates können funktionieren, sollten aber zuerst in einer getrennten Testumgebung geprüft werden.

## Bewusste Grenzen

- Das Plugin ist kein KI-Detektor und keine Rechtsberatung.
- Es kann nicht entscheiden, ob eine Kennzeichnung gesetzlich erforderlich ist.
- Es verändert keine Medienmetadaten und erzeugt keine unsichtbaren Wasserzeichen.
- Externe Bilder werden nicht heruntergeladen oder analysiert.
- SVG wird nicht als Galerie-Vorschau oder direkte Dateimanager-Auswahl zugelassen.
- Die Dateimanager-Komfortfunktion hängt von der intern verwendeten JTL/elFinder-Struktur ab und kann nach JTL-Updates kontrolliert deaktiviert bleiben.
- Das Frontend verarbeitet aus Sicherheits- und Leistungsgründen höchstens 500 sichtbare Kennzeichnungen pro Seitenaufruf.

## Update und Rollback

Neue Versionen sollten immer zuerst auf einer getrennten Testinstallation geprüft werden. Verwenden Sie für Dev und Live exakt dasselbe, per SHA-256 geprüfte ZIP.

Der sichere Ablauf lautet: Pflichtbackup, manueller ZIP-Upload auf Dev,
Funktions- und Sichtprüfung, neues Live-Backup und erst danach dasselbe Paket auf
Live. Halten Sie das bisherige Pluginverzeichnis für einen schnellen Rollback
bereit und leeren Sie nach Update oder Rückfall Shop-, Plugin- und
Template-Cache.

Bei einem Fehler:

1. Plugin im JTL-Plugin-Manager deaktivieren.
2. Shop- und Template-Cache leeren.
3. gesichertes Pluginverzeichnis wiederherstellen;
4. falls erforderlich die gesicherten Plugin-Tabellen wiederherstellen;
5. Frontend und Backend erneut prüfen.

Eine Deinstallation mit Datenlöschung ist kein normaler Rollback. Ohne ausdrücklich ausgewählte Datenlöschung bleiben die Kennzeichnungsdaten für eine spätere Neuinstallation erhalten.

## Dokumentation

- [GitHub-Handbuch – vollständige Wiki-Dokumentation](https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/blob/main/wiki/Home.md)
- [Admin-Bildverwaltung](Dokumentation/Admin-Bildverwaltung.md)
- [OPC-Kennzeichnung](Dokumentation/OPC-Kennzeichnung.md)
- [Installation, Test und Rollback](Dokumentation/Installation-und-Livetest.md)
- [Datenschutz und Sicherheit](Dokumentation/Datenschutz-und-Sicherheit.md)
- [Darstellung und Live-Vorschau](Dokumentation/Darstellung.md)
- [Release 1.3.4](Dokumentation/Release-1.3.4.md)
- [Release 1.3.3](Dokumentation/Release-1.3.3.md)
- [Release 1.3.2](Dokumentation/Release-1.3.2.md)
- [Release 1.3.1](Dokumentation/Release-1.3.1.md)
- [Release 1.3.0](Dokumentation/Release-1.3.0.md)
- [Monetarisierung und Marketplace-Regeln](Dokumentation/Monetarisierung-und-Marketplaces.md)
- [Plugin-Impressum](Dokumentation/Impressum.md)
- [Technische Dokumentationsübersicht](Dokumentation/README.md)
- [Änderungsprotokoll](CHANGELOG.md)
- [Sicherheitsmeldungen](SECURITY.md)

## Entwicklung und Qualitätssicherung

```bash
composer install
composer validate --strict
composer test
composer test:js
composer analyse
composer style
bash scripts/build-release.sh
unzip -t dist/MGD_AI_Kennzeichnung-1.3.4.zip
shasum -a 256 dist/MGD_AI_Kennzeichnung-1.3.4.zip
```

Die Testumgebung umfasst PHP-Unit- und Integrationstests, JavaScript-Tests, statische PHP-Analyse, Formatprüfung, Strukturverträge und die Prüfung des installierbaren ZIP-Pakets.

## Support und Beiträge

- Fehler bitte über [GitHub Issues](https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/issues) melden.
- Sicherheitsrelevante Hinweise bitte gemäß [SECURITY.md](SECURITY.md) vertraulich übermitteln.
- Bei Fehlerberichten keine Passwörter, Tokens, Kundendaten, Bestelldaten, vollständigen Datenbankauszüge oder personenbezogenen Serverprotokolle anhängen.
- Beiträge sind willkommen; technische Hinweise stehen in [CONTRIBUTING.md](CONTRIBUTING.md).

## Lizenz

MGD AI Kennzeichnung für JTL-Shop 5 wird unter `GPL-3.0-or-later` veröffentlicht. Siehe [LICENSE](LICENSE).
