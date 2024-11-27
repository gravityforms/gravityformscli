=== Gravity Forms CLI Add-On ===
Contributors: rocketgenius
Tags: gravity forms
Requires at least: 4.2
Tested up to: 6.7
Stable tag: 1.7
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Manage Gravity Forms on the command line.

== Description ==

The Gravity Forms CLI Add-On allows WP-CLI users to manage forms and entries and more on the command line.

[Documentation](https://docs.gravityforms.com/category/add-ons-gravity-forms/wp-cli-add-on/)

= Getting started =

*   `wp help gf`
*   `wp help gf form`
*   `wp help gf form field`
*   `wp help gf form notification`
*   `wp help gf entry`
*   `wp help gf entry notification`
*   `wp help gf install`
*   `wp help gf setup`
*   `wp help gf license`
*   `wp help gf tool`

= Form Management =

`wp gf form [command]`

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

= Notification Management =

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

= Entry Management =

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

= Entry Notifications =

`wp gf entry notification [command]`

Commands:

*  get - Returns the notifications for the given entry.
*  send - Sends the notifications for the given entry.

= License Management =

`wp gf license [command]`

Commands:

*  update - Updates the license key for the installation.
*  delete - Deletes the license key for the installation.

= Misc Tools =

`wp gf tool [command]`

Commands:

*  clear-transients
*  empty-trash           Delete the trashed entries.
*  verify-checksums      Verify Gravity Forms files against the checksums.
*  system-report         Outputs the system report from the Forms > System Status page. Supports "status" as an alias.

= Installing Gravity Forms and Add-Ons =

The above commands all require Gravity Forms to be installed. However, if Gravity Forms is not installed then you can use this add-on to install it along with all the other official Gravity Forms add-ons.

The install command will download and install the latest version Gravity Forms available for auto-update and then run the database setup. The license key will be saved in the plugin settings.

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

Gravity Forms and official add-ons can be updated using the update command.

Examples:

* `wp gf update`
* `wp gf update gravityformspolls`


Check the current version using the version command.

Examples:

* `wp gf version`
* `wp gf version gravityformspolls`


= Requirements =

1. Wordpress 4.2+
2. Gravity Forms 1.9.17.8
3. WP-CLI version 1.0+

= Support =

If you find anything that needs fixing please open a support ticket at https://www.gravityforms.com/open-support-ticket/

If you have any ideas for improvements please submit your idea at https://www.gravityforms.com/gravity-forms-roadmap/

== Installation ==

`wp plugin install gravityformscli --activate`

or

`wp package install https://github.com/gravityforms/gravityformscli.git`

or

1.  Download the zipped file.
1.  Extract and upload the contents of the folder to /wp-contents/plugins/ folder
1.  Go to the Plugin management page of WordPress admin section and enable the 'Gravity Forms CLI' plugin

== ChangeLog ==
= 1.7 =
- Fixed a bug that sometimes causes the form ID to be stored as a string.

= 1.6 =
- Updated the plugin to support installing and updating Gravity SMTP.

= 1.5 =
- Updated the plugin to allow its usage as a WP-CLI package.

= 1.4 =
- Fixed an issue where the version comparison performed when using `wp gf update` with an add-on slug uses the Gravity Forms version number.
- Added support for using `--version=beta` with the `wp gf install` and `wp gf update` commands. Add-On beta releases are not currently supported.
- Fixed a fatal error which can occur when using the `wp gf version` command with an add-on slug when Gravity Forms is not active or not installed.

= 1.3 =
- Fixed an error occurring when using the `wp gf form notification create` command.

= 1.2 =
- Updated Gravity API domain.
- Updated the ids format of the `wp gf entry list` command to support the page-size and offset args. Credit: Ulrich Pogson.
- Updated the form export command to support the optional filename arg e.g. `wp gf form export 1 --filename=testing.json`. Credit: Timothy Decker.
- Fixed an issue where the `wp gf install` command could not network activate plugins.
- Fixed an error occurring when using the `wp gf form notification update` command without the notification-id arg.
- Fixed an issue with the `wp gf entry export` command.

= 1.1 =
- Added support for start_date and end_date filters for the entry export command. e.g. `wp gf entry export 11 --start_date="2018-11-01" --end_date="2018-11-11"`
- Added the `wp gf tool system-report` command and the `wp gf tool status` alias for outputting the system report from the Gravity Forms 2.2+ System Status page.
- Fixed an issue with the `wp gf install` command ending with an error message when no error occurred.
- Fixed an issue where old messages could continue to be displayed in the admin following a license key change.
- Fixed an "invalid synopsis part" warning and an "unknown parameter" error with the `wp gf form field update` command.
- Fixed the `wp gf form update` command using the wrong argument to get the existing form which could result in a form not found error.
- Fixed an issue with the `wp gf form create` command where missing field IDs are added automatically.

= 1.0 =
- Added the `wp gf license` command.
- Fixed an issue with updating forms from an export file.

= 1.0-rc-1 =
- Added the `--file` arg to the `wp gf form update` command to allow forms to be updated from an export file.
- Fixed a fatal error when using the install command.

= 1.0-beta-5 =
- Added the `wp gf form notification` command.
- Added the `wp gf entry notification` command.
- Added the `wp gf version` command.
- Added the `wp gf update` command.
- Updated the `install` and `update` commands to download the latest hotfix version by default.
- Fixed an issue with `wp gf form export <form-id>` where the form ID is ignored.

= 1.0-beta-4 =
- Updated the `install` command to pass the `--force` value to the setup command.
- Updated the output when forcing the setup.

= 1.0-beta-3 =
- Added the `wp gf setup` command
- Fixed an issue with the `install` command where the database was not setup until visiting the WP dashboard.
- Updated the WP-CLI requirement to 1.0+

= 1.0-beta-2 =
- Added support for the WP-CLI package index.
- Fixed entry export.

= 1.0-beta-1 =
- All new!
