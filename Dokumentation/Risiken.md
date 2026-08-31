# Risiken und bekannte Grenzen

## Keine automatische inhaltliche Prüfung

Das Plugin kann nicht feststellen, ob ein Bild tatsächlich mit KI erstellt
wurde. Eine falsche menschliche Einstufung bleibt möglich. Betreiber sollten
deshalb Verantwortlichkeiten und einen nachvollziehbaren Prüfprozess festlegen.

## Keine Rechtsberatung

Die sichtbaren Status unterstützen Transparenz, garantieren aber keine
rechtliche Konformität. Gesetzliche Anforderungen können sich nach Land,
Branche, Inhalt und Veröffentlichungsform unterscheiden.

## Template-Kompatibilität

Version 1.1.1 wurde mit JTL-Shop 5.7.2, NOVA und einem NOVA-basierten
OnvisTheme geprüft. Stark abweichende Templates können andere Bildrahmen oder
CSS-Regeln verwenden. Updates sollten deshalb zuerst auf einer getrennten
Testinstallation geprüft werden.

Seit Version 1.3.1 werden beim JTL-Update die kompilierten `.tpl`-Vorlagen
dieses Plugins gezielt verworfen. Das verhindert alte Backend-Ansichten trotz
erfolgreich aktualisierter Dateien. Falls ein Hoster das Löschen kompilierter
Vorlagen technisch blockiert, muss das Update abgebrochen beziehungsweise der
JTL-Template-Cache im Wartungsfenster manuell geleert werden.

## JTL-Dateimanager

Die Komfortfunktion im elFinder-Dateimanager verwendet eine interne JTL-
Struktur, für die kein gleichwertiger stabiler Plugin-Hook existiert. Nach
einem JTL-Update kann der Menüpunkt fehlen. Das ist ein kontrollierter Fallback
und kein Datenverlust; Kennzeichnungen bleiben über die Galerie und den OPC-
Dialog möglich.

## Externe und aktive Medien

Externe URLs, SVG-Dateien, Videos und unsichere lokale Pfade werden nicht direkt
angeboten. Diese Begrenzung verhindert aktive Inhalte und unkontrollierte
Netzwerkzugriffe, bedeutet aber auch, dass solche Medien außerhalb des Plugins
organisatorisch bewertet werden müssen.

## Begrenzte Philosophie-Formatierung

Der lokale Editor erlaubt bewusst nur wenige semantische HTML-Elemente und
sichere HTTPS-Links. Komplexe Layouts, Bilder, eingebettete Inhalte, freie
Attribute und eigene Styles werden entfernt. Fällt JavaScript aus, bleibt die
Bearbeitung über die großen Textfelder möglich; die Komfort-Werkzeugleiste steht
dann nicht zur Verfügung.

## Marketplace-Regeln können sich ändern

Die dokumentierten Monetarisierungsregeln sind eine technische Recherche mit
Stand 30.08.2026 und keine Rechtsberatung. Externe Lizenz- oder
Zahlungsmodelle müssen vor einer Marketplace-Einreichung erneut anhand der
aktuellen Verträge und bei unklarer Lage schriftlich mit dem Betreiber
abgestimmt werden.

## Leistungsgrenzen

Scans sind paginiert und besitzen harte Obergrenzen. Das Frontend liest
höchstens 500 sichtbare Kennzeichnungen pro Seitenaufruf. Sehr große Shops
sollten Scan und Frontend-Ausgabe unter realistischen Lastbedingungen prüfen.

## Veraltete Fundstellen

Ein veralteter Verweis bedeutet nicht automatisch, dass die Bilddatei gelöscht
werden darf. Die Bereinigung entfernt deshalb nur ausgewählte Plugin-
Fundstellen und niemals das Originalbild.

## Updatehinweise

Bei aktivierten Updatehinweisen entsteht eine ausgehende HTTPS-Verbindung zu
GitHub. Dabei sind Server-IP, Zeitpunkt und der feste User-Agent technisch für
GitHub sichtbar. Weder Bilder noch Tokens, Kunden-, Shop- oder Formulardaten
werden übertragen. Positive und negative Ergebnisse werden zwölf Stunden
lokal zwischengespeichert. Das Plugin installiert keine Updates automatisch;
der öffentliche Release-Hinweis ersetzt nicht den manuellen, geprüften Upload.
Betreiber mit strikten Netzwerk- oder Datenschutzvorgaben können die Funktion
deaktivieren und Releases manuell prüfen.

## Vorschau und Lesbarkeit

Position und Farbschema im Darstellungstab sind **Nur Vorschau**. Wer dort eine
Ecke auswählt, ändert keine bildbezogene Position. Hohe Transparenz bis 90 %
kann auf unruhigen Motiven die Lesbarkeit reduzieren. Vor dem Speichern und nach
Templateänderungen ist deshalb eine Sichtprüfung auf Desktop und Mobil nötig.

## Gegenmaßnahmen

- vollständiges Backup vor Installation und Update;
- getrennte Dev- oder Staging-Umgebung;
- klarer redaktioneller Prüfprozess;
- Sichtkontrolle nach Template- oder JTL-Updates;
- Updatehinweise nur nach bewusster Datenschutzentscheidung aktivieren;
- Update als manuellen ZIP-Upload zuerst auf Dev testen und Rollback bereithalten;
- Sicherheitsmeldungen ohne Echtdaten und nicht als öffentliches Issue senden.
