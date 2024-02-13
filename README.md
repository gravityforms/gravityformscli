Gravity Forms CLI Add-On
==============================

The Gravity Forms CLI Add-On allows WP-CLI users to manage installation, forms and entries on the command line.

[Documentation](https://docs.gravityforms.com/category/add-ons-gravity-forms/wp-cli-add-on/)


Getting started
---------------

*   `wp help gf`
*   `wp help gf form`
*   `wp help gf field`
*   `wp help gf entry`
*   `wp gf license`
*   `wp help gf tool`

Form Management
---------------

`wp gf form [command]`

Commands:

*  create - Creates a new form.
*  delete - Deletes a form.
*  duplicate - Duplicates a form.
*  edit - Launch system editor to edit the Form configuration.
*  export - Exports forms to a Gravity Forms Form export file.
*  list - Lists the forms with entry count and view counts.
*  get - Returns the form JSON.
*  import - Imports forms from a Gravity Forms Form export file.
*  update - Updates a form.

Field Management
----------------

* `wp gf form field [command]`
* `wp gf field [command]` (alias)

Commands:

*  create - Creates a field and adds it to a form.
*  delete - Deletes a field.
*  duplicate - Duplicates a field.
*  edit - Launch system editor to edit the Field configuration.
*  get - Returns the JSON representation of a field.
*  list  - Displays a list of fields for a form.
*  update - Updates a field.

Notification Management
-----------------------

* `wp gf form notification [command]`
* `wp gf notification [command]` (alias)

Commands:

*  create - Creates a new notification.
*  delete - Deletes a notification.
*  duplicate - Duplicates a notification.
*  edit - Launch system editor to edit the notification configuration.
*  list - Lists the notification.
*  get - Returns the notification JSON.
*  update - Updates a notification.

Entry Management
----------------

`wp gf entry [command]`

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

Entry Notifications
-------------------

`wp gf entry notification [command]`

Commands:

*  get - Returns the notifications for the given entry.
*  send - Sends the notifications for the given entry.

License Management
-------------------

`wp gf license [command]`

Commands:

*  update - Updates the license key for the installation.
*  delete - Deletes the license key for the installation.


Misc Tools
----------

`wp gf tool [command]`

Commands:

*  clear_transients
*  empty-trash           Delete the trashed entries.
*  verify-checksums      Verify Gravity Forms files against the checksums.
*  system-report         Outputs the system report from the Forms > System Status page. Supports "status" as an alias.

Installing and Updating Gravity Forms and Add-Ons
-------------------------------------------------

The above commands all require Gravity Forms to be installed. However, if Gravity Forms is not installed then you can use this add-on to install it along with all the other official Gravity Forms add-ons.

The install command will download and install the latest version Gravity Forms and then run the database setup. The license key will be saved in the plugin settings.

A valid license key is required either in the `GF_LICENSE_KEY` constant or the `--key` option.

Examples:

* `wp gf install --key=xxxxx`
* `wp gf install --key=xxxxx --activate`
* `wp gf install gravityformspolls --key=xxxxx`
* `wp gf install gravityformsquiz --key=xxxxx`

Once installed, the database can be set up or upgraded separately using the setup command. The command will not re-run the setup unless the `--force` flag is set.

Examples:

* `wp gf setup`
* `wp gf setup --force`

Gravity Forms and add-ons can be updated using the update command.

Examples:

* `wp gf update`
* `wp gf update gravityformspolls`

Check the current version using the version command.

Examples:

* `wp gf version`
* `wp gf version gravityformspolls`


Requirements
------------

1. Wordpress 4.2+
2. Gravity Forms > 1.9.17.8
3. WP-CLI v1.0+


Support
-------

If you find anything that needs fixing please open a support ticket at https://www.gravityforms.com/open-support-ticket/

If you have any ideas for improvements please submit your idea at https://www.gravityforms.com/gravity-forms-roadmap/
