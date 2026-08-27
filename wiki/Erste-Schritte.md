# Erste Schritte

Diese Seite führt Sie vom installierten Plugin bis zur ersten sichtbaren Kennzeichnung.

## 1. Grundprinzip verstehen

Das Plugin trennt **Bild**, **Fundstelle** und **Kennzeichnung**:

- Das **Bild** ist die lokale Originaldatei im Shop.
- Eine **Fundstelle** beschreibt, wo JTL oder der OPC dieses Bild verwendet.
- Die **Kennzeichnung** enthält Status, Position und Darstellung.

Ein Bild kann an mehreren Stellen verwendet werden, benötigt aber nur eine zentrale redaktionelle Entscheidung. Ändern Sie diese Entscheidung, erscheint die neue Kennzeichnung an den erkannten vorhandenen Fundstellen.

## 2. Bildscan starten

Öffnen Sie im JTL-Backend:

**Plugins → MGD AI Kennzeichnung → Bildverwaltung**

Wählen Sie anschließend **Sicheren Bildscan starten**. Der Scan liest lokale Bildreferenzen aus unterstützten JTL-Bereichen. Er verändert keine Bilder und löscht keine Shopinhalte.

Nach dem ersten Scan erhalten neue Bilder den Status **Ungeprüft**.

## 3. Galerie eingrenzen

Für die erste Bearbeitung empfiehlt sich:

- Status: **Ungeprüft**;
- Fundstelle: **Vorhanden**;
- Sortierung: **ID** oder **Änderungsdatum**;
- Einträge pro Seite: **25**.

Klicken Sie auf **Galerie anzeigen**, damit die Auswahl angewendet wird.

## 4. Bild fachlich bewerten

Öffnen Sie auf der gewünschten Karte **Kennzeichnen** und wählen Sie:

1. den fachlich passenden Status;
2. eine freie Bildecke;
3. eine gut lesbare helle, dunkle oder automatische Darstellung.

Die Vorschau reagiert sofort. Noch ist nichts gespeichert.

## 5. Bewusst speichern

Erst der grüne Button **Kennzeichnung speichern** schreibt die Entscheidung. **Abbrechen**, Escape oder das Schließen des Dialogs verwirft die ungespeicherte Änderung.

## 6. Frontend prüfen

Öffnen Sie die Shopseite, auf der das Bild verwendet wird, und kontrollieren Sie:

- stimmt der sichtbare Status?
- liegt das Label innerhalb der gewünschten Bildecke?
- bleibt der darunterliegende Link nutzbar?
- ist der Text auf Desktop und Mobil gut lesbar?
- verdeckt das Label keine wesentliche Information?

Wenn ein Cache aktiv ist, leeren Sie Shop- und Template-Cache und laden Sie die Seite vollständig neu.

## 7. Weitere Bilder bearbeiten

Für ähnliche Bilder können Sie die Stapelbearbeitung verwenden. Ändern Sie dort nur die ausdrücklich aktivierten Felder. So lässt sich beispielsweise ausschließlich die Darstellung auf **Dunkel** setzen, ohne Status und Position anzutasten.

## Empfohlener redaktioneller Ablauf

1. Verantwortliche Person benennen.
2. Interne Kriterien für die vier sichtbaren Status festlegen.
3. Neue Bilder regelmäßig scannen.
4. Ungeprüfte Bilder abarbeiten.
5. Sichtbare Shopseiten stichprobenartig kontrollieren.
6. Nach Template-, JTL- oder Plugin-Updates erneut prüfen.

Das Plugin liefert die technische Transparenz. Die inhaltliche Verantwortung bleibt bewusst bei Ihrem Unternehmen.
