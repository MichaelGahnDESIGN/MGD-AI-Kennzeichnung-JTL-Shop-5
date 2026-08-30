# Release 1.3.0

Version 1.3.0 macht die Pflege der deutsch- und englischsprachigen
AI-Philosophie deutlich übersichtlicher. Beide Texte stehen untereinander in
großen Sprachkarten und können wahlweise **Visuell** oder als bereinigtes
**HTML** bearbeitet werden.

## Was sich für Betreiber ändert

- lokale Werkzeugleiste für Absatz, Überschrift, Listen, Fett, Kursiv und Link;
- getrennte deutsche und englische Bearbeitung auf einer Seite;
- sicherer Wechsel zwischen visuellem Modus und HTML-Ansicht;
- gemeinsame Aktion **Beide Sprachfassungen speichern**;
- große Textfelder bleiben ohne JavaScript vollständig bedienbar;
- keine externen Editorbibliotheken, Fonts, Icons, CDN-Dateien oder Telemetrie.

## Sicherer Editor

Zugelassen sind ausschließlich die HTML-Elemente `p`, `h2`, `h3`, `ul`, `ol`,
`li`, `strong`, `em` und `a`. Links müssen HTTPS verwenden und dürfen keine
eingebetteten Zugangsdaten oder fremden Ports enthalten. Scripts, Styles,
Bilder, Iframes, Formulare, SVG, Ereignisattribute und unbekannte Attribute
werden entfernt.

Die Bereinigung im Browser sorgt für direktes, verständliches Feedback. Beim
Speichern prüft der PHP-Sanitizer denselben Inhalt erneut und bildet die
maßgebliche Sicherheitsgrenze. Fällt die Editor-Initialisierung aus, bleiben
die ursprünglichen Textfelder sichtbar; dieser **No-JavaScript-Fallback**
verhindert, dass redaktionelle Arbeit vom Komforteditor abhängt.

## Datenschutz

Der Editor arbeitet vollständig lokal im Browser. Er lädt keine Drittinhalte,
sendet keine Philosophie-Texte an externe Dienste und verwendet keine
Telemetrie, Cookies, `localStorage` oder `sessionStorage`. Gespeichert werden
die beiden bereinigten Sprachfassungen ausschließlich über den vorhandenen,
geschützten JTL-Admin-Ablauf.

Die optionale GitHub-Updateprüfung bleibt eine davon getrennte Funktion. Sie
installiert keine Aktualisierung automatisch.

## Installation und Update

1. Datenbank, Shop-Dateisystem und vorhandenes Pluginverzeichnis sichern.
2. Das explizit angehängte Release-ZIP anhand seines SHA-256-Werts prüfen.
3. Exakt dieses Paket zuerst auf einer getrennten Dev- oder Staging-Instanz
   installieren.
4. AI-Philosophie auf Deutsch und Englisch öffnen, beide Modi testen und
   speichern.
5. Das OPC-Portlet auf einer Testseite prüfen, ohne die Seite unbeabsichtigt zu
   veröffentlichen.
6. Erst nach erfolgreicher Abnahme ein neues Live-Backup erstellen und dasselbe
   ZIP über den JTL-Plugin-Manager hochladen.

Der automatisch von GitHub erzeugte Quellcode-Download ist kein installierbares
JTL-Paket. Das Plugin installiert Updates nicht automatisch.

## Rückfall

Bei einem Fehler zuerst das Plugin deaktivieren und Shop-, Plugin- sowie
Template-Cache leeren. Wenn nötig, das zuvor gesicherte Pluginverzeichnis
wiederherstellen. Ein normaler Versions-Rollback erfordert keine Deinstallation
mit Datenlöschung; bestehende Kennzeichnungs- und Philosophie-Daten sollten
erhalten bleiben.

## Geschäftsmodell

Version 1.3.0 enthält keine Lizenzschlüssel, Zahlung, Sperren, Telemetrie oder
Pro-Freischaltung. Empfehlungen und offizielle Plattformquellen stehen in
[Monetarisierung und Marketplace-Regeln](Monetarisierung-und-Marketplaces.md).
