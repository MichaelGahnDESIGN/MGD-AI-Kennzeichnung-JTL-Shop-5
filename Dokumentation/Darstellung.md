# Darstellung der KI-Kennzeichnung

Version 1.2.1 bündelt alle globalen Darstellungswerte in einem eigenen,
geschützten Bereich unter **Plugins → MGD AI Kennzeichnung → Darstellung**.
Links stehen die Einstellungen, rechts zeigt ein lokales Beispielbild die
Auswirkung als Live-Vorschau.

## Was global gespeichert wird

Nach einem Klick auf **Speichern** gelten diese Werte für alle sichtbaren
Kennzeichnungen im Shop:

| Einstellung | Bereich | Standard | Wirkung |
|---|---:|---:|---|
| Sprache | Automatisch, Deutsch, Englisch | Automatisch | Textsprache des Labels |
| Schriftgröße | 8–48 px | 12 px | Größe des sichtbaren Kurztexts |
| Außenabstand | 0–64 px | 8 px | Abstand zur gewählten Bildecke |
| Innenabstand | 0–32 px | 6 px | Abstand zwischen Text und Labelrand |
| Eckenradius | 0–32 px | 4 px | Rundung des Labels |
| Hintergrundunschärfe | 0–24 px | 0 px | Unschärfe des Bildbereichs hinter dem Label |
| Transparenz | 0–90 % | 8 % | Durchlässigkeit des Labelhintergrunds |

Bei der Transparenz bedeutet **0 % vollständig deckend**. **90 %** ist nahezu
durchsichtig und kann die Lesbarkeit verringern. Schrift und Rand bleiben
sichtbar; verändert wird die Deckkraft des Labelhintergrunds.

## Was pro Bild gespeichert wird

Status, Position und Farbschema gehören zum jeweiligen Bild. Diese Werte werden
im Kennzeichnungsdialog der Bildverwaltung, im OnPage Composer oder in der
Stapelbearbeitung gespeichert. So kann ein Label zum Beispiel bei einem Bild
unten rechts und bei einem anderen oben links erscheinen.

Die Auswahlfelder **Position** und **Farbschema** neben dem Beispielbild tragen
deshalb den Hinweis **Nur Vorschau**. Sie helfen beim Beurteilen der globalen
Werte, werden im Darstellungstab aber nicht gespeichert und überschreiben keine
Bildentscheidung.

## Live-Vorschau bedienen

1. Gewünschten Wert in das Zahlenfeld eingeben oder den zugehörigen
   Schieberegler bewegen.
2. Wirkung sofort am fiktiven „Michael Gahn DESIGN Schuh“ prüfen.
3. Für unterschiedliche Bildsituationen Position und Farbschema im Bereich
   **Nur Vorschau** wechseln.
4. Erst nach der Sichtprüfung **Speichern** wählen.
5. Shop- und Template-Cache leeren, wenn eine bestehende Frontendseite noch
   den alten Wert zeigt.

Die Live-Vorschau arbeitet ausschließlich im Browser mit lokalen Plugin-Dateien.
Sie sendet weder das Beispielbild noch die eingegebenen Werte an Dritte. Ohne
Klick auf **Speichern** wird kein Shopwert geändert.

## Barrierefreiheit und sichere Grenzen

Zahlenfeld und Schieberegler sind gekoppelt und per Tastatur bedienbar. Die
Vorschau meldet Änderungen über eine zurückhaltende Live-Region. Server und
Browser akzeptieren nur die dokumentierten Ganzzahlbereiche und feste
Auswahlwerte. Freie CSS-Klassen oder ungeprüfte Stilwerte werden nicht
gespeichert.

## Herstellernennung

Die optionale Footer-Nennung lautet **supported by: Michael Gahn DESIGN**. Nur
„Michael Gahn DESIGN“ ist verlinkt; die Herstellerseite öffnet sicher in einem
neuen Tab. Die Einstellung ist freiwillig und beeinflusst die
KI-Kennzeichnungen nicht.

## Abnahme des Speicher-Hotfixes am 2. September 2026

Der Fix `1be0282` wurde auf ausdrücklichen Wunsch direkt auf Campingteile24
geprüft. Vor der Übertragung wurden ausschließlich `adminmenu/display.php`
und `adminmenu/templates/display.tpl` verschlüsselt lokal gesichert. Beide
Serverdateien entsprachen vorab der erwarteten Vorversion und nach der
Übertragung exakt dem geprüften Commit. Es wurden keine Datenbankmigrationen
oder Änderungen an anderen Plugins durchgeführt.

Der Test begann in der gefilterten Galerie mit Status „KI-generiert“. Nach
dem Wechsel zu „Darstellung“ führte das Formular an seine eigene Adresse,
ohne Galerieparameter zu übernehmen. Der echte Speichervorgang bestätigte:
„Die Darstellung wurde sicher gespeichert.“ Eine weiße Seite trat nicht auf.

Nach vollständigem Neuladen waren alle sieben Werte unverändert: Sprache
automatisch, Schriftgröße 12 px, Außenabstand 8 px, Innenabstand 6 px,
Eckenradius 4 px, Unschärfe 0 px und Transparenz 8 %. Es wurden bewusst keine
anderen Designwerte im Kunden-Liveshop ausprobiert. Die fünf Startseitenlabels
wurden weiterhin ausgegeben. Ihre bildbezogenen Entscheidungen wurden durch
diesen Test nicht bearbeitet.

Die installierte Versionsnummer bleibt 1.3.6 mit diesem dokumentierten
Hotfix. Onvis und Dev enthalten ihn noch nicht; auch im bisherigen ZIP und
auf GitHub ist er noch nicht veröffentlicht. Der Muster-Hintergrund der
Vorschau gehört nicht zu diesem Fix und ist weiterhin offen.
