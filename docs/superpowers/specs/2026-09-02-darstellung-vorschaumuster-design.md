# Entwurf: Vorschau mit Muster und lesbarer Überschrift

## Ziel und Freigabestand

Michael hat dem Vorschlag zugestimmt, im Darstellungstab ein kontrastreiches Muster rund um den Schuh und hinter dem Vorschau-Label sowie bessere Schriftkontraste umzusetzen. Dieses Dokument konkretisiert ausschließlich diese beiden Änderungen. Die schriftliche Entwurfsprüfung steht noch aus; der Produktivcode bleibt bis dahin unverändert.

## Vorschau

Das vorhandene, lokal mitgelieferte Schuhbild bleibt erhalten und wird unverzerrt mit Abstand zum Rand angezeigt. Ein hell-dunkles, regelmäßiges CSS-Muster umgibt das Bild. Es liegt tatsächlich hinter dem Label, damit dessen Transparenz und Hintergrundunschärfe sichtbar werden. Ein Muster ausschließlich hinter dem vollflächigen Bild wäre wirkungslos, weil das Bild einen undurchsichtigen Hintergrund besitzt.

Die Musterfläche berücksichtigt alle vier wählbaren Labelpositionen. Die vorhandenen Regler, Zahlenfelder und Vorschauoptionen bleiben erhalten. Ein kurzer Hinweis erklärt: Das Muster dient nur der Vorschau; es wird nicht in Shopbilder eingebaut. Es gibt keinen zusätzlichen Schalter und keine neue gespeicherte Einstellung.

Als Alternativen wurden ein neues Rasterbild und ein bloßes Hintergrundmuster unter dem unveränderten Bild betrachtet. Die CSS-Lösung mit sichtbarem Rand ist vorzuziehen: Sie benötigt keine Bildgenerierung, keine zusätzlichen Bilddateien und keine externen Dienste.

## Lesbarkeit

Die Überschrift „Darstellung“, die Pluginbezeichnung und die Einleitung erhalten eine gemeinsame helle Hintergrundfläche mit ausdrücklich festgelegten dunklen Textfarben. Damit bleiben sie sowohl im dunklen als auch im hellen JTL-Backend lesbar, ohne von undokumentierten Theme-Klassen abhängig zu sein. Die bestehenden Formular- und Vorschaukarten sowie die mobile Einspaltendarstellung bleiben erhalten.

## Technischer Umfang und Sicherheit

- Änderungen beschränken sich auf das Template und die lokal eingebundenen, auf den Darstellungstab begrenzten CSS-Regeln. Die Musterregeln erhalten eine eigene übersichtliche Datei.
- Es werden keine Schriften, Bilder, Skripte oder anderen Ressourcen von Drittanbietern geladen. Kostenpflichtige Werkzeuge sind nicht erforderlich.
- Keine Datenbankänderung, keine neue Speicherung, keine Änderung vorhandener Bildkennzeichnungen oder öffentlicher Shopbilder.
- Der bereits getestete Speicher-Hotfix, Formularziel, Berechtigungen, Validierung und CSRF-Schutz bleiben unverändert.
- Umsetzung und Tests erfolgen zuerst lokal in Git. Eine Übertragung auf einen Shop ist getrennt zu dokumentieren und darf nicht als bereits erfolgt dargestellt werden. Kein automatischer Release oder Rollout auf andere Shops.

## Abnahme

1. Strukturtests prüfen lokale Einbindung, begrenzte CSS-Geltung und den Hinweis „nur Vorschau“.
2. Im Browser werden helle und dunkle Umgebung, breite und schmale Darstellung sowie alle vier Labelpositionen geprüft.
3. Bei 50 Prozent Transparenz muss das Muster hinter dem Label erkennbar sein. Der Wechsel von 0 auf 12 Pixel Unschärfe muss dessen Konturen sichtbar verwischen; Text und Rahmen bleiben scharf.
4. Die übrigen erlaubten Wertebereiche dürfen keine horizontalen Überläufe erzeugen. Vorschauänderungen dürfen keine Speicherung oder Netzwerkanfrage auslösen.
5. Bestehende PHP- und JavaScript-Tests sichern den unveränderten Speicherablauf. Anschließend werden die Bedienhinweise und der lokale Wissensgraph aktualisiert.

## Rückfallweg

Die Änderung ist rein visuell. Lokal kann sie durch einen gezielten Git-Revert zurückgenommen werden. Vor einer späteren Shopübertragung sind ausschließlich die betroffenen Dateien zu sichern; ein Rückspielen dieser Dateien benötigt keine Datenbankänderung.

## Selbstprüfung

Der Entwurf enthält keine offenen Platzhalter, keine neuen externen Abhängigkeiten und keine widersprüchlichen Speicherregeln. Muster und Kontrastverbesserung sind klar vom öffentlichen Shop und vom bereits behobenen Speicherfehler getrennt.
