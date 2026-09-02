# Versionsübersicht

## 1.3.8 – 3. September 2026

Kompakte Werkzeuge im AI-Philosophie-Editor, dunkle Sprachüberschriften auf
weißen Karten und eine lokale CSS-Cachekennung. Große Textfelder bleiben
unverändert. Keine neue Migration. Siehe [Prüf- und Updatehinweise](Release-1.3.8.md).

## 1.3.7 – 3. September 2026

Vereint den rekursiven OPC-Scan aus den Testpaketen 1.3.5/1.3.6 mit dem
Speicher-Hotfix und der rein lokalen Muster-Vorschau. Keine neue Migration.
Veröffentlichung und Installation sind getrennte Schritte; maßgeblich sind
die [aktuellen Prüf- und Updatehinweise](Release-1.3.7.md).

## 1.3.6 – 2. September 2026

Korrigiert den Abbruch des OPC-Dateiscans durch den internen `.tmb`-Cache.
Normale Unterordner und bestehende Kennzeichnungen bleiben erhalten; keine
neue Datenbankmigration. Siehe [Prüf- und Updatehinweise](Release-1.3.6.md).

Campingteile24 enthält zusätzlich den gezielten Speicher-Hotfix `1be0282`.
Er wurde nach Sicherung auf Nutzerwunsch direkt dort erfolgreich geprüft.
Onvis, Dev, das ZIP und GitHub wurden dadurch nicht aktualisiert.
Siehe [Abnahme des Darstellungstabs](Darstellung.md).

## 1.3.5 – 2. September 2026

Eindeutig versioniertes lokales Testpaket, noch kein öffentliches Release.
Ergänzt den sicheren OPC-Seitenscan um den offiziellen lokalen Uploadspeicher
einschließlich Unterordnern und unbenutzter Bilder. Galerie-Filter und bestehende
Kennzeichnungen bleiben erhalten. Lesefehler und überschrittene Schutzgrenzen
rollen den gesamten Scan zurück. Die Abnahme im echten Dev-Shop steht noch aus;
der Live-Shop wurde nicht verändert. Siehe [Prüf- und Updatehinweise](Release-1.3.5.md).

## 1.3.4 – 1. September 2026

Historischer öffentlicher Stand. Startet den vollständig lokalen
AI-Philosophie-Editor über einen klassischen, JTL-kompatiblen AJAX-Starter.
Dieser lädt nur das lokale Stylesheet und das lokale ES-Modul von derselben
Shop-Domain. Die Textfelder bleiben bei einem Ladefehler als sicherer Fallback
sichtbar. Die gezielte Backend-Template-Neucompilierung aus Version 1.3.3 bleibt
unverändert erhalten.

## 1.3.3 – 1. September 2026

Historischer Stand. Verwendet im JTL-Backend die tatsächlich aktive
Smarty-Engine. Dies ist besonders im Smarty-4-Kompatibilitätsmodus wichtig, in
dem JTL die echte Engine innerhalb seiner Fassade kapselt. Die gezielte
Cache-Bereinigung erreicht dadurch den verwendeten Admin-Compile-Ordner. Das
direkte Modulskript im AJAX-Tab wurde von JTL jedoch nicht zuverlässig
ausgeführt; Version 1.3.4 behebt diese Integrationsgrenze.

## 1.3.2 – 1. September 2026

Historischer Stand. Erzeugte im frühen JTL-Update-Lifecycle ausdrücklich einen
`BackendSmarty`-Renderer. Im Smarty-4-Kompatibilitätsmodus landete der direkte
Methodenaufruf jedoch auf der äußeren Smarty-5-Fassade statt auf der intern
aktiven Engine. Diese Einschränkung wird durch Version 1.3.3 behoben.

## 1.3.1 – 31. August 2026

Historischer Stand. Führte die gezielte Cache-Bereinigung ein. Im frühen
JTL-Update-Lifecycle konnte die allgemeine Smarty-Instanz jedoch noch auf den
Frontend- statt den Backend-Compile-Ordner zeigen; diese Einschränkung wird
durch Version 1.3.3 vollständig behoben.

## 1.3.0 – 30. August 2026

Historischer Stand. Ergänzt zwei große Sprachkarten für die
AI-Philosophie, einen vollständig lokalen visuellen Editor, den optionalen
HTML-Modus, eine enge Positivliste und den No-JavaScript-Fallback. Das
kostenlose Grund-Plugin enthält keine Lizenzschlüssel, Zahlung, Sperren,
Telemetrie oder Pro-Freischaltung.

## 1.2.1 – 29. August 2026

Historischer Stand. Ergänzt den geschützten Darstellungstab mit
lokaler Live-Vorschau, Transparenz, gekoppelten Reglern und der neuen
Herstellernennung **supported by: Michael Gahn DESIGN**. Updateprüfungen besitzen
einen positiven und negativen Zwölf-Stunden-Cache; das Update selbst erfolgt
weiterhin per manuellem ZIP-Upload.

## 1.2.0 – 27. August 2026

Ergänzt einen geschützten, rein lesenden
Impressum-Tab für transparente Herstellerangaben. Der öffentliche Shop, die
Bildkennzeichnung und vorhandene Plugin-Daten bleiben unverändert.

## 1.1.1 – 27. August 2026

Stabile Kennzeichnung normaler, verlinkter und
responsiver Bilder sowie lokaler OPC-Hintergrundbilder. Umfangreiche
Nutzerdokumentation und GitHub-Wiki ergänzt. Dev-Abnahme erfolgreich.

## 1.1.0 – 22. August 2026

Bildgalerie, Filter, Einzel- und Stapelbearbeitung, OPC-Dialog,
Dateimanager-Komfortfunktion und Einordnung der AI-Philosophie unter Custom
Portlets. Später auf Live zurückgebaut, um die weitere Entwicklung bewusst auf
Dev fortzuführen.

## 1.0.0 – 22. August 2026

Erste vollständige Pluginbasis mit eigenen Tabellen, sicherem Bildscan,
Kennzeichnungsstatus, Frontend-Styles, AI-Philosophie, Einstellungen,
Updatehinweisen und geschützter Deinstallation.

## Versionspolitik

- **Patch:** Fehlerkorrekturen und kompatible Detailverbesserungen;
- **Minor:** neue, abwärtskompatible Funktionen;
- **Major:** Änderungen, die einen bewussten Migrationsschritt erfordern.

Installierbare Pakete werden mit Versionsnummer im Dateinamen und SHA-256 im
GitHub-Release veröffentlicht. Der automatisch erzeugte GitHub-Quellcode-
Download ist kein Ersatz für das geprüfte JTL-Installationspaket.
