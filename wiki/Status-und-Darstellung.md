# Status und Darstellung

## Die sechs Status

### Ungeprüft

Das Bild ist technisch bekannt, wurde aber noch nicht fachlich bewertet. Im Frontend erscheint kein Label.

### Keine Kennzeichnung

Das Bild wurde geprüft und nach Ihrer redaktionellen Entscheidung bewusst ohne sichtbares Label belassen. Dieser Zustand unterscheidet sich von **Ungeprüft**.

### KI-generiert

Für Bilder, die vollständig mit einem KI-System erzeugt wurden. Sichtbarer Text:

- Deutsch: **KI-GENERIERT**
- Englisch: **AI-GENERATED**

### Teilweise KI-generiert

Für Bilder, deren wesentliche Bestandteile mit KI erzeugt oder zusammengesetzt wurden. Sichtbarer Text:

- Deutsch: **TEILWEISE KI-GENERIERT**
- Englisch: **PARTIALLY AI-GENERATED**

### KI-bearbeitet

Für bestehende Bilder, die mit KI verändert, erweitert oder wesentlich überarbeitet wurden. Sichtbarer Text:

- Deutsch: **MIT KI BEARBEITET**
- Englisch: **AI-MODIFIED**

### Deepfake

Für einen KI-generierten oder manipulierten Deepfake. Sichtbarer Text:

- Deutsch: **KI-DEEPFAKE**
- Englisch: **AI DEEPFAKE**

## Wie wähle ich den richtigen Status?

Legen Sie unternehmensinterne Kriterien fest. Hilfreiche Fragen:

- Wurde das gesamte Motiv synthetisch erzeugt?
- Stammen nur einzelne Bestandteile aus einer KI-Generierung?
- Wurde ein echtes Foto lediglich mit KI retuschiert oder erweitert?
- Wird eine reale Person, Aussage oder Situation täuschend echt simuliert?
- Ist die Bearbeitung für die Wahrnehmung des Bildes wesentlich?

Das Plugin beantwortet diese Fragen nicht automatisch und ersetzt keine Rechtsberatung.

## Position

Jedes verwaltete Bild speichert seine eigene Position:

- oben links;
- oben rechts;
- unten links;
- unten rechts.

Wählen Sie eine Ecke, in der das Label keine Produkteigenschaft, keinen Text und keine wichtige Bildaussage verdeckt.

Version 1.1.1 setzt das Label in den erkannten Bildrahmen. Das gilt für:

- normale Bilder;
- Bilder in Links;
- responsive `picture`-Ausgaben;
- lokale OPC-Hintergrundbilder;
- verzögert geladene Hintergründe über `data-image-src`.

## Darstellung

### Automatisch

Orientiert sich am bevorzugten hellen oder dunklen Farbschema des Endgeräts. Diese Variante ist flexibel, sollte aber auf den wichtigsten Shopseiten geprüft werden.

### Hell

Heller Hintergrund, dunkle Schrift. Geeignet für überwiegend dunkle Bildbereiche.

### Dunkel

Dunkler Hintergrund, helle Schrift. Geeignet für überwiegend helle oder wechselnde Motive.

## Sprache und Barrierefreiheit

Die Sprache kann automatisch aus der Shopsprache übernommen oder fest auf Deutsch beziehungsweise Englisch eingestellt werden.

Zusätzlich zum sichtbaren Kurztext enthält das Label eine ausführliche Beschreibung für assistive Technologien. Der Status wird nicht ausschließlich über Farbe vermittelt.

## Abstände und Form

Globale Plugin-Einstellungen begrenzen:

- Schriftgröße: 8 bis 48 px;
- Außenabstand: 0 bis 64 px;
- Innenabstand: 0 bis 32 px;
- Eckenradius: 0 bis 32 px;
- Hintergrundunschärfe: 0 bis 24 px.

Unzulässige Werte werden nicht frei in CSS übernommen, sondern auf sichere Grenzen zurückgeführt.
