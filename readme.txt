=== Gravity Forms CLI Add-On ===
Contributors: rocketgenius, stevehenty
Tags: gravity forms
Requires at least: 4.2
Tested up to: 4.8.1
Stable tag: trunk
License: GPL-3.0+
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Manage Gravity Forms on the command line.

== Description ==

The Gravity Forms CLI Add-On allows WP-CLI users to manage forms and entries and more on the command line.

Form Management
[youtube https://www.youtube.com/watch?v=LO3fLW6SWk0]

Entry Management
[youtube https://www.youtube.com/watch?v=KRI2NIsf75U]

= Getting started =

*   wp help gf
*   wp help gf form
*   wp help gf form field
*   wp help gf form notification
*   wp help gf entry
*   wp help gf entry notification
*   wp help gf install
*   wp help gf setup
*   wp help gf license
*   wp help gf tool

= Form Management =

wp gf form [command]

Commands:

*  create - Creates a new form.
*  delete - Deletes a form.
*  duplicate - Duplicates a form.
*  edit - Launch system editor to edit the Form configuration.
*  export - Exports forms to a Gravity Forms Form export file.
*  form_list - Lists the forms with entry count and view counts.
*  get - Returns the form JSON.
*  import - Imports forms from a Gravity Forms Form export file.
*  update - Updates a form.

= Field Management =

* wp gf form field [command]
* wp gf field [command] (alias)

Commands:

*  create - Creates a field and adds it to a form.
*  delete - Deletes a field.
*  duplicate - Duplicates a field.
*  edit - Launch system editor to edit the Field configuration.
*  get - Returns the JSON representation of a field.
*  list  - Displays a list of fields for a form.
*  update - Updates a field.

= Notification Management =

* wp gf form notification [command]
* wp gf notification [command] (alias)

Commands:

*  create - Creates a new notification.
*  delete - Deletes a notification.
*  duplicate - Duplicates a notification.
*  edit - Launch system editor to edit the notification configuration.
*  list - Lists the notification.
*  get - Returns the notification JSON.
*  update - Updates a notification.

= Entry Management =

wp gf entry [command]

Commands:

*  create - Creates a new entry from either a JSON string with the raw entry or from field-value pairs.
*  delete - Deletes an entry.
*  duplicate - Duplicates an entry
*  edit - Launch system editor to edit the JSON representation of the Entry.
*  export - Exports entries.
*  get - Returns a JSON representation of an entry.
*  import - Imports entries.
*  list - Displays a list of entries.
*  update - Updates an entry.

= Entry Notifications =

wp gf entry notification [command]

Commands:

*  get - Returns the notifications for the given entry.
*  send - Sends the notifications for the given entry.

= License Management =

wp gf license [command]

Commands:

*  update - Updates the license key for the installation.
*  delete - Deletes the license key for the installation.

= Misc Tools =

wp gf tool [command]

Commands:

*  clear-transients
*  empty-trash           Delete the trashed entries.
*  verify-checksums      Verify Gravity Forms files against the checksums.

= Installing Gravity Forms and Add-Ons =

The above commands all require Gravity Forms to be installed. However, if Gravity Forms is not installed then you can use this add-on to install it along with all the other official Gravity Forms add-ons.

The install command will download and install the latest version Gravity Forms available for auto-update and then run the database setup. The license key will be saved in the plugin settings.

A valid license key is required either in the GF_LICENSE_KEY constant or the --key option.

Examples:

* wp gf install -key=xxxxx
* wp gf install -key=xxxxx --activate
* wp gf install gravityformspolls -key=xxxxx
* wp gf install gravityformsquiz -key=xxxxx

Once installed, the database can be set up or upgraded separately using the setup command. The command will not re-run the setup unless the --force flag is set.

Examples:

* wp gf setup
* wp gf setup --force

Gravity Forms and official add-ons can be updated using the update command.

Examples:

* wp gf update
* wp gf update gravityformspolls


Check the current version using the version command.

Examples:

* wp gf version
* wp gf version gravityformspolls


= Requirements =

1. Wordpress 4.2+
2. Gravity Forms 1.9.17.8
3. WP-CLI version 1.0+

= Support =

If you find any that needs fixing, or if you have any ideas for improvements, please submit a support ticket:
https://www.gravityhelp.com/request-support/


== Installation ==

1.  Download the zipped file.
1.  Extract and upload the contents of the folder to /wp-contents/plugins/ folder
1.  Go to the Plugin management page of WordPress admin section and enable the 'Gravity Forms CLI' plugin

== ChangeLog ==

= 1.0 =
- Added the wp gf license command.
- Fixed an issue with updating forms from an export file.

= 1.0-rc-1 =
- Added the --file arg to the wp gf form update command to allow forms to be updated from an export file.
- Fixed a fatal error when using the install command.

= 1.0-beta-5 =
- Added the wp gf form notification command.
- Added the wp gf entry notification command.
- Added the wp version command.
- Added the wp update command.
- Updated the install and update commands to download the latest hotfix version by default.
- Fixed an issue with wp gf form export <form-id> where the form ID is ignored.

= 1.0-beta-4 =
- Updated the install command to pass the --force value to the setup command.
- Updated the output when forcing the setup.

= 1.0-beta-3 =
- Added the wp gf setup command
- Fixed an issue with the install command where the database was not setup until visiting the WP dashboard.
- Updated the WP-CLI requirement to 1.0+

= 1.0-beta-2 =
- Added support for the WP-CLI package index.
- Fixed entry export.

= 1.0-beta-1 =
- All new!
