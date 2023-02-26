# Profile Monitor

The Profile Monitor is a module that allows to monitor variables and their profiles. An example would be the search for empty batteries which are displayed in a variable. If a variable with a certain profile value is found, then this variable can be displayed in the webfront or email/app notifications can be sent.

* Set an alarm variable
* Vatiable with the number of found profiles/variables
* Notification via Email 
* Notification via Symcon App
* List found variables in a HTML box
* Possibility to customize the message texts

## Setup
The setup of the module is very simple. 
1. download the module via Module Store or github https://github.com/elueckel/Profile-Monitor 
2. creation of the instance: Profile Monitor
3. determine the time and frequency of execution 
4. select the profiles and the trigger value - by default the module monitors batteries
5. if needed, set up email/SMTP instance and enable notification (for the app, a configured webfront is assumed and automatically selected)


## Usage
The module queries every x days at a defined time all variables where a battery profile has been set. If there are empty batteries, then the alarm variable is set (true) and the number of empty batteries is counted up. Furthermore it is possible to display the actuators in the webfront, as HTML box or send a message via email or the app.

## Version
1.0 - 16-02-2023
* Query of any profile
* Alarm variable
* Counter for found variables
* HTML box for webfront 
* Message via Email / Symcon App

1.1 - 26-02-2023
* New - Variables can be excluded
* New - In the webfront it is now possible to display ID, parent object and path
* New - selection of a webfront is possible
* Fix - if no variables were found, webfront was not cleared
