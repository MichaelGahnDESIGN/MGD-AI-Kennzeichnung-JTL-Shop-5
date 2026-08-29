# Datenschutz und Sicherheit

## Datenminimierung

Das Plugin speichert ausschließlich technische lokale Bildschlüssel, lokale Pfade, Fundstellen, Kennzeichnungsstatus, Anzeigeeinstellungen und die redaktionellen Philosophie-Texte. Es benötigt keine Kundenprofile, Bestellungen, Zahlungsdaten, E-Mail-Adressen oder externen KI-Konten.

Es findet keine automatische KI-Erkennung statt. Das Plugin sendet **keine Bilder**, Bildinhalte, Shoptexte oder personenbezogenen Daten an einen KI-Anbieter.

## Netzwerkzugriffe

Bei Neuinstallationen sind Updatehinweise standardmäßig aktiviert. Die Funktion
kann in den Plugin-Einstellungen jederzeit ausgeschaltet werden. Nur beim
Öffnen des adressierten Darstellungstabs ruft der Server höchstens alle zwölf
Stunden am festen Endpunkt `api.github.com` öffentliche Metadaten des neuesten
Releases ab. Erfolgreiche und erfolglose Versuche werden für zwölf Stunden
lokal gespeichert. Dadurch führen auch eine Störung, ein Rate-Limit oder das
private Repository nicht bei jedem Seitenaufruf zu einer neuen Anfrage.

GitHub kann bei der HTTPS-Verbindung technisch **Server-IP**, Zeitpunkt und den
festen **User-Agent** `MGD-AI-Kennzeichnung-JTL-Shop-5/1.2.1` erhalten. Das
Plugin überträgt keine Bilder, Shop-, Kunden- oder Formulardaten, Tokens oder
Zugangsdaten. TLS-Prüfung ist verpflichtend, Weiterleitungen sind gesperrt und
die Antwort ist auf 65.536 Byte begrenzt. Das Plugin installiert keine Updates
automatisch. Da es sich um ein privates Repository handelt, kann die anonyme
Abfrage ohne Release-Hinweis enden; Updates erfolgen per manuellem ZIP-Upload.

## Administration

Schreibzugriffe verlangen eine angemeldete, berechtigte JTL-Administration, ein gültiges CSRF-Token und bei Stapel- oder Löschvorgängen eine kurzlebige Einmalbestätigung. Logs enthalten nur feste Ereigniscodes und Mengen, keine Tokens, SQL-Ausnahmen, lokalen Pfade oder eingegebenen Inhalte.

Die Galerie lädt ausschließlich lokale Vorschauen aus festen Shopwurzeln. OPC und Dateimanager verwenden JTLs bestehende Admin-IO-Strecke mit derselben Sitzung und CSRF-Prüfung. Externe Bild-URLs, SVG und mehrdeutige Auswahlen werden abgelehnt. Es entsteht kein eigener öffentlicher Upload- oder Speicherendpunkt.

Auch die Live-Vorschau im Darstellungstab bleibt lokal: Das fiktive Produktbild
liegt im Plugin, und die Regler ändern die Vorschau nur im Browser. Position und
Farbschema sind dort **Nur Vorschau** und werden nicht gespeichert. Erst der
Knopf **Speichern** übermittelt die geprüften globalen Werte an JTL.

## Ausgabe und Deinstallation

HTML, Klassen und numerische Stile stammen aus Positivlisten. Philosophie-Inhalte erlauben nur wenige semantische Elemente und HTTPS-Links ohne Zugangsdaten. Bei Deinstallation werden fremde oder veränderte Tabellen nie gelöscht.
