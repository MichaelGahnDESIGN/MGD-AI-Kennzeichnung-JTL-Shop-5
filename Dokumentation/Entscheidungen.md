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
