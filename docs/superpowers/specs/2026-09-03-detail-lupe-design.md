# Design-Entwurf: separate Detail-Lupe für KI-Labels

## Entscheidung und Freigabestand

Michael hat die lokale, bedienbare Vorschau am 03.09.2026 mit „top!“ bestätigt.
Diese Spezifikation hält den Entwurf für den anschließenden Einbau fest.
Die Umsetzung wurde anschließend mit „go“ freigegeben und lokal im Zweig
`codex/detail-lupe` eingebaut. Ein neues Release und Shop-Updates sind noch
nicht erfolgt. Nachweise stehen in `Dokumentation/Detail-Lupe-Abnahme.md`.

## Darstellung

- Das vorhandene Schuhbild erscheint wieder ohne Schachbrett und ohne Musterstreifen.
  Das Label sitzt innerhalb des Bildbereichs an der gewählten Ecke.
- Direkt unter dem Produktbild steht eine feste Box „Detail-Lupe“, beschriftet
  mit „2× vergrößert“. Sie verdeckt das Produkt nicht.
- Der abstrakte Testhintergrund besteht aus bunten Flächen, feinen kontrastreichen
  Linien und einer Kreisform. Er wird ausschließlich lokal mit CSS erzeugt.
- Das Label bleibt in der Lupe mittig. Die gesamte Lupenszene wird zweifach
  vergrößert, einschließlich Schrift, Innenabstand, Radius und Unschärfe.
  Die Lupe ist eine Effektprobe, kein echter Ausschnitt des Schuhbilds.
- Transparenz und Unschärfe werden zusätzlich als Prozent- und Pixelwert angezeigt.
  Bei 0 % Transparenz erklärt ein Hinweis, warum die Unschärfe nicht sichtbar ist.
- Auf schmalen Bildschirmen stehen die Bereiche untereinander, ohne horizontalen
  Seitenüberlauf. Beide Vorschauen bleiben vollständig bedienbar.

## Bedienung und gespeicherte Werte

Beide Labels erhalten dieselben validierten Designwerte und denselben Sprachtext.
Die bestehenden Zahlenfelder, Schieberegler, Wertebereiche und der Speichern-Button
bleiben erhalten. Zahlenfeld und Regler eines Werts bleiben synchron.
Farbschema gilt für beide Vorschauen; die Positionswahl betrifft das Produktbild.
Außenabstand bleibt am Produkt sichtbar, die Detailprobe bleibt bewusst zentriert.

Die tatsächlichen gespeicherten Einstellungen werden geladen. Die Demo-Werte der
lokalen Vorschau ersetzen keine Shopwerte. Der reine Demo-Zurücksetzen-Button
und die Kennzeichnung „Lokaler Entwurf“ werden nicht ins Plugin übernommen.
Position und Farbschema der Vorschau bleiben weiterhin nicht persistente Optionen.

## Technischer Einbau und Sicherheit

Die Änderung bleibt auf den Darstellungstab begrenzt. Eine eigene Template-Datei
für die Detail-Lupe und eine eigene lokale CSS-Datei halten die Darstellung getrennt.
Das vorhandene validierte Modell in `display-preview.mjs` wird wiederverwendet;
die Anbindung in `display-controls.mjs` versorgt beide Ansichten gemeinsam.
Fehlende optionale Lupenelemente dürfen die bisherige Produktvorschau nicht stoppen.
Geänderte lokale Ressourcen erhalten eine nachvollziehbare Cachekennung.

Keine Datenbankänderung, keine neuen externen Ressourcen, kein Tracking und keine
Änderung von Shopbildern oder vorhandenen Bildkennzeichnungen. Speicherung und
CSRF-Schutz bleiben unverändert. Unschärfe nutzt weiterhin `backdrop-filter` und
die Safari-Variante. Ohne Browserunterstützung bleibt die transparente Darstellung
benutzbar; die Oberfläche darf keine tatsächlich nicht sichtbare Unschärfe versprechen.

## Abnahme für den Einbau

1. Automatische Tests prüfen die Übertragung derselben Werte an beide Labels,
   Zahl-/Regler-Synchronität, Eingabegrenzen und fehlende optionale Elemente.
2. Im Browser werden 0, mittlere und maximale Transparenz/Unschärfe verglichen;
   nur der Hintergrund hinter dem Label wird unscharf, nicht die Schrift.
3. Alle vier Produktecken, beide Farbschemata, Sprachen und große Schrift werden
   geprüft; die Lupe bleibt mittig und verursacht keinen horizontalen Seitenüberlauf.
4. Der bisher korrigierte Speichervorgang bleibt erhalten: keine weiße Seite,
   Erfolgsmeldung und unveränderte Werte nach erneutem Laden.
5. Lokale Ressourcen und das Installationspaket werden geprüft. Ein Deployment
   erfolgt getrennt, zunächst auf Dev; Live-Shops werden nicht für Entwürfe verändert.
