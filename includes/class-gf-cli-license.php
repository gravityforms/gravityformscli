<?php

/**
 * Manage the Gravity Forms License Key.
 *
 * @since    1.0
 * @package  GravityForms/CLI
 * @category CLI
 * @author   Rockegenius
 * @copyright Copyright (c) 2017-2018, Rocketgenius
 */
class GF_CLI_License extends WP_CLI_Command {

	/**
	 * Updates the license key.
	 *
	 * @since 1.0
	 *
	 * ## OPTIONS
	 *
	 * <license-key>
	 * : The new license key
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf license update xxxxxx
	 *
	 * @synopsis <license-key>
	 */
	function update( $args, $assoc_args ) {

		$key = $args[0];

		$this->save_key( $key );

		WP_CLI::log( 'License key updated' );
	}

	/**
	 * Deletes the license key.
	 *
	 * @since 1.0
	 *
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf license delete
	 */
	function delete( $args, $assoc_args ) {

		$this->save_key( '' );

		WP_CLI::log( 'License key deleted' );
	}

	/**
	 * Saves or deletes the license key from the database.
	 *
	 * @param string $key The license key to be saved.
	 */
	private function save_key( $key ) {
		$current_key = get_option( 'rg_gforms_key' );
		if ( empty( $key ) ) {
			delete_option( 'rg_gforms_key' );
		} else if ( $current_key != $key ) {
			$key = trim( $key );
			update_option( 'rg_gforms_key', md5( $key ) );
		}

		$this->maybe_update_caches();
	}

	/**
	 * Refreshes cached data which could become invalid after changing the license key.
	 *
	 * @since 1.0.3
	 */
	private function maybe_update_caches() {
		if ( class_exists( 'GFCommon' ) ) {
			// Update the contents of the gform_version_info option.
			GFCommon::get_version_info( false );
			// Update the contents of the rg_gforms_message option.
			GFCommon::cache_remote_message();
		}
	}
}
