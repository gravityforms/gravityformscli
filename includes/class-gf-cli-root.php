<?php

defined( 'ABSPATH' ) || defined( 'WP_CLI' ) || die();

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
		if ( ! class_exists( 'GFForms' ) ) {
			WP_CLI::error( 'Gravity Forms is not installed. Use the wp gf install command.' );
		}

		$slug = $this->get_slug( $args );

		if ( $slug == 'gravityforms' ) {
			WP_CLI::log( GFForms::$version );
		} else {
			$addon = $this->get_addon( $slug );
			WP_CLI::log( $addon->get_version() );
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
	 * : The version to be installed. Accepted values: auto-update, hotfix, or beta. Default: hotfix.
	 *
	 * [--force]
	 * : If set, the command will overwrite any installed version of the plugin, without prompting for confirmation.
	 *
	 * [--activate]
	 * : If set, the plugin will be activated immediately after install.
	 *
	 * [--activate-network]
	 * : If set, the plugin will be network activated immediately after install
	 *
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf install
	 *     wp gf install --force
	 *     wp gf install --key=[A valid Gravity Forms License Key]
	 *     wp gf install gravityformspolls key=[1234ABC]
	 * @synopsis [<slug>] [--key=<key>] [--version=<version>] [--force] [--activate] [--activate-network]
	 */
	public function install( $args, $assoc_args ) {
		$slug = $this->get_slug( $args, true );
		$key  = isset( $assoc_args['key'] ) ? $assoc_args['key'] : $key = $this->get_key();

		if ( empty( $key ) ) {
			WP_CLI::error( 'A valid license key must be specified either in the GF_LICENSE_KEY constant or the --key option.' );
		}

		$this->save_key( $key );

		$key = md5( $key );

		$version     = isset( $assoc_args['version'] ) ? $assoc_args['version'] : 'hotfix';
		$plugin_info = $version === 'beta' ? $this->get_beta_plugin_info( $slug, $key ) : $this->get_plugin_info( $slug, $key );

		if ( $version == 'hotfix' ) {
			$download_url = isset( $plugin_info['download_url_latest'] ) ? $plugin_info['download_url_latest'] : '';
		} else {
			$download_url = isset( $plugin_info['download_url'] ) ? $plugin_info['download_url'] : '';
		}

		if ( $plugin_info && ! empty( $download_url ) ) {

			$download_url .= '&key=' . $key;

			$force = WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );

			$activate = WP_CLI\Utils\get_flag_value( $assoc_args, 'activate', false );

			$activate_network = WP_CLI\Utils\get_flag_value( $assoc_args, 'activate-network', false );

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

			if ( $activate_network ) {
				$install_assoc_args['activate-network'] = true;
				$command .= ' --activate-network';
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
	 * : The version to be installed. Accepted values: auto-update, hotfix, or beta. Default: hotfix.
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

		$slug = $this->get_slug( $args, true );
		$key  = isset( $assoc_args['key'] ) ? $assoc_args['key'] : $key = $this->get_key();

		if ( empty( $key ) ) {
			$key = GFCommon::get_key();
		} else {
			$this->save_key( $key );
			$key = GFCommon::get_key();
		}

		if ( empty( $key ) ) {
			WP_CLI::error( 'A valid license key must be saved in the settings or specified in the GF_LICENSE_KEY constant or the --key option.' );
		}

		if ( $slug === 'gravityforms' ) {
			$current_version = GFForms::$version;
		} else {
			$addon           = $this->get_addon( $slug );
			$current_version = $addon->get_version();
		}

		$version     = isset( $assoc_args['version'] ) ? $assoc_args['version'] : 'hotfix';
		$plugin_info = $version === 'beta' ? $this->get_beta_plugin_info( $slug, $key ) : $this->get_plugin_info( $slug, $key );

		if ( $version == 'hotfix' ) {
			$available_version = isset( $plugin_info['version_latest'] ) ? $plugin_info['version_latest'] : '';
			$download_url      = isset( $plugin_info['download_url_latest'] ) ? $plugin_info['download_url_latest'] : '';
		} else {
			$available_version = isset( $plugin_info['version'] ) ? $plugin_info['version'] : '';
			$download_url      = isset( $plugin_info['download_url'] ) ? $plugin_info['download_url'] : '';
		}

		if ( $plugin_info && ! empty( $download_url ) ) {

			if ( version_compare( $current_version, $available_version, '>=' ) ) {
				WP_CLI::success( 'Plugin already updated' );

				return;
			}

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

		$slug = $this->get_slug( $args );

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
			$addon = $this->get_addon( $slug );
			$addon->setup();
			WP_CLI::success( 'setup ' . $slug );
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
		$slug = $this->get_slug( $args );

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

		$gravity_manager_url = defined( 'GRAVITY_MANAGER_URL' ) && GRAVITY_MANAGER_URL ? GRAVITY_MANAGER_URL : 'https://gravityapi.com/wp-content/plugins/gravitymanager';

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

	/**
	 * Gets the plugin info for beta releases.
	 *
	 * @since 1.4
	 *
	 * @param string $slug The plugin or add-on slug.
	 * @param string $key  The license key.
	 *
	 * @return mixed
	 * @throws \WP_CLI\ExitException
	 */
	private function get_beta_plugin_info( $slug, $key = '' ) {
		if ( $slug !== 'gravityforms' ) {
			WP_CLI::error( '--version=beta is not currently supported by add-ons.' );
		}

		$beta_info    = $this->get_plugin_info( $slug . '-beta', $key );
		$beta_version = isset( $beta_info['version'] ) ? $beta_info['version'] : '';
		$no_beta_msg  = 'There is no beta release available at this time.';

		if ( empty( $beta_version ) ) {
			WP_CLI::error( $no_beta_msg );
		}

		$stable_info    = $this->get_plugin_info( $slug, $key );
		$stable_version = isset( $stable_info['version'] ) ? $stable_info['version'] : '';

		if ( $stable_version && version_compare( $stable_version, $beta_version, '>=' ) ) {
			WP_CLI::error( $no_beta_msg );
		}

		return $beta_info;
	}

	/**
	 * Gets the plugin slug for the current command.
	 *
	 * @since 1.4
	 *
	 * @param array $args       The command arguments.
	 * @param false $beta_check Should we check for the -beta suffix and display an error if found?
	 *
	 * @return string
	 * @throws \WP_CLI\ExitException
	 */
	private function get_slug( $args, $beta_check = false ) {
		if ( empty( $args[0] ) ) {
			return 'gravityforms';
		}

		$slug = $args[0];

		if ( $beta_check && strpos( $slug, '-beta' ) ) {
			WP_CLI::error( 'Appending -beta to the slug is not supported. Use --version=beta instead.' );
		}

		return $slug;
	}

	/**
	 * Returns the add-on instance for the given slug.
	 *
	 * @since 1.4
	 *
	 * @param string $slug The add-on slug.
	 *
	 * @return GFAddon
	 * @throws \WP_CLI\ExitException
	 */
	private function get_addon( $slug ) {
		$addon_class_names = GFAddOn::get_registered_addons();

		foreach ( $addon_class_names as $addon_class_name ) {
			/* @var GFAddon $addon */
			$addon = call_user_func( array( $addon_class_name, 'get_instance' ) );
			if ( $addon->get_slug() == $slug ) {

				return $addon;
			}
		}

		WP_CLI::error( 'Invalid slug or plugin not active: ' . $slug );
	}

}
