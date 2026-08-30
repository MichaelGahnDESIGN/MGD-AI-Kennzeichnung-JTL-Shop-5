# Monetarisierung und Marketplace-Regeln

Stand der technischen Plattformrecherche: **30.08.2026**

Diese Seite beschreibt tragfähige Geschäftsmodelle rund um das kostenlose
Grund-Plugin und fasst öffentlich zugängliche Vorgaben der jeweiligen
Plattformbetreiber zusammen. Sie ist eine technische und geschäftliche
Orientierung, **keine Rechtsberatung**. Marketplace-Regeln können sich ändern.
Vor einer kostenpflichtigen Veröffentlichung müssen die aktuellen Verträge,
Entwicklerbedingungen und steuerlichen Pflichten erneut geprüft werden.

## Klare Grenze der Version 1.3.0

Version 1.3.0 bleibt ein vollständig nutzbares, kostenloses Grund-Plugin. Sie
enthält:

- **keine Lizenzschlüssel** und keinen externen Lizenzprüfdienst;
- **keine Zahlung** und keinen Checkout;
- **keine Sperren**, Laufzeitbegrenzungen oder künstlichen Kontingente;
- **keine Telemetrie** oder versteckte Nutzungsanalyse;
- keine Pro-Freischaltung und keinen automatisch nachgeladenen Programmcode.

Damit hängt die bestehende Bildkennzeichnung nicht von einem Drittanbieter,
einem Konto oder einer laufenden Zahlung ab.

## Sinnvolle erste Einnahmemodelle

### 1. Installation, Einrichtung und Migration

Das Plugin bleibt kostenlos, während die persönliche Dienstleistung bezahlt
wird. Dazu können Installation auf Dev und Live, Datenübernahme, Template-
Anpassung, Einführung eines Kennzeichnungsprozesses und dokumentierte Abnahme
gehören. Dieses Modell ist transparent und benötigt keine technische Sperre.

### 2. Wartung und Prioritäts-Support

Betreiber können einen freiwilligen Vertrag für planbare Reaktionszeiten,
Updateprüfungen, Kompatibilitätstests, Fehleranalyse und Release-Begleitung
abschließen. Die kostenlose Version bleibt davon unabhängig nutzbar.

### 3. Separates Pro-Add-on

Ein späteres, getrennt ausgeliefertes Add-on könnte echte zusätzliche
Arbeitsabläufe bieten, zum Beispiel:

- Freigabeworkflows mit Vier-Augen-Prinzip;
- Rollen und feinere Berechtigungen;
- revisionsfähige Audit-Historie;
- erweiterte Massenbearbeitung;
- plattformübergreifende Verwaltung mehrerer Shops.

Das Pro-Add-on sollte technisch und in der Dokumentation klar vom kostenlosen
Grund-Plugin getrennt sein. Ob und wie es in einem Marketplace angeboten werden
darf, richtet sich nach den unten beschriebenen Plattformregeln.

### 4. Eigenständige SaaS-Leistung

Eine bezahlte Online-Leistung ist sinnvoll, wenn der Dienst selbst eine
substantielle Funktion erbringt, etwa organisationsübergreifende Freigaben,
zentrale Richtlinienverwaltung oder nachvollziehbare Governance-Berichte. Ein
Server, der ausschließlich einen Lizenzschlüssel prüft, ist insbesondere für
WordPress.org kein zulässiger Ersatz für echte Servicefunktionalität.

Für jede SaaS-Anbindung gelten zusätzlich: ausdrückliche Aktivierung,
Datenminimierung, Auftragsverarbeitung soweit erforderlich, sichere
Authentifizierung, dokumentierte Löschfristen und eine klare Datenschutzinfo.

### 5. Beratung, Schulung und AI Governance

Bezahlbar sind auch die fachliche Leistung und Prozessberatung: Kennzeichnungs-
richtlinien, Rollenmodelle, interne Schulungen, Prüfprotokolle und die
Einführung eines verantwortlichen AI-Governance-Prozesses. Das Plugin
unterstützt diese Arbeit, verspricht aber keine automatische Rechtskonformität.

### 6. Sponsoring und freiwillige Unterstützung

Sponsoring, Fördermitgliedschaften oder freiwillige Beiträge können die freie
Entwicklung finanzieren. Hinweise müssen zurückhaltend, transparent und ohne
Tracking umgesetzt werden. Die Kernfunktionen dürfen nicht von einer Spende
abhängen.

## JTL-Extension Store

Die offiziellen JTL-Seiten beschreiben einen Store-Prozess mit Seller-
Onboarding, Produktprüfung, Checkout, Lizenzbindung und Installation über
**Plugins → Meine Käufe**. Angeboten werden kostenlose Produkte, Lifetime-
Lizenzen, befristete Lizenzen, Subscriptions sowie Installations- und
Einrichtungspakete. Damit sind kostenpflichtige Services und Supportpakete im
JTL-Ökosystem grundsätzlich als vorgesehene Modelle erkennbar.

Die öffentlich zugänglichen Unterlagen beantworten jedoch nicht eindeutig, ob
ein im JTL-Extension Store gelistetes Plugin seine lokalen Funktionen über
einen zusätzlich außerhalb von JTL gekauften Lizenzschlüssel freischalten darf.
Vor der technischen Umsetzung oder einem Store-Listing ist deshalb eine
schriftliche Freigabe unter **extensions@jtl-software.de** einzuholen.

Eine freie Direktverteilung des GPL-Plugins über GitHub ist organisatorisch von
einem JTL-Store-Listing zu unterscheiden. Ein späteres Listing muss trotzdem
alle dann gültigen JTL-Bedingungen erfüllen.

Offizielle Quellen:

- [JTL: Plugins vertreiben und Seller werden](https://www.jtl-software.de/extension-store/Seller-werden)
- [JTL-Guide: Extension Store und Lizenzbindung](https://guide.jtl-software.com/jtl-shop/shop-erweitern/extension-store/)
- [JTL: FAQ zu Preis-, Lizenz- und Supportmodellen](https://www.jtl-software.de/extension-store/faq)

## Shopware Store

Shopware dokumentiert Prüfung, Veröffentlichung und Lizenzmodelle für
Store-Extensions. Käufe, Mieten und Testlizenzen werden im Extension-Partner-
Bereich den jeweiligen Domains zugeordnet. Der technische Name einer Extension
wirkt sich laut Shopware auf die Kundenlizenzierung aus.

Besonders wichtig für ein ergänzendes SaaS- oder Transaktionsmodell: Eine
Software-Erweiterung oder Schnittstelle mit nachgelagerten Kosten,
Transaktions- oder Servicegebühren benötigt laut Shopware eine
**Technology-Partner-Vereinbarung**, bevor sie aktiviert werden kann. Für ein
extern bezahltes Lizenzmodell innerhalb eines Store-Listings ergibt sich aus
den geprüften Seiten keine pauschale Freigabe. Vor der Umsetzung sollte daher
eine schriftliche Abstimmung mit **alliances@shopware.com** und gegebenenfalls
der Store-Qualitätssicherung erfolgen.

Eine außerhalb des Stores direkt verteilte freie Edition ist von einem
Shopware-Store-Produkt zu unterscheiden. Sobald ein Marketplace-Listing oder
eine Store-Lizenz genutzt wird, gelten die Shopware-Prozesse und Verträge.

Offizielle Quellen:

- [Shopware: Extension Partner – Extensions](https://docs.shopware.com/en/account-en/extension-partner/extensions)
- [Shopware: Extension Partner – Sales](https://docs.shopware.com/en/account-en/extension-partner/sales)

## WordPress.org Plugin Directory

Für ein kostenloses Plugin im WordPress.org-Verzeichnis gelten besonders klare
Grenzen:

- **Trialware ist nicht erlaubt.** Im Directory-Plugin enthaltene Funktionen
  dürfen nicht erst durch Zahlung, Upgrade, Zeitablauf oder Kontingent
  freigeschaltet beziehungsweise gesperrt werden.
- Ein separates Premium-Add-on darf außerhalb von WordPress.org vertrieben
  werden; der Premium-Code gehört dann nicht in das kostenlose Directory-
  Plugin.
- Eine bezahlte SaaS-Leistung ist möglich, wenn der externe Dienst selbst eine
  substantielle Funktion erbringt und transparent dokumentiert ist.
- Ein Dienst, der nur lokale Funktionen über Lizenzschlüssel validiert, gilt
  nicht als zulässige SaaS-Leistung.
- Externe Kommunikation benötigt eine ausdrückliche, informierte Zustimmung.
  Nicht servicebezogenes JavaScript und CSS muss lokal enthalten sein; fremder
  ausführbarer Code darf nicht als vermeintliches Update nachgeladen werden.
- Verzeichniscode, Daten und mitgelieferte Medien müssen GPL-kompatibel sein.

Für WordPress ist daher ein kostenloses, vollständig nutzbares Grund-Plugin plus
separates Pro-Add-on oder eine echte optionale SaaS-Leistung die nachvollzieh-
barste Richtung. Der kostenlose Teil darf nicht bloß als zeitlich oder
funktional gesperrte Testversion dienen.

Offizielle Quellen:

- [WordPress.org: Detailed Plugin Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)
- [WordPress.org: Including a Software License](https://developer.wordpress.org/plugins/plugin-basics/including-a-software-license/)

## Shopify App Store

Shopify unterscheidet öffentliche und benutzerdefinierte Verteilung. Öffentliche
Apps können in mehreren Shops installiert werden und durchlaufen die
App-Store-Prüfung. Für öffentliche Apps ist **Shopify App Pricing** der
bevorzugte Abrechnungsweg; auch einmalige App-Käufe werden über die dokumentierte
Shopify-Abrechnung angelegt und vom Händler bestätigt.

Eine **Custom Distribution** ist kein allgemeiner Ersatz für einen öffentlichen
Vertrieb: Sie ist auf einen einzelnen Shop, mehrere Shops derselben Plus-
Organisation oder bestimmte Development Stores begrenzt. Außerdem kann sie
Shopifys App-Billing-System nicht verwenden.

Die geprüften Seiten geben keine belastbare pauschale Erlaubnis, bei einer
öffentlichen App den Kauf und eine Lizenzfreischaltung am Shopify-Billing
vorbei über einen eigenen Checkout abzuwickeln. Für ein öffentliches Angebot
sollte deshalb mit Shopify App Pricing beziehungsweise der offiziellen Billing
API geplant und ein Sondermodell vorab schriftlich mit Shopify geklärt werden.

Offizielle Quellen:

- [Shopify: About app distribution](https://shopify.dev/docs/apps/launch/distribution)
- [Shopify: Public and private plans](https://shopify.dev/docs/apps/launch/billing/shopify-app-pricing/plans)
- [Shopify: Support one-time app purchases](https://shopify.dev/docs/apps/launch/billing/manual-pricing/support-one-time-purchases)

## Empfohlene Reihenfolge

1. Kostenloses Grund-Plugin stabil und ohne Lizenzsperren veröffentlichen.
2. Bezahlte Installation, Schulung und Wartung als erste Angebote testen.
3. Nachfrage nach konkreten Pro-Funktionen dokumentieren.
4. Für jede Zielplattform das geplante Vertriebsmodell schriftlich bestätigen
   lassen, bevor Checkout oder Lizenzierung programmiert werden.
5. Pro-Add-on oder SaaS getrennt entwickeln, datenschutzrechtlich dokumentieren
   und in einer eigenen Testumgebung prüfen.
6. Marketplace-Regeln unmittelbar vor Einreichung erneut kontrollieren.
