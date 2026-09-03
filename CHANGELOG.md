# Änderungsprotokoll

## 1.3.9 – 2026-09-03

- Zeigt das unveränderte Schuh-Beispielbild wieder ohne Schachbrettrand.
- Ergänzt darunter eine eigene zweifach vergrößerte Detail-Lupe mit bunten
  Flächen, feinen Linien und einem Kreis, vollständig lokal per CSS.
- Synchronisiert beide Vorschauen mit denselben geprüften Designwerten.
  Transparenz und Unschärfe werden zusätzlich als Prozent- und Pixelwerte angezeigt.
- Erklärt den verdeckten Unschärfe-Effekt bei 0 % Transparenz und weist auf
  fehlende Browserunterstützung hin. Große Labels lassen die Lupenbox mitwachsen.
- Behält Position und Außenabstand am Produktbeispiel; die Detailprobe bleibt
  mittig. Vorhandene Werte, Bilder und Kennzeichnungen bleiben unverändert.
- Keine neue Datenbankmigration, kein externer Dienst und keine automatische
  Speicherung. Lokale Tests und tatsächliche Shop-Updates sind getrennt in
  den [Release-Hinweisen](Dokumentation/Release-1.3.9.md) dokumentiert.

## 1.3.8 – 2026-09-03

- Verkleinert ausschließlich die Werkzeuge im AI-Philosophie-Editor auf
  mindestens 32 × 32 px bei normaler Basisschrift und reduziert die Abstände.
- Behält große Textflächen, den Speichern-Button, Tastaturfokus und Umbrüche bei.
- Macht die Sprachüberschriften auf weißen Karten auch im dunklen Backend lesbar.
- Eine lokale CSS-Inhaltskennung verhindert die Anzeige der alten Werkzeugleiste
  aus dem Browsercache. Keine externen Ressourcen oder Datenbankänderungen.
- Enthält die auf Dev geprüfte Korrektur jetzt im eigenen Installationspaket.
  Die tatsächlichen Shop-Updates werden in den Release-Hinweisen dokumentiert.

## 1.3.7 – 2026-09-03

### Vorschau und Lesbarkeit

- Ergänzt ein rein lokales Schachbrettmuster rund um das unveränderte
  Schuh-Vorschaubild. So sind Transparenz und Hintergrundunschärfe direkt
  hinter dem Label erkennbar.
- Die Musterstreifen wachsen bei großen oder mehrzeiligen Labels mit.
  Alle vier Vorschaupositionen behalten den eingestellten Außenabstand.
- Gibt Überschrift und Einleitung eine eigene helle Fläche mit dunkler
  Schrift, damit sie auch im dunklen JTL-Backend lesbar bleiben.
- Keine neue Einstellung, keine externen Ressourcen und keine Änderungen
  an Shopbildern, Bildkennzeichnungen oder dem Speicherablauf.
- Lokal im Browser bei 360 Pixeln Breite und auf breiter Fläche geprüft;
  sechs neue Strukturtests sichern die Einbindung ab.
- Enthält die OPC-Unterordner-Korrekturen aus den Testpaketen 1.3.5/1.3.6.
- Automatische GitHub-Cloud-Läufe durch ausschließlich manuell startbare
  Qualitätsprüfung ersetzt; Release-Prüfungen werden lokal ausgeführt.

### Darstellung speichern

- Behebt die weiße Adminseite beim Speichern von Design-Einstellungen nach
  einem Wechsel aus der gefilterten Bildergalerie: Das Formular sendet nun
  ausdrücklich an den Darstellungstab und übernimmt keine Galerie-Filter.
- Fängt widersprüchliche Formularadressen bereits vor der Verarbeitung ab.
  Statt eines Seitenabbruchs erscheint ein verständlicher Hinweis; in diesem
  Fehlerfall werden keine Einstellungen geschrieben.
- Rechteprüfung, Sicherheitstoken und strenge Prüfung der Formularfelder
  bleiben unverändert. Keine Datenbankmigration oder externen Ressourcen.
- Durch lokale Regressionstests abgesichert. Am 2. September auf ausdrücklichen
  Wunsch als Zwei-Dateien-Hotfix (`1be0282`) auf Campingteile24 eingespielt und
  aus der zuvor problematischen Galerieadresse erfolgreich gespeichert.
  Alle sieben vorhandenen Designwerte blieben beim Test unverändert.
- Das versionierte Paket 1.3.7 enthält diesen Hotfix. Der tatsächliche
  Installationsstand wird gesondert in den Release-Hinweisen dokumentiert.

## 1.3.6 – 2026-09-02 (Testpaket)

- Schließt ausschließlich elFinders interne `.tmb`-Cacheordner auf jeder Ebene
  vom rekursiven OPC-Dateiscan aus, ohne Dateien zu löschen oder umzubenennen.
- Verhindert dadurch den vollständigen Scan-Abbruch durch Vorschaubilder.
- Prüft verschachtelte Cacheordner, echte Unterordner, unveränderte
  Pfadsicherheit und den Erhalt vorhandener Kennzeichnungen mit Regressionstests.
- Keine neue Datenbankmigration und keine zusätzlichen externen Dienste.

## 1.3.5 – 2026-09-02 (Testpaket, noch nicht veröffentlicht)

- Ergänzt den bisherigen OPC-Seitenscan um einen rekursiven Scan des lokalen
  OPC-Uploadspeichers einschließlich Unterordnern und noch unbenutzter Bilder.
- Nutzt weiterhin die vorhandenen Galerie-Filter und eine Karte pro Bildpfad.
- Erhält Kennzeichnung, Position und Darstellung bestehender Bilder.
- Führt OPC-Seiten- und Dateifundstellen gemeinsam atomar zusammen; bei
  Lesefehlern oder überschrittenen Grenzen wird der gesamte Lauf zurückgerollt.
- Begrenzt den Dateiscan auf 9.999 Rasterbilder, 20.000 Einträge und 32 Ebenen;
  folgt keinen Symlinks und lädt weder Bildinhalte noch externe Ressourcen.
- Ergänzt verständliche, pfadfreie Fehlerhinweise und eine gemeinsame
  Galerie-/Detailhilfe zum Unterschied zwischen Speicher und Seitenverwendung.
- Noch kein öffentliches Release und keine Installation auf Dev oder Live.

## 1.3.4 – 2026-09-01

- vollständig lokalen AI-Philosophie-Editor auch in JTLs per AJAX
  nachgeladenen Plugin-Tabs zuverlässig gestartet;
- kleinen klassischen JTL-Starter ergänzt, der ausschließlich das lokale
  Stylesheet und das lokale ES-Modul von derselben Shop-Domain lädt;
- direkte `type="module"`-Ausführung im AJAX-Fragment nicht mehr vorausgesetzt;
- bei Ladefehlern bleiben beide großen Textfelder sichtbar und nutzbar;
- Strukturvertrag, Datenschutzprüfung, Dokumentation, Wiki und Releasepaket auf
  Version 1.3.4 aktualisiert.

## 1.3.3 – 2026-09-01

- Cache-Bereinigung im Smarty-4-Kompatibilitätsmodus auf die von JTL
  tatsächlich aktive, intern gekapselte Template-Engine umgestellt;
- verhindert, dass die von Smarty 5 geerbte Methode der äußeren JTL-Fassade
  einen nicht verwendeten Compile-Ordner prüft;
- gezielte Löschung weiterhin ausschließlich auf `.tpl`-Dateien des eigenen
  Pluginverzeichnisses begrenzt;
- Regressionstest bildet die JTL-Fassade und ihre aktive Engine getrennt ab;
- Paket, Updatehinweis, Dokumentation und Wiki auf Version 1.3.3 aktualisiert.

## 1.3.2 – 2026-09-01

- Template-Cache des JTL-Backends im frühen Plugin-Update-Lifecycle nun
  ausdrücklich über `BackendSmarty` angesprochen;
- verhindert, dass eine noch nicht initialisierte allgemeine Smarty-Instanz
  versehentlich nur den Frontend-Compile-Ordner bereinigt;
- Cache-Löschung weiterhin auf kompilierte `.tpl`-Vorlagen im eigenen
  Pluginverzeichnis begrenzt;
- Regressionstest ergänzt, der die Erzeugung genau einer echten
  Backend-Smarty-Instanz verbindlich prüft;
- Paket, Updatehinweis, Dokumentation und Wiki auf Version 1.3.2 aktualisiert.

## 1.3.1 – 2026-08-31

- veraltete, serverseitig kompilierte Smarty-Vorlagen nach einem JTL-Update
  gezielt verworfen;
- reproduzierbare Release-Zeitstempel und JTLs Template-Cache dadurch sicher
  miteinander vereinbar gemacht;
- Cache-Aktualisierung auf `.tpl`-Dateien innerhalb des eigenen
  Pluginverzeichnisses begrenzt;
- Regressionstests für den JTL-Update-Lifecycle und die gezielte
  Template-Neukompilierung ergänzt;
- Paket, Updatehinweis, Dokumentation und Wiki auf Version 1.3.1 aktualisiert.

## 1.3.0 – 2026-08-30

- AI-Philosophie in zwei große, untereinander angeordnete Sprachkarten
  überführt;
- vollständig lokalen visuellen Editor mit optionalem HTML-Modus,
  Werkzeugleiste und sicherem HTTPS-Linkdialog ergänzt;
- erlaubte Formatierung auf `p`, `h2`, `h3`, `ul`, `ol`, `li`, `strong`, `em`
  und `a` begrenzt und serverseitige Bereinigung als maßgebliche
  Sicherheitsgrenze beibehalten;
- beide Sprachfassungen über einen eindeutigen Speichern-Vorgang
  synchronisiert;
- große Original-Textfelder als vollständig bedienbaren
  No-JavaScript-Fallback erhalten;
- externe Editorbibliotheken, CDN-Ressourcen, Fonts, Icons, Drittinhalte und
  Telemetrie ausgeschlossen;
- Nutzerhandbuch, Release-Hinweise und technische Marketplace-Recherche für
  ein nachhaltiges, kostenloses Grund-Plugin ergänzt.

## 1.2.1 – 2026-08-29

- geschützten, zweispaltigen Darstellungstab mit lokaler Live-Vorschau ergänzt;
- globale Transparenz von 0 bis 90 Prozent durch Frontend, PHP-Renderer,
  Smarty-Ausgabe und CSS geführt;
- Eckenradius, Hintergrundunschärfe und Transparenz mit gekoppeltem Zahlenfeld
  und Schieberegler bedienbar gemacht;
- Position und Farbschema im Darstellungstab ausdrücklich als **Nur Vorschau**
  gekennzeichnet; die gespeicherten Werte bleiben weiterhin bildbezogen;
- optionalen Footertext auf **supported by: Michael Gahn DESIGN** mit sicherem
  Herstellerlink aktualisiert;
- Updatehinweise bei Neuinstallationen standardmäßig aktiviert und erfolgreiche
  wie erfolglose Prüfungen zwölf Stunden lokal zwischengespeichert;
- reproduzierbares Installationspaket, CI, Sicherheitsdokumentation und Wiki auf
  Version 1.2.1 vereinheitlicht.

## 1.2.0 – 2026-08-27

- geschützten, rein lesenden **Impressum**-Tab im Plugin-Backend ergänzt;
- freigegebene Hersteller-, Kontakt- und Steuerangaben semantisch und ohne
  Datenbankspeicherung ausgegeben;
- Telefon- und E-Mail-Link für berechtigte Administratoren ergänzt;
- öffentlichen Shop, Originalbilder und vorhandene Kennzeichnungsdaten
  unverändert gelassen;
- README, technische Dokumentation und vollständiges Benutzerhandbuch um Zweck,
  Datenschutzgrenzen und Bedienung des Impressum-Tabs erweitert.

## 1.1.1 – 2026-08-27

- native Frontend-Kennzeichnungen für normale, verlinkte und responsive
  `picture`-Bilder ergänzt;
- lokale OPC-Hintergrundbilder aus `background-image` und `data-image-src`
  sicher erkannt und innerhalb der sichtbaren Bildfläche gekennzeichnet;
- ungültige Label-Kindelemente innerhalb von `picture` vermieden;
- Kompatibilität mit der in JTL-Shop 5.7.2 verwendeten phpQuery-Version
  abgesichert;
- Inline-Bildlinks als begrenzte Positionsrahmen stabilisiert, ohne Linkziel,
  Bilddatei oder bestehende Blocklayouts zu verändern;
- Tests für Selektor-Sicherheit, echte JTL-Markup-Strukturen, Hintergrundbilder,
  doppelte Ausgabe und CSS-Verträge ergänzt;
- README zu einer vollständigen, nutzerorientierten Produktbeschreibung
  ausgebaut und ein umfangreiches GitHub-Wiki vorbereitet;
- Dev-Installation 1.1.1 mit getrenntem Backup, unveränderten Bilddaten,
  erfolgreicher JTL-Aktualisierung und visueller Frontend-Prüfung abgenommen.

## 1.1.0 – 2026-08-22

- technische Pfadliste durch eine responsive Bildgalerie mit sicheren lokalen Vorschauen ersetzt;
- Filter, Sortierung, Pagination, Scan und bestätigte Stapelbearbeitung übersichtlich zusammengeführt;
- direkten Kennzeichnungsdialog mit Live-Vorschau, Fokusführung und explizitem Speichern ergänzt;
- lokale Kennzeichnungen über JTLs geschützte Admin-IO-Strecke für OPC und Dateimanager angebunden;
- unterstützte Bildfelder im OnPage Composer konservativ erkannt und direkt bearbeitbar gemacht;
- optionales, fehlertolerantes Kontextmenü für genau eine lokale elFinder-Bildauswahl ergänzt;
- AI-Philosophie unter „Custom Portlets“ eingeordnet;
- Paket-, Sicherheits-, Bedienungs- und Rollback-Dokumentation auf Version 1.1.0 aktualisiert.

## 1.0.0 – 2026-08-22

- sichere Datenbanktabellen und Scanner für lokale JTL-Bildquellen ergänzt;
- geschützte Einzel-, Stapel- und Bereinigungsabläufe im Backend umgesetzt;
- barrierearme Labels, lokale Styles und eng begrenzten OPC-Helfer ergänzt;
- zweisprachige, bereinigte AI-Philosophie mit eigenem OPC-Portlet ergänzt;
- optionale Footer-Nennung und datensparsamen GitHub-Updatehinweis ergänzt;
- Kompatibilitätsprüfung und eigentumsgeschützte Deinstallation umgesetzt;
- PHP-Versionen mit eindeutigem technischem Hosterzusatz wie `8.5.3-nmm1`
  werden bei der Installationsprüfung korrekt erkannt;
- reproduzierbares, symlinkfreies Installations-ZIP und Betriebsdokumentation ergänzt.
