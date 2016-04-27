<?php

// Include the Gravity Forms add-on framework
GFForms::include_addon_framework();

class GF_CLI extends GFAddOn {
	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since 1.0-beta-1
	 * @access private
	 * @var object $_instance If available, contains an instance of this class
	 */
	private static $_instance = null;

	/**
	 * Defines the version of the WP-CLI add-on.
	 *
	 * @since 1.0-beta-1
	 * @access protected
	 * @var string $_version Contains the version, defined from cli.php
	 */
	protected $_version = GF_CLI_VERSION;
	/**
	 * Defines the minimum Gravity Forms version required.
	 * @since 1.0-beta-1
	 * @access protected
	 * @var string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = GF_CLI_MIN_GF_VERSION;
	/**
	 * Defines the plugin slug.
	 *
	 * @since 1.0-beta-1
	 * @access protected
	 * @var string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'gravityformscli';
	/**
	 * Defines the main plugin file.
	 *
	 * @since 1.0-beta-1
	 * @access protected
	 * @var string $_path The path to the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'gravityformscli/cli.php';
	/**
	 * Defines the full path to this class file.
	 *
	 * @since 1.0-beta-1
	 * @access protected
	 * @var string $_full_path The full path.
	 */
	protected $_full_path = __FILE__;
	/**
	 * Defines the URL where this add-on can be found.
	 *
	 * @since 1.0-beta-1
	 * @access protected
	 * @var string
	 */
	protected $_url = 'http://www.gravityforms.com';
	/**
	 * Defines the title of this add-on.
	 *
	 * @since 1.0-beta-1
	 * @access protected
	 * @var string $_title The title of the add-on.
	 */
	protected $_title = 'Gravity Forms CLI Add-On';
	/**
	 * Defines the short title of the add-on.
	 *
	 * @since 1.0-beta-1
	 * @access protected
	 * @var string $_short_title The short title.
	 */
	protected $_short_title = 'CLI';

	/**
	 * Returns an instance of this class, and stores it in the $_instance property.
	 *
	 * @since 1.0-beta-1
	 * @access public
	 * @static
	 * @return object $_instance An instance of the GF_CLI class
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GF_CLI();
		}

		return self::$_instance;
	}

	private function __clone() {
	} /* do nothing */

}



