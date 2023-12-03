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

1.2 - 01-04-2023
* New - Added second timer option, allowing to check profiles every xx minutes
* New - Results can be stored in variable as a JSON
* New - Variable that captures the time of the check
* New - The possibility to trigger the module manually - e.g. via the webfront
* Fix - HTML Box Error if not configured 

1.3 - 08-04-2023
* New - All variables that were checked can be saved to a variable as JSON
* New - It is possible to save the triggering value in the HTML table
* Fix - The HTML variable works also with the Light Skin

1.4 - 28-05-2023
* New - Timestamp when Variable was update last can be displayed
* New - Padding in HTML can be configured

1.5 - 11-06-2023
* New - Ability to configure content of the email sent
* Fix - Selecting more than 1 exclusion did not work
* Fix - HTML now shows a message when nothing is found

1.6 - 30-11-2023
* New - Support for Symcon 7 Visu Notification, incl. the option to open an object when clicking on notification (Link below Webfront)