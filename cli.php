<?php
/*
Plugin Name: Gravity Forms CLI
Plugin URI: https://gravityforms.com
Description: Manage Gravity Forms with the WP CLI.
Version: 1.6
Author: Rocketgenius
Author URI: https://gravityforms.com
License: GPL-2.0+
Text Domain: gravityformscli
Domain Path: /languages

------------------------------------------------------------------------
Copyright 2016-2020 Rocketgenius, Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see http://www.gnu.org/licenses.
*/

defined( 'ABSPATH' ) || defined( 'WP_CLI' ) || die();

// Defines the current version of the CLI add-on
define( 'GF_CLI_VERSION', '1.6' );

define( 'GF_CLI_MIN_GF_VERSION', '1.9.17.8' );

// After GF is loaded, load the CLI add-on
defined( 'ABSPATH' ) && add_action( 'gform_loaded', array( 'GF_CLI_Bootstrap', 'load_addon' ), 1 );



/**
 * Loads the Gravity Forms CLI add-on.
 *
 * Includes the main class, registers it with GFAddOn, and adds WPCLI commands.
 *
 * @since 1.0-beta-1
 */
class GF_CLI_Bootstrap {

	/**
	 * Loads the required files, and adds CLI commands.
	 *
	 * @since 1.0-beta-1
	 * @access public
	 * @static
	 */
	public static function load_addon() {

		// Requires the class file
		require_once( plugin_dir_path( __FILE__ ) . '/class-gf-cli.php' );

		// Registers the class name with GFAddOn
		GFAddOn::register( 'GF_CLI' );
	}

	public static function load_cli() {
		// Ensure that WP-CLI is available
		if ( defined( 'WP_CLI' ) && WP_CLI ) {

			// Checks for files within the includes directory, and includes them.
			foreach ( glob( dirname( __FILE__ ) . '/includes/*.php' ) as $filename ) {
				require_once( $filename );
			}
			$command_args = array( 'before_invoke' => array( 'GF_CLI_Bootstrap', 'before_invoke' ) );

			// Adds WP-CLI commands, and maps them to the appropriate class.
			WP_CLI::add_command( 'gf', 'GF_CLI_Root' );
			WP_CLI::add_command( 'gf form', 'GF_CLI_Form', $command_args );
			WP_CLI::add_command( 'gf form notification', 'GF_CLI_Form_Notification', $command_args );
			WP_CLI::add_command( 'gf notification', 'GF_CLI_Form_Notification', $command_args );
			WP_CLI::add_command( 'gf form field', 'GF_CLI_Form_Field', $command_args );
			WP_CLI::add_command( 'gf field', 'GF_CLI_Form_Field', $command_args );
			WP_CLI::add_command( 'gf entry', 'GF_CLI_Entry', $command_args );
			WP_CLI::add_command( 'gf license', 'GF_CLI_License', $command_args );
			WP_CLI::add_command( 'gf entry notification', 'GF_CLI_Entry_Notification', $command_args );
			WP_CLI::add_command( 'gf tool', 'GF_CLI_Tool', $command_args );
		}
	}

	public static function before_invoke() {
		if ( ! class_exists( 'GFForms' ) ) {
			WP_CLI::error( 'Gravity Forms is not installed. Use the "wp gf install" command to install Gravity Forms.' );
		}

		if ( version_compare( GFForms::$version, GF_CLI_MIN_GF_VERSION, '<' ) ) {
			WP_CLI::error( 'The minimum required version of Gravity Forms is ' . GF_CLI_MIN_GF_VERSION . '. The version installed is ' . GFForms::$version . '. Please upgrade Gravity Forms to the latest version.' );
		}
	}
}

GF_CLI_Bootstrap::load_cli();

/**
 * Returns an instance of the GF_CLI class
 *
 * @since 1.0-beta-1
 * @return object An instance of the GF_CLI class
 */
function gf_cli() {
	return GF_CLI::get_instance();
}

