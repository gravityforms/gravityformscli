<?php

/**
 * Manage Gravity Forms.
 *
 * @class    GF_CLI
 * @version  1.0
 * @package  GravityForms/CLI
 * @category CLI
 * @author   Rocketgenius
 * @copyright Copyright (c) 2016-2018, Rocketgenius
 */
class GF_CLI_Root extends WP_CLI_Command {

	/**
	 * Returns the version of Gravity Forms.
	 *
	 * @since 1.0-beta-5
	 * @access public
	 *
	 * [<slug>]
	 * : The slug of the plugin. Default: gravityforms
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf version
	 *     wp gf version gravityformspolls
	 */
	public function version( $args, $assoc_args ) {
		$slug = isset( $args[0] ) ? $args[0] : 'gravityforms';

		if ( $slug == 'gravityforms' ) {
			if ( class_exists( 'GFForms' ) ) {
				WP_CLI::log( GFForms::$version );
			} else {
				WP_CLI::error( 'Gravity Forms is not installed. Use the wp gf install command.' );
			}
		} else {
			$addon_class_names = GFAddOn::get_registered_addons();
			$addon_found = false;
			foreach ( $addon_class_names as $addon_class_name ) {
				/* @var GFAddon $addon */
				$addon = call_user_func( array( $addon_class_name, 'get_instance' ) );
				if ( $addon->get_slug() == $slug ) {
					WP_CLI::log( $addon->get_version() );
					$addon_found = true;
					break;
				}
			}

			if ( ! $addon_found ) {
				WP_CLI::error( 'Invalid pluging slug: ' . $slug );
			}
		}
	}

	/**
	 * Installs Gravity Forms or a Gravity Forms official add-on.
	 *
	 * A valid key is required either in the GF_LICENSE_KEY constant or the --key option.
	 *
	 * @since 1.0
	 * @access public
	 *
	 * [<slug>]
	 * : The slug of the add-on. Default: gravityforms
	 *
	 * [--key=<key>]
	 * : The license key if not already available in the GF_LICENSE_KEY constant.
	 *
	 * [--version=<version>]
	 * : The version to be installed. Accepted values: auto-update, hotfix. Default: hotfix.
	 *
	 * [--force]
	 * : If set, the command will overwrite any installed version of the plugin, without prompting for confirmation.
	 *
	 * [--activate]
	 * : If set, the plugin will be activated immediately after install.
	 *
	 * [--network-activate]
	 * : If set, the plugin will be network activated immediately after install
	 *
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf install
	 *     wp gf install --force
	 *     wp gf install --key=[A valid Gravity Forms License Key]
	 *     wp gf install gravityformspolls key=[1234ABC]
	 * @synopsis [<slug>] [--key=<key>] [--version=<version>] [--force] [--activate] [--network-activate]
	 */
	public function install( $args, $assoc_args ) {
		$slug = isset( $args[0] ) ? $args[0] : 'gravityforms';

		$key = isset( $assoc_args['key'] ) ? $assoc_args['key'] : $key = $this->get_key();

		if ( empty( $key ) ) {
			WP_CLI::error( 'A valid license key must be specified either in the GF_LICENSE_KEY constant or the --key option.' );
		}

		$this->save_key( $key );

		$key = md5( $key );

		$plugin_info = $this->get_plugin_info( $slug, $key );

		$version = isset( $assoc_args['version'] ) ? $assoc_args['version'] : 'hotfix';

		if ( $version == 'hotfix' ) {
			$download_url = isset( $plugin_info['download_url_latest'] ) ? $plugin_info['download_url_latest'] : '';
		} else {
			$download_url = isset( $plugin_info['download_url'] ) ? $plugin_info['download_url'] : '';
		}

		if ( $plugin_info && ! empty( $download_url ) ) {

			$download_url .= '&key=' . $key;

			$force = WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );

			$activate = WP_CLI\Utils\get_flag_value( $assoc_args, 'activate', false );

			$network_activate = WP_CLI\Utils\get_flag_value( $assoc_args, 'network-activate', false );

			$command = sprintf( 'plugin install "%s"', $download_url );

			$install_assoc_args = array();

			if ( $force ) {
				$install_assoc_args['force'] = true;
				$command .= ' --force';
			}

			if ( $activate ) {
				$install_assoc_args['activate'] = true;
				$command .= ' --activate';
			}

			if ( $network_activate ) {
				$install_assoc_args['network-activate'] = true;
				$command .= ' --network-activate';
			}

			$options = array(
				'return' => false,
				'launch' => true,
				'exit_error' => true,
			);

			WP_CLI::runcommand( $command, $options );

		} else {
			WP_CLI::error( 'There was a problem retrieving the download URL, please check the key.' );
		}
	}

	/**
	 * Updates Gravity Forms or a Gravity Forms official add-on.
	 *
	 * A valid key is required either in the Gravity Forms settings, the GF_LICENSE_KEY constant or the --key option.
	 *
	 * @since 1.0
	 * @access public
	 *
	 * [<slug>]
	 * : The slug of the add-on. Default: gravityforms
	 *
	 * [--key=<key>]
	 * : The license key if not already available in the GF_LICENSE_KEY constant.
	 *
	 * [--version=<version>]
	 * : The version to be installed. Accepted values: auto-update, hotfix. Default: hotfix.
	 *
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf update
	 *     wp gf install --key=[A valid Gravity Forms License Key]
	 *     wp gf install gravityformspolls key=[1234ABC]
	 * @synopsis [<slug>] [--key=<key>] [--version=<version>]
	 */
	public function update( $args, $assoc_args ) {

		if ( ! class_exists( 'GFForms' ) ) {
			WP_CLI::error( 'Gravity Forms is not active.' );
		}

		$slug = isset( $args[0] ) ? $args[0] : 'gravityforms';

		$key = isset( $assoc_args['key'] ) ? $assoc_args['key'] : $key = $this->get_key();

		if ( empty( $key ) ) {
			$key = GFCommon::get_key();
		} else {
			$this->save_key( $key );
			$key = GFCommon::get_key();
		}

		if ( empty( $key ) ) {
			WP_CLI::error( 'A valid license key must be saved in the settings or specified in the GF_LICENSE_KEY constant or the --key option.' );
		}

		$plugin_info = $this->get_plugin_info( $slug, $key );

		$version = isset( $assoc_args['version'] ) ? $assoc_args['version'] : 'hotfix';

		if ( $version == 'hotfix' ) {
			$available_version = isset( $plugin_info['version_latest'] ) ? $plugin_info['version_latest'] : '';
			$download_url = isset( $plugin_info['download_url_latest'] ) ? $plugin_info['download_url_latest'] : '';
		} else {
			$available_version = isset( $plugin_info['version'] ) ? $plugin_info['version'] : '';
			$download_url = isset( $plugin_info['download_url'] ) ? $plugin_info['download_url'] : '';
		}

		if ( version_compare( GFForms::$version, $available_version, '>=' ) ) {
			WP_CLI::success( 'Plugin already updated' );
			return;
		}

		if ( $plugin_info && ! empty( $download_url ) ) {

			$download_url .= '&key=' . $key;

			$command = sprintf( 'plugin install "%s" --force', $download_url );

			$options = array(
				'return' => false,
				'launch' => true,
				'exit_error' => true,
			);

			WP_CLI::runcommand( $command, $options );

			$setup_command = 'gf setup ' . $slug . ' --force';

			WP_CLI::runcommand( $setup_command, $options );

		} else {
			WP_CLI::error( 'There was a problem retrieving the download URL, please check the key.' );
		}
	}

	/**
	 * Runs the setup for Gravity Forms or a Gravity Forms official add-on.
	 *
	 *
	 * @since 1.0
	 * @access public
	 *
	 * [<slug>]
	 * : The slug of the add-on. Default: gravityforms
	 *
	 * [--force]
	 * : If set, the command will run the setup regardless of whether it has been run before.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf setup
	 *     wp gf setup --force
	 *     wp gf setup gravityformspolls
	 *     wp gf setup gravityformspolls --force
	 *
	 * @synopsis [<slug>] [--force]
	 */
	public function setup( $args, $assoc_args ) {

		$slug = isset( $args[0] ) ? $args[0] : 'gravityforms';

		$force = WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );

		if ( $slug == 'gravityforms' ) {
			$versions = gf_upgrade()->get_versions();
			if ( empty( $versions['current_version'] ) ) {
				// First setup
				gf_upgrade()->maybe_upgrade();
				WP_CLI::success( 'setup ' . $slug );
			} else {
				// Re-running setup
				if ( $force ) {
					gf_upgrade()->upgrade( $versions['previous_db_version'], true );
					WP_CLI::success( 'Database upgraded.' );
				} else {
					WP_CLI::error( 'Use the --force flag to force the database setup.' );
				}
			}
		} else {
			$addon_class_names = GFAddOn::get_registered_addons();
			foreach ( $addon_class_names as $addon_class_name ) {
				$addon = call_user_func( array( $addon_class_name, 'get_instance' ) );
				if ( $addon->get_slug() == $slug ) {
					$addon->setup();
					WP_CLI::success( 'setup ' . $slug );
					break;
				}
			}
		}
	}

	/**
	 * Checks for available updates for Gravity Forms or a Gravity Forms official add-on.
	 *
	 * A valid key is required either in the GF_LICENSE_KEY constant or the --key option.
	 *
	 * @since 1.0-beta-2
	 * @access public
	 *
	 * [<slug>]
	 * : The slug of the add-on. Default: gravityforms
	 *
	 * [--format=<format>]
	 * : Accepted values: table, csv, json, count. Default: table.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf check-update
	 *     wp gf check-update gravityformspolls
	 *
	 * @synopsis [<slug>] [--format=<format>]
	 * @alias check-update
	 */
	public function check_update( $args, $assoc_args ) {
		$slug = isset( $args[0] ) ? $args[0] : 'gravityforms';

		$plugin_info = $this->get_plugin_info( $slug );

		$versions = array(
			 array(
				 'type' => 'auto-update',
				'version' => $plugin_info['version'],
			),
			array(
				'type' => 'hotfix',
				'version' => $plugin_info['version_latest'],
			),
		);

		$fields = array(
			'type',
			'version',
		);

		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';

		WP_CLI\Utils\format_items( $format, $versions, $fields );
	}

	private function save_key( $key ) {
		$current_key = get_option( 'rg_gforms_key' );
		if ( empty( $key ) ) {
			delete_option( 'rg_gforms_key' );
		} else if ( $current_key != $key ) {
			$key = trim( $key );
			update_option( 'rg_gforms_key', md5( $key ) );
		}
	}

	private function get_key() {
		global $gf_license_key;
		$license_key = defined( 'GF_LICENSE_KEY' ) && empty( $gf_license_key ) ? GF_LICENSE_KEY : $gf_license_key;
		return $license_key;
	}

	private function get_plugin_info( $slug, $key = '' ) {

		$gravity_manager_url = defined( 'GRAVITY_MANAGER_URL' ) && GRAVITY_MANAGER_URL ? GRAVITY_MANAGER_URL : 'https://www.gravityhelp.com/wp-content/plugins/gravitymanager';

		$url      = $gravity_manager_url . "/api.php?op=get_plugin&slug={$slug}&key={$key}";
		$options  = array(
			'timeout' => 30,
		);
		$headers  = array(
			'Accept' => 'text/plain',
		);
		$response = \WP_CLI\Utils\http_request( 'GET', $url, null, $headers, $options );
		if ( ! $response->success || 200 != $response->status_code ) {
			WP_CLI::error( 'There was a problem getting the download URL' );
		}
		$body      = trim( $response->body );
		$plugin_info = unserialize( $body );
		return $plugin_info;
	}
}
