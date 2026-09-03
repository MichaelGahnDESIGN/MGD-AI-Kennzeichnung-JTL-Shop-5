# Häufige Fragen

## Erkennt das Plugin automatisch, ob ein Bild mit KI erstellt wurde?

Nein. Das Plugin sammelt lokale Bilder und stellt eine sichere Kennzeichnungsverwaltung bereit. Die fachliche Entscheidung trifft ein berechtigter Mensch.

## Werden meine Bilder an OpenAI oder andere KI-Dienste gesendet?

Nein. Das Plugin führt keine externe Bildanalyse durch.

## Verändert das Plugin die Originalbilder?

Nein. Das Label wird im Frontend als getrenntes Overlay ausgegeben.

## Bleibt ein Link auf dem Bild funktionsfähig?

Ja. Version 1.1.1 verwendet den Link als begrenzten Bildrahmen, verändert aber weder Ziel noch Bildquelle.

## Funktionieren responsive Bilder?

Ja. JTLs `picture`-Ausgabe wird unterstützt, ohne ungültige Label-Elemente in das `picture` einzufügen.

## Funktionieren OPC-Hintergrundbilder?

Ja, wenn es sich um lokale Bilder handelt, die über `background-image` oder `data-image-src` ausgegeben werden.

## Welche Status sind im Shop sichtbar?

KI-generiert, teilweise KI-generiert, KI-bearbeitet und Deepfake. **Ungeprüft** und **Keine Kennzeichnung** bleiben unsichtbar.

## Kann ich mehrere Bilder gleichzeitig ändern?

Ja. Die Stapelbearbeitung verarbeitet bis zu 500 eindeutige Einträge und zeigt vor der Ausführung eine Zusammenfassung.

## Werden beim Scan Bilder gelöscht?

Nein. Der Scan liest Referenzen und markiert verschwundene Fundstellen als veraltet.

## Was löscht die Bereinigung?

Nur ausgewählte, nachweislich veraltete Plugin-Fundstellen. Keine Bilddateien und keine Shopinhalte.

## Warum zeigt ein Bild keine Vorschau?

Der Pfad oder Dateityp konnte nicht sicher als lokale Rasterbildvorschau aufgelöst werden. SVG und externe URLs sind bewusst ausgeschlossen.

## Kann ich die Kennzeichnung direkt im OPC setzen?

Ja, bei eindeutig erkannten lokalen Bildfeldern. Das Speichern der Kennzeichnung veröffentlicht die OPC-Seite nicht.

## Warum fehlt der Eintrag im Dateimanager?

Er erscheint nur bei genau einer lokalen Rasterbilddatei und kompatibler elFinder-Struktur. Die Galerie bleibt der zuverlässige Hauptweg.

## Kann ich eigene Labeltexte eingeben?

Die Statusbezeichnungen sind aus Sicherheits- und Konsistenzgründen fest definiert. Die ausführliche Unternehmenskommunikation gehört in die frei pflegbare, bereinigte AI-Philosophie.

## Kann ich Deutsch und Englisch verwenden?

Ja. Labeltexte können automatisch der Shopsprache folgen oder fest auf Deutsch beziehungsweise Englisch gesetzt werden. Die AI-Philosophie besitzt getrennte Sprachfassungen.

## Kann ich die AI-Philosophie formatiert bearbeiten?

Ja. Version 1.3.0 bietet einen lokalen visuellen Modus und eine optionale
HTML-Ansicht. Erlaubt sind Absätze, zwei Überschriftenebenen, Listen,
Hervorhebungen und sichere HTTPS-Links. Scripts, Styles, Bilder, Iframes und
fremde Attribute werden entfernt. **Beide Sprachfassungen speichern** sichert
Deutsch und Englisch gemeinsam.

## Funktioniert die AI-Philosophie ohne JavaScript?

Ja. Die großen deutschen und englischen Textfelder bleiben als
No-JavaScript-Fallback vollständig bedienbar. Der Komforteditor lädt keine
externen Bibliotheken, Fonts, Icons, Drittinhalte oder Telemetrie.

## Muss ich die Herstellernennung anzeigen?

Nein. Sie ist optional und standardmäßig deaktiviert. Bei Aktivierung erscheint
**supported by: Michael Gahn DESIGN**; nur der Herstellername ist verlinkt.

## Verbindet sich das Plugin mit GitHub?

Bei Neuinstallationen ist die Funktion standardmäßig aktiviert und kann
ausgeschaltet werden. Sie fragt nur beim adressierten Darstellungstab höchstens
alle zwölf Stunden öffentliche Release-Metadaten ab. GitHub kann dabei
Server-IP, Zeitpunkt und User-Agent erhalten; Bilder, Tokens und Kundendaten
werden nicht übertragen.

## Installiert das Plugin Updates automatisch?

Nein.

Version 1.3.9 wird per geprüftem, manuellem ZIP-Upload installiert. Das aktuelle
Testpaket liegt lokal vor; die öffentliche GitHub-Veröffentlichung steht noch aus.

## Was bedeutet Transparenz?

**0 %** bedeutet einen deckenden Labelhintergrund. Bei **90 %** ist der
Hintergrund nahezu durchsichtig. Die Live-Vorschau hilft bei der Sichtprüfung;
das Originalbild wird nicht verändert.

## Warum werden Position und Farbschema aus der Vorschau nicht übernommen?

Beide Felder sind **Nur Vorschau**. Position und Farbschema gehören zum
einzelnen Bild und werden in dessen Kennzeichnungsdialog oder per
Stapelbearbeitung gespeichert.

## Ist das Plugin eine rechtliche Komplettlösung?

Nein. Es unterstützt transparente Kennzeichnung, ersetzt aber keine individuelle Rechtsberatung oder inhaltliche Prüfung.

## Was passiert bei Deinstallation?

JTL kann die Daten behalten oder auf ausdrücklichen Wunsch löschen. Für ein normales Rollback sollten die Daten erhalten bleiben.
