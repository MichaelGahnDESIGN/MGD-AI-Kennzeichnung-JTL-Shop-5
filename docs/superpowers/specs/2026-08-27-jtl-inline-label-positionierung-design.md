# Sichere Inline-Positionierung der KI-Kennzeichnung

## Ausgangslage

Die gespeicherten Werte für Status, Position und Darstellung sind in der
Dev-Datenbank korrekt. Die fehlerhafte Ausgabe entsteht erst im Frontend:

- Bei einem verlinkten OPC-Bild wird das Label an den umgebenden Inline-Link
  angehängt. Dessen Abmessungen sind kein verlässlicher Positionierungsrahmen.
- Ein OPC-Container mit Hintergrundbild enthält kein `<img>`-Element. Die
  bisherige Integration sucht ausschließlich nach `<img>` und übersieht diesen
  Fall deshalb vollständig.

Die Korrektur wird ausschließlich auf `dev.onvis-shop.de` entwickelt und
geprüft. `onvis-shop.de` bleibt unverändert.

## Gewählte Lösung

Normale Bilder erhalten einen eigenen, neutralen Positionierungsrahmen. Bei
einer responsiven `<picture>`-Ausgabe wird das vollständige `<picture>`
umschlossen; andernfalls wird das `<img>` selbst umschlossen. Der Rahmen ist
relativ positioniert und richtet seine Größe an der sichtbaren Bildausgabe aus.
Das absolut positionierte Label liegt dadurch zuverlässig innerhalb der
gewählten Bildecke – unabhängig davon, ob das Bild verlinkt ist.

OPC-Hintergrundbilder werden zusätzlich über sichere Attributselektoren für
`style` und `data-image-src` gefunden. Das Label wird direkt in den bereits
positionierten OPC-Container eingefügt. Selektoren entstehen weiterhin nur aus
einem streng geprüften Dateinamen; freie Datenbankwerte werden nicht als CSS
oder HTML übernommen.

## Komponenten und Verantwortlichkeiten

- `FrontendLabelTargetLocator` erzeugt ausschließlich die erlaubten Selektoren
  für Bild- und Hintergrundfundstellen. Dadurch bleibt die Selektorlogik aus
  dem Dokument-Integrator ausgelagert und einzeln testbar.
- `FrontendDocumentIntegrator` entscheidet anhand des gefundenen Elements, ob
  ein Bildrahmen ergänzt oder ein vorhandener Hintergrund-Container verwendet
  wird. Bereits gekennzeichnete Ziele werden nicht nochmals verändert.
- `mgd-ai-labels.css` definiert den neutralen Bildrahmen und stellt sicher, dass
  Bild und Label ohne sichtbare Layoutverschiebung gemeinsam dargestellt
  werden.
- `LabelRenderer` und das gespeicherte Datenmodell bleiben unverändert. Status,
  Position, Darstellung und barrierearme Texte verwenden weiterhin die
  vorhandenen geschlossenen Wertelisten.

## Datenfluss

1. Der Frontend-Hook lädt ausschließlich sichtbare Kennzeichnungen.
2. Der Dateiname des lokalen Assets wird wie bisher streng geprüft.
3. Die Zielsuche prüft sowohl echte Bildausgaben als auch lokale
   Hintergrundbildattribute.
4. Ein echtes Bild erhält genau einen eigenen Rahmen; ein Hintergrundbild nutzt
   genau seinen vorhandenen Container.
5. Das gerenderte Label wird in diesen Rahmen eingefügt und über die bereits
   vorhandene Positionsklasse ausgerichtet.

## Fehler- und Sicherheitsverhalten

- Unbekannte Dateinamen, unvollständige Hook-Dokumente und nicht unterstützte
  DOM-Strukturen bleiben ohne Ausgabe und ohne Shopfehler.
- Bilddateien, OPC-Seitendaten und JTL-Templates werden nicht verändert.
- Es werden keine externen Ressourcen, Nutzerdaten oder zusätzlichen
  JavaScript-Nutzdaten übertragen.
- Eine fehlende oder bereits vorhandene Umhüllung darf keine doppelte
  Kennzeichnung erzeugen.

## Abnahmekriterien

- `oben links`, `oben rechts`, `unten links` und `unten rechts` liegen jeweils
  innerhalb der sichtbaren Bildfläche.
- Verlinkte OPC-Bilder behalten Linkziel, responsive Darstellung und Layout.
- Statische und per `data-image-src` geladene OPC-Hintergrundbilder zeigen das
  Label innerhalb ihres Containers.
- Ein bereits integriertes Ziel erhält kein zweites Label.
- Bestehende Produktbilder und manuell markierte OPC-Elemente funktionieren
  weiterhin.
- Automatisierte PHP-, JavaScript-, Struktur- und Pakettests bleiben grün.
- Die visuelle Prüfung erfolgt ausschließlich auf `dev.onvis-shop.de` in
  Desktop- und schmaler Ansicht.
