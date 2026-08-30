# Produkt- und Sicherheitsentscheidungen

## Menschliche Einstufung statt automatischer Erkennung

Das Plugin führt keine automatische KI-Erkennung durch. Technische Detektoren
können Fehlentscheidungen erzeugen und würden häufig eine Übertragung von
Bilddaten an externe Dienste erfordern. Die Einstufung bleibt deshalb bewusst
bei einem berechtigten Menschen.

## Metadaten statt veränderter Originalbilder

Kennzeichnungen werden als getrennte Plugin-Daten gespeichert und im Frontend
als Overlay ausgegeben. Dadurch bleiben Originaldatei, Kompression,
Bildmetadaten, Linkziel und Wiederverwendbarkeit des Bildes unverändert.

## Lokale Verarbeitung

Der Scan erfasst ausschließlich lokale technische Bildreferenzen. Bilder
werden weder analysiert noch zu einem Drittanbieter hochgeladen. Öffentliche
GitHub-Release-Metadaten werden nur bei ausdrücklich aktivierten
Updatehinweisen und höchstens alle zwölf Stunden abgefragt.

## Lokaler Editor mit doppelter Sicherheitsgrenze

Der Philosophie-Editor verwendet native Browserfunktionen und ausschließlich
lokale Plugin-Dateien. Eine kleine Positivliste bereinigt Inhalte beim
Moduswechsel, damit Redakteure sofort verständliches Feedback erhalten. Beim
Speichern prüft der PHP-Sanitizer erneut und bleibt die maßgebliche
Sicherheitsgrenze. Die Original-Textfelder bleiben als No-JavaScript-Fallback
erhalten, damit eine Komfortfunktion nie Voraussetzung für redaktionelle Arbeit
wird.

## Eigener Darstellungstab mit lokaler Live-Vorschau

Globale Werte werden nicht mehr zwischen allgemeinen JTL-Einstellungen
versteckt, sondern in einem geschützten, zweispaltigen Formular gepflegt. Das
Beispielbild ist Bestandteil des Plugins und die Live-Vorschau läuft nur im
Browser. Position und Farbschema sind **Nur Vorschau**, weil diese beiden Werte
fachlich zum einzelnen Bild gehören. Erst **Speichern** ändert die globalen
Shopwerte.

## Transparenz als globaler, begrenzter Wert

Transparenz wird als ganze Zahl von 0 bis 90 Prozent gespeichert. 0 % bedeutet
deckend, 90 % nahezu durchsichtig. Der daraus berechnete CSS-Wert entsteht aus
geprüften Zahlen und nicht aus freier Benutzereingabe.

## Hinweis statt Auto-Updater

Version 1.3.0 installiert keine Updates automatisch. Die optionale Prüfung
fragt nur den festen GitHub-Endpunkt ab und speichert auch erfolglose Versuche
zwölf Stunden. Auch beim öffentlichen Repository bleibt der zuverlässige Weg
ein geprüfter manueller ZIP-Upload nach Backup und Dev-Test.

## Kostenloses Grund-Plugin ohne technische Sperren

Version 1.3.0 enthält keine Lizenzschlüssel, Zahlung, Sperren, Telemetrie oder
Pro-Freischaltung. Einnahmen sollen zunächst über klar getrennte Leistungen wie
Installation, Schulung, Wartung und Beratung entstehen. Ein späteres Pro-Add-on
oder SaaS-Angebot wird erst nach plattformspezifischer Prüfung separat
entwickelt.

## Geschlossene Auswahlwerte

Status, Position, Darstellung, Sprache und Quelle stammen aus festen
Positivlisten. Freie CSS-Klassen, freie HTML-Fragmente oder unbeschränkte
Zahlenwerte sind nicht Bestandteil der Kennzeichnungseinstellungen.

## Eigene Positionsrahmen im Frontend

Eine sichtbare Kennzeichnung wird an den kleinstmöglichen gültigen Rahmen der
Bildfläche angehängt. Bei `<picture>` wird der äußere Link oder Block verwendet,
weil ein Label kein gültiges Kind von `<picture>` ist. Lokale
Hintergrundbilder erhalten den vorhandenen OPC-Container als Rahmen.

## Kontrollierter Komfort im Dateimanager

Die elFinder-Integration wird nur aktiviert, wenn JTLs bekannte lokale
Struktur eindeutig erkannt wird. Bei Abweichungen bleibt sie ohne Fehler aus.
Die zentrale Bildverwaltung ist der dauerhaft unterstützte Hauptweg.

## Keine automatische Veröffentlichung

Das Speichern einer Kennzeichnung veröffentlicht keine OPC-Seite und verändert
keinen redaktionellen Inhalt. Veröffentlichung und Kennzeichnung bleiben zwei
getrennte, bewusst auszuführende Aktionen.

## Sichere Deinstallation

Bei einer normalen Deinstallation können die Plugin-Daten erhalten bleiben.
Eine ausdrücklich gewählte Datenlöschung greift nur auf Tabellen zu, deren
Eigentumsmarker und Struktur zum Plugin passen. Fremde oder unerwartet
veränderte Tabellen werden nicht automatisch gelöscht.
