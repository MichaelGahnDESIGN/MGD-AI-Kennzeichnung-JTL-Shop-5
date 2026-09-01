# Release 1.3.4

Version 1.3.4 sorgt dafür, dass der vollständig lokale Editor im Tab
**AI-Philosophie** auch dann zuverlässig startet, wenn JTL den Tab per AJAX in
die bestehende Administrationsseite lädt.

## Ursache

Version 1.3.3 lieferte bereits die neue Formularvorlage aus und bereinigte den
richtigen Backend-Templatecache. JTL führte den direkt im nachgeladenen
HTML-Fragment eingebundenen `type="module"`-Einstieg jedoch nicht zuverlässig
aus. Dadurch blieben die zwei großen Textfelder sichtbar, während
Werkzeugleiste, visueller Editor und lokale Formatierung nicht initialisiert
wurden.

## Lösung

Ein kleiner klassischer Einstieg `philosophy-editor-init.js` wird über den von
JTL unterstützten AJAX-Skriptpfad ausgeführt. Er:

1. liest die lokalen Asset-Adressen aus dem geschützten Formular;
2. akzeptiert ausschließlich dieselbe Shop-Domain und exakt den eigenen
   Pluginpfad;
3. bindet das lokale Stylesheet höchstens einmal ein;
4. lädt das lokale ES-Modul dynamisch;
5. initialisiert beide Sprachkarten;
6. lässt bei jedem Fehler die normalen Textfelder sichtbar und nutzbar.

Es werden weder Editorbibliotheken noch Fonts, Icons, Telemetrie oder Inhalte
von externen Servern geladen. Die serverseitige HTML-Bereinigung bleibt die
verbindliche Sicherheitsgrenze beim Speichern.

## Abnahme auf DEV

Nach dem Update auf `dev.onvis-shop.de` muss der Tab **AI-Philosophie** zeigen:

- Deutsch und Englisch untereinander;
- je Sprache eine lokale Werkzeugleiste;
- einen visuellen Bearbeitungsmodus;
- einen optionalen HTML-Modus;
- den gemeinsamen Button **Beide Sprachfassungen speichern**.

Vor einer Freigabe für Live müssen zusätzlich Browserkonsole und
Serverprotokoll frei von neuen Fehlern sein. Ein Live-Update ist nicht Teil der
DEV-Abnahme und benötigt weiterhin eine eigene ausdrückliche Freigabe.

## Geprüftes Installationspaket

- Datei: `MGD_AI_Kennzeichnung-1.3.4.zip`
- SHA-256: `221134aaf18aaa874d3818d04497b240257e005d6f3ad49e12f8b0706efb5aba`

Verwenden Sie im JTL-Plugin-Manager ausschließlich dieses ausdrücklich am
GitHub-Release angehängte ZIP, nicht die automatisch erzeugten
Quellcodearchive.

## Rückfall

Bei Problemen Plugin im JTL-Plugin-Manager deaktivieren und das unmittelbar vor
dem Update gesicherte Pluginverzeichnis wiederherstellen. Die eigenen
Plugin-Tabellen und gespeicherten Philosophie-Inhalte werden durch dieses
Release nicht migriert oder gelöscht.
