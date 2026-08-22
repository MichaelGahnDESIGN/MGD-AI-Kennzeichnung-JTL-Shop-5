# Dev-Abnahme vom 22. August 2026

## Ausgangslage

Der Upload von `MGD_AI_Kennzeichnung-1.0.0.zip` auf `dev.onvis-shop.de`
war vollständig, die Installation brach jedoch mit JTL-Fehlercode `421` ab.
JTL verwendet diesen Code, wenn die Methode `preInstallCheck()` des Plugins
die Installation bewusst ablehnt.

## Ursache

Der Dev-Server meldet seine PHP-Version als `8.5.3-nmm1`. Der technische
Anbieterzusatz `-nmm1` wurde vom zu strengen Versionsformat des Plugins
abgelehnt, obwohl der numerische Versionskern die Mindestanforderung PHP 8.1
erfüllt.

## Korrektur

Die Prüfung unterscheidet nun zwischen der weiterhin streng dreiteiligen
JTL-Shop-Version und einer PHP-Version, die nach dem eindeutigen numerischen
Versionskern einen begrenzten technischen Hosterzusatz enthalten darf.

Ein Regressionstest bildet den realen Serverwert ab. Zusätzlich wird geprüft,
dass eine zu alte PHP-Version mit demselben Zusatz weiterhin abgelehnt wird.

## Verifizierter Stand

- JTL-Shop: `5.7.2`
- Server-PHP: `8.5.3-nmm1`
- Plugin: `MGD AI Kennzeichnung 1.0.0`
- interne JTL-Plugin-ID auf Dev: `47`
- Status: installiert und aktiviert
- Adminseite: erreichbar, ohne PHP- oder JTL-Fehler
- angelegte Tabellen: Asset-, Nutzungs-, Philosophie- und Bestätigungstabelle
- JTL-Dateiprüfung: keine modifizierten Plugin-Dateien
- Dev-Shop: weiterhin im Wartungsmodus
- Live-Shop: nicht verändert

Vor der erneuten Installation wurden die separate Dev-Datenbank und der zuvor
hochgeladene Pluginordner außerhalb des Webverzeichnisses gesichert.

## Korrigiertes Installationspaket

Datei: `dist/MGD_AI_Kennzeichnung-1.0.0.zip`

SHA-256:
`cee45ae0fab2242c9cf3603fe0f8cad071159512119029e64060d3d6de4f65a0`

Der nächste Schritt ist der funktionale Dev-Test von Bildscan, Einzel- und
Stapelkennzeichnung, Frontendausgabe und AI-Philosophie-Portlet. Eine
Installation auf dem Live-Shop ist erst nach dieser Abnahme vorgesehen.
