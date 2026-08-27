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

## Muss ich die Herstellernennung anzeigen?

Nein. Sie ist optional und standardmäßig deaktiviert.

## Verbindet sich das Plugin mit GitHub?

Nur wenn Sie Updatehinweise ausdrücklich aktivieren. Dann werden höchstens alle zwölf Stunden öffentliche Release-Metadaten abgerufen.

## Installiert das Plugin Updates automatisch?

Nein.

## Ist das Plugin eine rechtliche Komplettlösung?

Nein. Es unterstützt transparente Kennzeichnung, ersetzt aber keine individuelle Rechtsberatung oder inhaltliche Prüfung.

## Was passiert bei Deinstallation?

JTL kann die Daten behalten oder auf ausdrücklichen Wunsch löschen. Für ein normales Rollback sollten die Daten erhalten bleiben.
