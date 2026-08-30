# Design: lokaler Philosophie-Editor, öffentliches Repository und Release 1.3.0

**Stand:** 30. August 2026  
**Projekt:** MGD AI Kennzeichnung für JTL-Shop 5  
**Zielversion:** 1.3.0

## Ziel

Der Tab **AI-Philosophie** wird zu einer verständlichen, vollständig lokalen
Redaktionsoberfläche ausgebaut. Deutsche und englische Inhalte stehen
untereinander, bieten jeweils eine große visuelle Bearbeitung und einen
HTML-Quellcode-Modus und bleiben auch ohne JavaScript bearbeitbar.

Das bislang private GitHub-Repository wird nach einer Prüfung des vollständigen
Git-Verlaufs öffentlich. Der kostenlose Plugin-Kern bleibt unter
`GPL-3.0-or-later`. Die Änderung wird dokumentiert, getestet, nach `main`
übertragen und als GitHub-Release **v1.3.0** veröffentlicht.

Die Monetarisierungsrecherche wird als verständliche, mit offiziellen Quellen
belegte Projektdokumentation ergänzt. Version 1.3.0 führt noch keine
Lizenzschlüssel, Zahlungen oder kostenpflichtigen Funktionssperren ein.

## Nicht-Ziele

- keine externe Editorbibliothek und kein CDN;
- keine extern geladenen Fonts, Icons, Skripte, Styles oder Bilder;
- keine Telemetrie und keine zusätzlichen Netzwerkaufrufe;
- keine automatische Veröffentlichung oder Änderung einer OPC-Seite;
- keine Lizenzprüfung und keine Zahlungsfunktion in Version 1.3.0;
- kein automatisches Update im Shop; der bestehende Hinweis verweist nur auf
  ein geprüftes GitHub-Release.

## Gewählte Editor-Architektur

### Progressive Verbesserung

Die vorhandenen `<textarea>`-Felder bleiben die verbindlichen Formularfelder.
Ohne JavaScript erscheinen sie untereinander, über die gesamte verfügbare
Breite und mit mindestens 360 Pixel Bearbeitungshöhe. Damit bleibt das Formular
auch bei strenger Content-Security-Policy oder einem JavaScriptfehler nutzbar.

Bei verfügbarem JavaScript wird jedes Feld um eine lokale Editoroberfläche
erweitert:

- Modus **Visuell** mit `contenteditable`;
- Modus **HTML** mit dem ursprünglichen Quelltextfeld;
- eine eigene Werkzeugleiste je Sprache;
- sichtbare Statusangaben für Modus, Zeichenmenge und entfernte Formatierungen;
- ein gemeinsamer Speichern-Button unter beiden Sprachfassungen.

Die deutsche Fassung steht zuerst, die englische unmittelbar darunter. Beide
Bereiche sind als eigenständige, gut erkennbare Karten aufgebaut.

### Werkzeugleiste

Die Werkzeugleiste enthält ausschließlich Funktionen, die der bestehenden
serverseitigen Positivliste entsprechen:

- Absatz;
- Überschrift H2 und H3;
- Fett und Kursiv;
- ungeordnete und geordnete Liste;
- sicherer HTTPS-Link;
- Formatierung entfernen;
- Rückgängig und Wiederholen;
- Umschaltung zwischen visueller und HTML-Ansicht.

Bedienelemente verwenden Textkürzel beziehungsweise lokale Inline-SVGs. Es
werden keine Webfonts oder externen Iconsets eingebunden.

### Sichere Synchronisierung

Das Quelltextfeld bleibt die einzige an den Server übermittelte Datenquelle.
Die visuelle Ansicht wird daraus aufgebaut und schreibt Änderungen wieder in
dieses Feld zurück.

Vor dem Wechsel von HTML zu Visuell und vor dem Absenden läuft eine lokale
Positivlisten-Bereinigung. Sie erlaubt nur:

`p`, `h2`, `h3`, `ul`, `ol`, `li`, `strong`, `em` und `a`.

Links müssen `https://` verwenden, dürfen keine eingebetteten Zugangsdaten und
keinen abweichenden Port enthalten und erhalten `rel="noopener noreferrer"`.
Alle freien Attribute, Ereignishandler, Styles sowie aktive Elemente werden
entfernt. Einfügen aus der Zwischenablage wird durch dieselbe Bereinigung
geführt.

Die lokale Bereinigung dient der sicheren Vorschau und guten Rückmeldung. Die
bestehende PHP-Bereinigung bleibt die allein verbindliche Sicherheitsgrenze und
bereinigt beide Inhalte beim Speichern erneut.

### JavaScript-Aufteilung

Die Bedienung wird in kleine, verständlich dokumentierte Module getrennt:

- `philosophy-editor.mjs` – Initialisierung und Lebenszyklus;
- `philosophy-sanitizer.mjs` – lokale Positivlisten-Bereinigung;
- `philosophy-toolbar.mjs` – Formatierungsbefehle und Tastaturbedienung;
- `philosophy-source-sync.mjs` – Wechsel und Synchronisierung der Ansichten;
- `philosophy-link-dialog.mjs` – zugänglicher Dialog für HTTPS-Links.

Das bestehende PHP-Admin-Entry-Point bleibt für Berechtigung, CSRF-Prüfung,
Feld-Positivliste, Transaktion und Fehlerbehandlung verantwortlich.

## Darstellung und Barrierefreiheit

- volle Breite innerhalb des JTL-Admin-Inhalts;
- zwei Editor-Karten untereinander;
- Mindesthöhe von 360 Pixel je Bearbeitungsansicht;
- klare Beschriftungen „Deutsch“ und „Englisch“;
- sichtbare Fokuszustände und mindestens 44 Pixel hohe primäre Bedienelemente;
- Werkzeugleisten mit `aria-label`, gedrücktem Zustand und Tastaturbedienung;
- Status- und Fehlermeldungen über passende Live-Regionen;
- responsives Umbrechen der Werkzeugleiste bei schmalen Ansichten;
- ausschließlich JTL-Systemschrift und lokale Styles.

## Daten- und Fehlerfluss

1. Der berechtigte Admin-GET lädt die bereits serverseitig bereinigten Inhalte.
2. Das Template gibt die Textfelder sicher HTML-escaped aus.
3. JavaScript erweitert die Felder lokal und führt keine Netzwerkanfrage aus.
4. Änderungen werden lokal synchronisiert und vor einer visuellen Darstellung
   bereinigt.
5. Beim POST werden ausschließlich `content_de`, `content_en` und das
   CSRF-Token akzeptiert.
6. PHP bereinigt erneut, schreibt beide Sprachfassungen in einer Transaktion
   und meldet den gemeinsamen Erfolg.
7. Bei JavaScriptfehlern bleibt das HTML-Textfeld funktionsfähig. Bei einem
   Serverfehler werden keine halben Sprachstände gespeichert.

## Öffentliches Repository

Vor der Sichtbarkeitsänderung werden geprüft:

- kompletter Git-Verlauf und alle erreichbaren Objekte;
- Dateinamen und Inhalte auf Zugangsdaten, Tokens, Schlüssel, `.env`, Dumps und
  Backups;
- große historische Binärdateien und unerwartete Archive;
- Releaseartefakt und aktuelle Quellen auf personenbezogene oder interne Daten.

Bei einem sensiblen Treffer wird die Veröffentlichung gestoppt und der Verlauf
zuerst bereinigt. Bei sauberem Befund wird ausschließlich das Repository
`MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5` auf **public** gestellt.

Weil öffentliche GitHub-Releases anonym lesbar sind, wird die Dokumentation zur
optionalen Updateprüfung angepasst: Der bisherige Hinweis auf das private
Repository entfällt. Die Prüfung bleibt abschaltbar, auf GitHub beschränkt und
maximal einmal in zwölf Stunden aktiv.

## Monetarisierungsstrategie

Der kostenlose Kern bleibt vollständig funktionsfähig. Empfohlene Erlöswege:

1. kostenpflichtige Installation, Einrichtung, Migration und persönlicher
   Support;
2. Wartungs- und Prioritätssupport-Verträge;
3. ein eigenständiger Pro-Zusatz oder ein substanzieller gehosteter Dienst für
   Teamfreigaben, Auditberichte, Massenworkflows und plattformübergreifende
   Verwaltung;
4. Beratung zu AI-Governance, Kennzeichnungsprozessen und redaktionellen
   Richtlinien;
5. freiwilliges Sponsoring und Unternehmensförderung.

Ein späterer Lizenzdienst darf nicht nur einen Schlüssel prüfen. Er muss einen
echten Zusatznutzen erbringen, Datensparsamkeit, Opt-in, EU-/DSGVO-nahe
Verarbeitung und nachvollziehbare Kündigungs-/Exportwege bieten.

Die Dokumentation unterscheidet Plattformregeln ausdrücklich:

- **JTL:** Der Extension Store bietet eigenen Checkout und zentrale Lizenzen.
  Die öffentlich auffindbaren Unterlagen klären externe Abrechnung für eine
  dort gelistete Erweiterung nicht eindeutig. Vor externen Lizenzschlüsseln ist
  daher eine schriftliche Freigabe von JTL erforderlich.
- **Shopware:** Store-Verkäufe und Store-Lizenzen werden über Shopware
  abgewickelt. Nachgelagerte Kosten oder Servicegebühren können einen
  Technology-Partner-Vertrag verlangen. Externe Abrechnung innerhalb einer
  Store-Erweiterung wird nicht ohne schriftliche Vereinbarung vorgesehen.
- **WordPress.org:** Ein kostenlos gelistetes Plugin darf enthaltene lokale
  Funktionen nicht als Trialware sperren. Ein getrennt vertriebener Pro-Zusatz
  oder ein substanzieller kostenpflichtiger Dienst ist möglich; ein reiner
  Lizenzprüfdienst und das Nachladen ausführbaren Premiumcodes sind nicht
  zulässig.
- **Shopify App Store:** Alle App-Gebühren einer öffentlich gelisteten App
  müssen über Shopify App Pricing beziehungsweise die Shopify Billing API
  laufen. Off-Platform-Billing oder externe Lizenzschlüssel ersetzen diese
  Abrechnung nicht. Individuelle Custom-Distribution ist auf einzelne Händler
  beziehungsweise eine Plus-Organisation begrenzt und folgt einem anderen
  Vertriebsmodell.

Die Recherche ist eine technische Markt- und Plattformbewertung, keine Rechts-
oder Steuerberatung. Vor Einführung eines Bezahlmodells werden die dann
aktuellen Verträge nochmals geprüft.

## Dokumentation und Release

Zu aktualisieren sind insbesondere:

- `README.md` und `README.en.md`;
- `CHANGELOG.md`;
- `SECURITY.md`;
- `wiki/AI-Philosophie.md`, `wiki/Installation-und-Update.md`,
  `wiki/Datenschutz-und-Sicherheit.md` und `wiki/FAQ.md`;
- neue Seite `Dokumentation/Monetarisierung-und-Marketplaces.md` mit
  offiziellen Quellen und Standdatum;
- Releasehinweise `Dokumentation/Release-1.3.0.md`;
- Pluginversion, Buildskript, CI und Pakettests auf 1.3.0.

Das Release enthält ausschließlich die geprüfte Positivliste, die neuen lokalen
Editor-Module und Styles. Es enthält weder Abhängigkeiten von einem CDN noch
Entwicklungs-, Test-, Backup- oder Zugangsdaten.

## Teststrategie

### PHP und Integration

- vorhandene Berechtigungs-, CSRF-, Feld- und Transaktionstests bleiben grün;
- deutsche und englische Inhalte werden gemeinsam gespeichert;
- Serverbereinigung entfernt aktive Inhalte und unsichere Attribute;
- vorhandene Inhalte und UTF-8/Umlaute bleiben erhalten;
- Admin-Template referenziert nur lokale Assets.

### JavaScript

- Editorinitialisierung für beide Sprachfelder;
- Umschaltung Visuell/HTML ohne Inhaltsverlust;
- alle Werkzeugleistenbefehle und Tastaturzustände;
- Zwischenablage und Quellcode werden auf die Positivliste reduziert;
- unsichere Links, Skripte, Eventhandler, Styles und eingebettete Objekte werden
  entfernt;
- keine Fetch-, XHR-, Beacon-, WebSocket-, Storage- oder externen Assetzugriffe;
- fehlerhafte beziehungsweise fehlende DOM-Strukturen bleiben ohne Absturz;
- ohne JavaScript bleiben beide großen Textfelder nutzbar.

### Repository und Release

- vollständiger History-Secretscan vor `public`;
- öffentliche Sichtbarkeit nach Änderung erneut per GitHub API prüfen;
- alle PHP-, JavaScript-, Analyse- und Styleprüfungen;
- reproduzierbarer Build und SHA-256;
- ZIP-Integrität, interne Version 1.3.0 und Positivlistenprüfung;
- Fast-Forward nach `main`, annotierter Tag `v1.3.0` und genau zwei
  Releaseassets: ZIP und SHA-256.

## Freigabekriterien

- der Nutzer kann beide Sprachfassungen groß und untereinander bearbeiten;
- visueller und HTML-Modus funktionieren ohne externe Ressourcen;
- serverseitige Bereinigung bleibt verbindlich und alle Sicherheitstests sind
  grün;
- Repository-Historie ist frei von Secrets und das Repository ist öffentlich;
- Dokumentation erklärt kostenlosen Kern, erlaubte Monetarisierungswege und
  Plattformgrenzen verständlich;
- GitHub `main`, Tag, Release, ZIP-Version und Prüfsumme zeigen denselben
  geprüften Stand.
