# Profile Monitor

Der Profile Monitor ist ein Modul mit dem Variable und deren Profile überwacht werden können. Ein Beispiel wäre z.B. die Suche nach leeren Batterien welche in einer Variable angezeigt werden. Wenn eine Variable mit einem bestimmten Profilwert gefunden wird, dann kann können diese Variablen z.B. im Webfront angezeigt oder Email/App Notifications gesendet werden.

* Setzen einer Alarmvariable
* Vatiable mit der Anzahl der gefundenen Profile/Variablen
* Benachrichtigung via Email 
* Benachrichtigung via Symcon App
* Auflisten gefundenen Variablen in einer HTML Box
* Möglichkeit die Meldungstexte anzupassen

## Setup
Die Einrichtung des Moduls ist sehr einfach. 
1. Download des Moduls via Module Store oder github https://github.com/elueckel/Proofile-Monitor 
2. Anlegen der Instanz: Profile Monitor
3. Bestimmen der Zeit und der Häufigkeit der Ausführung 
4. Auswählen der Profile und dem Auslösewert - im Standard überwacht das Modul Batterien
5. Bei Bedarf einrichten der Email/SMTP Instanz und Aktivieren der Benachrichtigung (bei der App wird ein konfiguriertes Webfront vorausgesetzt und automatisch ausgewählt)


## Nutzung
Das Modul fragt alle x Tage um eine definerte Zeit alle Variablen ab bei denen ein Batterieprofil gesetzt wurde. Wenn es leere Batterien gibt, dann wird die Alarmvariable gesetzt (true) und die Anzahl der leere Batterien hochgezählt. Weiterhin ist es möglich die Aktoren im Webfront anzuzeigen, als HTML Box oder eine Nachricht via Email oder die App zu senden.

## Version
1.0 - 16-02-2023
* Abfragen von beliebigen Profilen
* Alarmvariable
* Zähler für gefundene Variablen
* HTML Box fürs Webfront 
* Nachricht via Email / Symcon App

