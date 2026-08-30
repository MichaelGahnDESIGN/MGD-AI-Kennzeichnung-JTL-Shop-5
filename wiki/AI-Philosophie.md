# AI-Philosophie

Mit der AI-Philosophie erklären Sie Besuchern, wie Ihr Unternehmen künstliche
Intelligenz einsetzt, wie Menschen Inhalte prüfen und welche Grundsätze dabei
gelten. Version 1.3.0 stellt dafür zwei große, untereinander angeordnete
Sprachkarten bereit.

## Inhalte pflegen

Öffnen Sie:

**Plugins → MGD AI Kennzeichnung → AI-Philosophie**

Danach gehen Sie so vor:

1. Wählen Sie die Sprachkarte **Deutsch** oder **Englisch**.
2. Schreiben und formatieren Sie im Modus **Visuell**.
3. Wechseln Sie bei Bedarf in den Modus **HTML**, um den bereinigten Quelltext
   zu prüfen oder direkt zu bearbeiten.
4. Kehren Sie bei Bedarf zu **Visuell** zurück. Der Inhalt wird dabei erneut
   bereinigt und synchronisiert.
5. Wählen Sie **Beide Sprachfassungen speichern**. Deutsch und Englisch werden
   gemeinsam über den geschützten JTL-Admin-Ablauf gespeichert.

Im Frontend lädt das OPC-Portlet die zur aktuellen Shopsprache passende
Fassung. Die beiden Sprachkarten arbeiten unabhängig voneinander; der
Speichern-Knopf übermittelt beide geprüften Werte gemeinsam.

## Visueller Modus

Die lokale Werkzeugleiste bietet bewusst nur die benötigten Funktionen:

- normaler Absatz;
- Überschrift der Ebene 2 oder 3;
- ungeordnete und geordnete Liste;
- fett beziehungsweise stark hervorgehobener Text;
- kursiver beziehungsweise betonter Text;
- sicherer HTTPS-Link.

Der Linkdialog akzeptiert ausschließlich sichere HTTPS-Ziele ohne eingebettete
Zugangsdaten oder abweichenden Port. Es wird kein externer Dialog, Editor oder
Webdienst geladen.

## HTML-Modus und erlaubte Elemente

Der HTML-Modus ist für Redakteure gedacht, die den bereinigten Quelltext
kontrollieren möchten. Die Positivliste umfasst ausschließlich:

- `p` für Absätze;
- `h2` und `h3` für Überschriften;
- `ul` und `ol` für Listen;
- `li` für Listeneinträge;
- `strong` für starke Hervorhebung;
- `em` für Betonung;
- `a` für sichere HTTPS-Links.

Nicht zugelassen sind insbesondere Scripts, Styles, Bilder, Iframes, Objekte,
Embeds, SVG, MathML, Formulare, Ereignisattribute und fremde HTML-Attribute.
Unbekannte passive Formatierung wird entfernt, während lesbarer Text nach
Möglichkeit erhalten bleibt. Aktive Inhalte werden vollständig verworfen.

Die Bereinigung im Browser sorgt für unmittelbares Feedback. Beim Speichern
prüft der PHP-Sanitizer den Inhalt erneut und bleibt die maßgebliche
Sicherheitsgrenze.

## Datenschutz und Betrieb ohne JavaScript

Der Editor lädt keine externen Bibliotheken, Drittinhalte, Fonts, Icons,
CDN-Ressourcen oder Telemetrie. Er sendet die Texte nicht an einen KI-Dienst und
verwendet weder `localStorage` noch `sessionStorage` oder Cookies.

Wenn JavaScript deaktiviert ist oder ein Browserfehler die Komfortoberfläche
verhindert, bleiben beide großen Textfelder vollständig nutzbar. Dieser
**No-JavaScript-Fallback** ermöglicht weiterhin das Bearbeiten und gemeinsame
Speichern der Sprachfassungen.

## Portlet einsetzen

1. gewünschte OPC-Seite öffnen;
2. Bereich **Custom Portlets** aufklappen;
3. **AI-Philosophie** an die gewünschte Position ziehen;
4. Vorschau kontrollieren;
5. OPC-Seite bewusst speichern und veröffentlichen.

Wenn für die aktuelle Sprache kein Inhalt vorliegt, zeigt die öffentliche Seite
keinen leeren Platzhaltertext. Im OPC-Vorschaumodus erscheint ein
redaktioneller Hinweis.

## Vorschlag für den Aufbau

### Unsere Haltung

Warum verwendet Ihr Unternehmen KI und welchen Nutzen sollen Kunden davon
haben?

### Menschliche Kontrolle

Wer prüft Inhalte vor der Veröffentlichung? Welche Entscheidungen bleiben
ausdrücklich bei Menschen?

### Transparenz

Welche Bilder oder Inhalte kennzeichnen Sie und wie erklären Sie die
verwendeten Status?

### Datenschutz

Werden personenbezogene Daten oder Kundeninhalte verarbeitet? Werden externe
Dienste eingesetzt? Welche Schutzmaßnahmen gelten?

### Kontakt

Wie können Kunden Fragen, Hinweise oder Beschwerden zum KI-Einsatz melden?

## Beispieltext als Ausgangspunkt

> Wir setzen künstliche Intelligenz unterstützend bei der Erstellung und
> Bearbeitung ausgewählter visueller Inhalte ein. Veröffentlichte Inhalte werden
> vorab von einem Menschen geprüft. KI-generierte oder wesentlich KI-bearbeitete
> Bilder kennzeichnen wir transparent. Kundendaten werden durch dieses
> Kennzeichnungs-Plugin nicht an externe KI-Dienste übertragen.

Passen Sie den Text an Ihre tatsächlichen Prozesse an. Das Plugin unterstützt
Transparenz, ersetzt aber keine individuelle Rechtsberatung oder Prüfung Ihrer
Unternehmensprozesse.
