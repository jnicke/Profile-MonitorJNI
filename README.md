# Batterie Watcher

Der Batterie Watcher überwacht die Batterievariable von Aktoren die diese zu Verfügung stellen. Zur Auswertung bietet er die folgenden Funktionen: 

* Setzen einen Alarmvariable
* Vatiable mit der Anzahl der leeren Batterien
* Benachrichtigung via Email 
* Benachrichtigung via Symcon App
* Auflisten der Aktoren mit leeren Batterien in einer HTML Box
* Möglichkeit die Meldungstexte anzupassen

## Setup
Die Einrichtung des Moduls ist sehr einfach. 
1. Download des Moduls via Module Store oder github https://github.com/elueckel/battery-watcher 
2. Anlegen der Instanz: Batterie Monitor
3. Bestimmen der Zeit und der Häufigkeit der Ausführung 
4. Bei Bedarf einrichten der Email/SMTP Instanz und Aktivieren der Benachrichtigung (bei der App wird ein konfiguriertes Webfront vorausgesetzt und automatisch ausgewählt)


## Nutzung
Das Modul fragt alle x Tage um eine definerte Zeit alle Variablen ab bei denen ein Batterieprofil gesetzt wurde. Wenn es leere Batterien gibt, dann wird die Alarmvariable gesetzt (true) und die Anzahl der leere Batterien hochgezählt. Weiterhin ist es möglich die Aktoren im Webfront anzuzeigen, als HTML Box oder eine Nachricht via Email oder die App zu senden.

## Version
0.5 - 27-01-2023 (BETA)
* Abfragen von Batterieprofilen
* Alarmvariable
* Zähler für gefundene Aktoren mit leerer Batterie
* HTML Box fürs Webfront 
* Nachricht via Email / Symcon App

