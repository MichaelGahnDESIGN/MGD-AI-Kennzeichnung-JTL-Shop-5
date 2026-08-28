# Entwurf: Darstellung, Live-Vorschau und Release 1.2.1

## Ziel

Version **1.2.1** verbessert die Bedienbarkeit der globalen KI-Kennzeichnung im JTL-Administrationsbereich. Shop-Betreiber sollen die sichtbare Kennzeichnung verständlich konfigurieren und ihre Wirkung vor dem Speichern unmittelbar an einem lokalen Beispielbild prüfen können.

Zusätzlich wird die freiwillige Herstellernennung sprachlich angepasst und die datensparsame GitHub-Versionsprüfung bei Neuinstallationen standardmäßig aktiviert.

## Bedienkonzept

Das Plugin erhält zwischen **AI-Philosophie** und **Impressum** den neuen geschützten Tab **Darstellung**. Eine eigene Plugin-Seite ist gegenüber einem Eingriff in die generische JTL-Einstellungsansicht vorzuziehen: Sie benötigt keine Änderung am Shop-Kern, bleibt über JTL-Updates hinweg nachvollziehbar und kann die gewünschte Live-Vorschau barrierearm abbilden.

Die Seite besteht auf ausreichend breiten Bildschirmen aus zwei Spalten:

- links stehen die Einstellungen mit klaren Bezeichnungen, Einheiten, Wertebereichen und einem bewussten Speichern-Button;
- rechts erscheint eine feststehende Vorschau mit einem lokal mitgelieferten Produktbild „Michael Gahn DESIGN Schuh“ und dem Kennzeichnungsstatus **KI-generiert**.

Auf schmalen Bildschirmen werden die Bereiche untereinander angeordnet. Die Vorschau folgt den Einstellungen, damit Formulareingaben in einer natürlichen Reihenfolge erreichbar bleiben.

## Darstellungsoptionen

Die Seite übernimmt alle bereits vorhandenen globalen Darstellungswerte:

- Sprache der Kennzeichnung;
- Position;
- Farbschema;
- Schriftgröße;
- Außenabstand;
- Innenabstand;
- Eckenradius;
- Hintergrundunschärfe.

Neu hinzu kommt die **Transparenz des Label-Hintergrunds**. Sie wird in Prozent angegeben. `0 %` bedeutet einen vollständig deckenden Hintergrund; `90 %` ist die zugelassene Obergrenze. Text und Rahmen bleiben unabhängig davon sichtbar. Der Standardwert beträgt `8 %` und entspricht annähernd der bisherigen dunklen Darstellung.

Eckenradius, Hintergrundunschärfe und Transparenz besitzen jeweils:

1. ein beschriftetes Zahlenfeld für eine genaue Eingabe;
2. einen synchronisierten Schieberegler für eine schnelle visuelle Anpassung;
3. eine sichtbare Einheit und einen erklärten sicheren Wertebereich.

Die bestehenden Grenzen bleiben erhalten:

- Eckenradius: 0 bis 32 Pixel;
- Hintergrundunschärfe: 0 bis 24 Pixel;
- Transparenz: 0 bis 90 Prozent.

Alle übrigen Zahlenwerte behalten ebenfalls ihre bereits implementierten sicheren Grenzen.

## Live-Vorschau

Jede gültige Änderung wird sofort ausschließlich in der lokalen Vorschau dargestellt. Die Vorschau verändert weder den öffentlichen Shop noch gespeicherte Daten. Erst der Button **Speichern** überträgt die geprüften Werte an den Server.

Das Beispielbild wird als optimierte lokale Bilddatei Bestandteil des Plugins. Es lädt keine externe Ressource, enthält keine personenbezogenen Daten und löst keine Netzwerkanfrage aus. Bildtitel und Alternativtext benennen das fiktive Produkt verständlich als „Michael Gahn DESIGN Schuh“.

Die JavaScript-Logik wird in kleine Module aufgeteilt:

- Synchronisierung von Zahlenfeld und Schieberegler;
- Berechnung und Anwendung der Vorschauvariablen;
- Formularstatus und Rücksetzen auf gespeicherte Werte.

Ohne JavaScript bleibt das Formular vollständig speicherbar; lediglich die unmittelbare Vorschauaktualisierung und die Regler-Synchronisierung entfallen.

## Speicherung und Abwärtskompatibilität

Die Darstellungswerte werden in einer eigenen, eigentumsmarkierten Plugin-Tabelle gespeichert. Dadurch muss das Plugin keine internen JTL-Konfigurationstabellen direkt verändern.

Beim ersten Aufruf beziehungsweise solange noch kein eigener Datensatz vorhanden ist, werden die bisherigen Werte aus der JTL-Plugin-Konfiguration übernommen. So bleibt die bereits eingerichtete Positionierung und Gestaltung nach dem Update erhalten. Nach dem ersten bewussten Speichern ist der neue Datensatz die maßgebliche Quelle für die Darstellung.

Die neue Migration:

- prüft vor dem Anlegen, ob der Tabellenname frei oder nachweislich Eigentum des Plugins ist;
- verwendet denselben Eigentumsmarker und dieselbe Sperrstrategie wie die vorhandenen Plugin-Tabellen;
- wird in der sicheren Datenlöschung des Plugins berücksichtigt;
- speichert ausschließlich globale Darstellungswerte, keine Kunden-, Bestell- oder Nutzungsdaten.

## Validierung und Sicherheit

Der Darstellungstab ist ausschließlich über den authentifizierten JTL-Plugin-Kontext erreichbar. Für Lesen und Speichern gelten dieselben Admin-Berechtigungen wie für die Bildverwaltung.

Beim Speichern gelten folgende Regeln:

- ausschließlich POST für Änderungen;
- gültiges JTL-CSRF-Token;
- feste Liste erlaubter Formularfelder;
- feste Auswahlwerte für Sprache, Position und Farbschema;
- streng geprüfte Ganzzahlen innerhalb der dokumentierten Grenzen;
- atomare Speicherung in einer Datenbanktransaktion;
- keine Ausgabe roher Eingabewerte in Fehlermeldungen oder Logs.

Freie CSS-Klassen, HTML, URLs oder beliebige Labeltexte werden nicht angenommen. Dadurch kann die neue Seite weder Skriptcode noch fremde Ressourcen in den Shop einschleusen.

## Herstellernennung

Wenn die freiwillige Herstellernennung aktiviert ist, lautet die sichtbare Ausgabe künftig:

> supported by: Michael Gahn DESIGN

Nur **Michael Gahn DESIGN** ist verlinkt. Das Ziel ist `https://Michael-Gahn.de` und öffnet sich mit `target="_blank"` sowie `rel="noopener noreferrer"` in einem neuen Tab. Der zugängliche Linktext erklärt das neue Fenster. Es gibt weiterhin kein Tracking und keine externe Einbindung.

## GitHub-Updatehinweise

`Updatehinweise über GitHub abrufen` erhält für **Neuinstallationen** den Standardwert **Ja**. Bestehende Installationen behalten ihren bereits gespeicherten Wert und werden durch das Update nicht ungefragt überschrieben.

Die vorhandenen Datenschutz- und Lastschutzregeln bleiben bestehen: Es werden ausschließlich öffentliche Release-Metadaten abgefragt, höchstens einmal innerhalb von zwölf Stunden und ohne Übertragung von Kunden- oder Bestelldaten.

## Technische Struktur

Die Umsetzung wird in klar getrennte Dateien gegliedert:

- Menü-Einstieg und Template für den Darstellungstab;
- Admin-Service für Berechtigung, CSRF-Prüfung, Validierung und Transaktion;
- Repository und Migration für die globalen Darstellungswerte;
- unveränderliches Wertemodell für geprüfte Einstellungen;
- getrennte CSS-Datei für das zweispaltige Layout und die Vorschau;
- getrennte JavaScript-Module für Feldkopplung und Live-Vorschau;
- lokale Bilddatei für den fiktiven Schuh;
- kleine Unit-, Integrations-, Struktur- und JavaScript-Tests.

Der Frontend-Bootstrap lädt die gespeicherten Darstellungswerte fehlertolerant. Bei einem unerwarteten Datenbankproblem fallen die Werte auf die bisherige JTL-Konfiguration zurück; die öffentliche Shop-Seite darf deshalb nicht ausfallen.

## Dokumentation und Veröffentlichung

Folgende Artefakte werden für **1.2.1** aktualisiert:

- Plugin-Version in `info.xml`;
- README und Changelog;
- versioniertes Wiki beziehungsweise Benutzerhandbuch;
- Release-Prüfungen und Paketmetadaten;
- Installations-ZIP mit Prüfsumme;
- Git-Commit auf `main`, Tag `v1.2.1` und GitHub-Release mit dem ZIP als Asset.

Eine Installation auf `dev.onvis-shop.de` oder `onvis-shop.de` ist nicht Bestandteil dieses Schritts. Anschließend wird geprüft, ob JTL-Shop das veröffentlichte GitHub-Release als Updatehinweis erkennt und den vorgesehenen Updateablauf anbietet.

## Abnahme

Die Umsetzung ist abgenommen, wenn:

1. der neue Darstellungstab ohne Änderung am JTL-Shop-Kern erreichbar ist;
2. gespeicherte Werte eines Updates auf 1.2.1 erhalten bleiben;
3. Eckenradius, Unschärfe und Transparenz Zahlenfeld und Regler synchron anbieten;
4. alle Designwerte die lokale Vorschau unmittelbar und korrekt verändern;
5. ungültige, fremde oder nicht autorisierte Anfragen sicher abgewiesen werden;
6. der öffentliche Shop bei fehlenden Darstellungsdaten oder einem Lesefehler mit sicheren Rückfallwerten weiterläuft;
7. die Herstellernennung exakt wie freigegeben ausgegeben und sicher verlinkt wird;
8. Updatehinweise bei Neuinstallationen standardmäßig aktiviert sind, ohne bestehende Entscheidungen zu überschreiben;
9. alle automatisierten Tests, statischen Analysen und Release-Paketprüfungen erfolgreich sind;
10. GitHub `v1.2.1` mit dokumentiertem ZIP und SHA-256-Prüfsumme veröffentlicht.
