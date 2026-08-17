# Installation, Livetest und Rollback

## 1. Pflichtbackup und Wartungsfenster

Vor jeder Installation ist ein **Pflichtbackup** erforderlich:

1. vollständiger Export der JTL-Shop-Datenbank;
2. Sicherung des gesamten Shop-Dateisystems, mindestens von `plugins/` und der aktiven Templates;
3. Prüfung, dass beide Sicherungen lesbar und zeitlich eindeutig zugeordnet sind;
4. dokumentiertes Wartungsfenster mit einer verantwortlichen Person und genügend Zeit für den Rollback.

Die Installation darf nicht unmittelbar vor einer Kampagne, einem Import oder einem Shop-Update erfolgen.

## 2. Technischer Preflight

- JTL-Shop-Version im Backend prüfen: mindestens 5.7.2;
- PHP-Version prüfen: mindestens 8.1;
- aktives Template und Child-Theme notieren; Ziel ist NOVA beziehungsweise OnvisTheme auf NOVA 1.7.1;
- freien Datenbankspeicher, HTTPS und fehlerfreie Shop-Startseite prüfen;
- SHA-256 des ZIP mit dem freigegebenen Wert vergleichen.

## 3. Installation im Plugin-Manager

1. Im JTL-Backend **Plugins → Plugin-Manager → Upload** öffnen.
2. `MGD_AI_Kennzeichnung-1.0.0.zip` auswählen.
3. Nach erfolgreicher Validierung installieren und aktivieren.
4. Keine Dateien in JTL-Core, NOVA oder OnvisTheme manuell ersetzen.
5. Updatehinweise und Footer-Nennung zunächst deaktiviert lassen.

## 4. Kontrollierter Livetest auf https://onvis-shop.de

1. Startseite, Kategorie, Produktdetail, Herstellerseite und eine OPC-Seite als Gast öffnen.
2. Browser-Konsole und Server-Fehlerprotokoll auf neue Fehler prüfen; keine personenbezogenen Daten in Tickets kopieren.
3. Im Backend einen Scan auslösen und Zahl sowie Quellen der gefundenen Bilder plausibilisieren.
4. Ein unkritisches Testbild auf „KI-generiert“ setzen; Position, Hell/Dunkel/Auto und Deutsch/Englisch prüfen.
5. Mit Tastatur, 200 % Zoom und einem Screenreader-Stichprobentest prüfen, dass das Label Inhalte nicht blockiert.
6. Ein OPC-Testelement mit den dokumentierten Klassen markieren und den CSS-Fallback bei deaktiviertem JavaScript prüfen.
7. Das Portlet „AI-Philosophie“ zunächst nur auf einer nicht verlinkten Testseite platzieren und beide Sprachen prüfen.
8. Warenkorb, Suche, Login und Checkout nur lesend beziehungsweise mit einem ausdrücklich freigegebenen Testkonto gegenprüfen.

## 5. Deaktivierung und Rollback

Bei Fehlern das Plugin zuerst im Plugin-Manager deaktivieren. Bleibt der Fehler bestehen, Shopdateien und Datenbank aus demselben Pflichtbackup-Zeitpunkt wiederherstellen und Caches nach JTL-Vorgabe leeren.

Eine Deinstallation ohne Datenlöschung bewahrt Kennzeichnungen und Philosophie-Texte. Die Option „Daten löschen“ ist irreversibel und darf nur nach erneutem Backup gewählt werden. Sie entfernt ausschließlich Tabellen, deren Eigentümermarker und Struktur vollständig zum Plugin passen; Abweichungen führen bewusst zum Abbruch.
