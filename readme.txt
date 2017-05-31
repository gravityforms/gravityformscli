=== Gravity Forms CLI Add-On ===
Contributors: rocketgenius, stevehenty
Tags: gravity forms
Requires at least: 4.2
Tested up to: 4.7.5
Stable tag: trunk
License: GPL-3.0+
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Manage Gravity Forms on the command line.

== Description ==

The Gravity Forms CLI Add-On allows WP-CLI users to manage forms and entries on the command line.

Form Management
[youtube https://www.youtube.com/watch?v=LO3fLW6SWk0]

Entry Management
[youtube https://www.youtube.com/watch?v=KRI2NIsf75U]

= Getting started =

*   wp help gf
*   wp help gf form
*   wp help gf field
*   wp help gf entry
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

wp gf field [command]

Commands:

*  create - Creates a field and adds it to a form.
*  delete - Deletes a field.
*  duplicate - Duplicates a field.
*  edit - Launch system editor to edit the Field configuration.
*  get - Returns the JSON representation of a field.
*  list  - Displays a list of fields for a form.
*  update - Updates a field.

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

= Misc Tools =

wp gf tool [command]

Commands:

*  clear_transients
*  empty-trash           Delete the trashed entries.
*  verify-checksums      Verify Gravity Forms files against the checksums.

= Installing Gravity Forms and Add-Ons =

The above commands all require Gravity Forms to be installed. However, if Gravity Forms is not installed then you can use this add-on to install it along with all the other official Gravity Forms add-ons.

The install command will download and install the latest version Gravity Forms available for auto-update and then run the database setup.

A valid license key is required either in the GF_LICENSE_KEY constant or the --key option.

Examples:

* wp gf install -key=xxxxx
* wp gf install -key=xxxxx --activate
* wp gf install gravityformspolls -key=xxxxx
* wp gf install gravityformsquiz -key=xxxxx

The database can be set up using the setup command. The command will not re-run the setup unless the --force flag is set.

Examples:

* wp gf setup
* wp gf setup --force


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
