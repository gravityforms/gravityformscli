<?php
/**
 * Misc Gravity Forms Tools.
 *
 * @since    1.0
 * @package  GravityForms/CLI
 * @category CLI
 * @author   Rockegenius
 * @copyright Copyright (c) 2016-2018, Rocketgenius
 */
class GF_CLI_Tool extends WP_CLI_Command {

	/**
	 * Clears the Gravity Forms transients.
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @alias clear-transients
	 */
	public function clear_transients( $args, $assoc_args ) {
		$result = GFCache::flush( true );
		if ( $result ) {
			WP_CLI::success( 'Gravity Forms transients cleared successfully.' );
		} else {
			WP_CLI::error( 'There was a problem clearing the Gravity Forms transients.' );
		}
	}

	/**
	 * Delete the trashed entries.
	 *
	 * @since 1.0-beta-1
	 * @access public
	 *
	 * [<form-id>]
	 * : The ID of the form. Default: all forms.
	 *
	 *
	 * @subcommand empty-trash
	 */
	public function empty_trash( $args, $assoc_args ) {
		$form_id = isset( $args[0] ) ? $args[0] : 0;
		if ( empty( $form_id ) ) {
			$form_ids = GFFormsModel::get_form_ids();
			foreach ( $form_ids as $id ) {
				GFFormsModel::delete_leads_by_form( $id, 'trash' );
			}
			WP_CLI::success( 'Trash emptied successfully for all forms.' );
		} else {
			GFFormsModel::delete_leads_by_form( $form_id, 'trash' );
			WP_CLI::success( 'Trash emptied successfully for form ID ' . $form_id );
		}
	}

	/**
	 * Verify Gravity Forms files against the checksums.
	 *
	 * Specify version to verify checksums.
	 *
	 * @since 1.0-beta-1
	 * @access public
	 *
	 * [--version=<version>]
	 * : Verify checksums against a specific version of Gravity Forms.
	 *
	 * @subcommand verify-checksums
	 */
	public function verify_checksums( $args, $assoc_args ) {
		if ( ! empty( $assoc_args['version'] ) ) {
			$gf_version = $assoc_args['version'];
		} else {
			$gf_version = GFForms::$version;
		}
		$checksums = $this->get_checksums( $gf_version );
		if ( ! is_array( $checksums ) ) {
			WP_CLI::error( "Couldn't get download checksums." );
		}

		$has_errors = false;
		foreach ( $checksums as $checksum_string ) {
			$checksum = substr( $checksum_string, 0, 32 );
			$file     = str_replace( $checksum . '  ', '', $checksum_string );
			$path     = GFCommon::get_base_path() . DIRECTORY_SEPARATOR . $file;
			if ( ! file_exists( $path ) ) {
				WP_CLI::warning( "File doesn't exist: {$file}" );
				$has_errors = true;
				continue;
			}
			$md5_file = md5_file( $path );
			if ( $md5_file !== $checksum ) {
				WP_CLI::warning( "File doesn't verify against checksum: {$file}" );
				$has_errors = true;
			}
		}
		if ( ! $has_errors ) {
			WP_CLI::success( 'Gravity Forms install verifies against checksums.' );
		} else {
			WP_CLI::error( 'Gravity Forms install doesn\'t verify against checksums.' );
		}
	}

	/**
	 * Gets the checksums for the given version of Gravity Forms.
	 *
	 * @since 1.0-beta-1
	 * @access public
	 *
	 * @param string $version Version string to query.
	 *
	 * @return bool|array False on failure. An array of checksums on success.
	 */
	private function get_checksums( $version ) {
		$url      = 'https://s3.amazonaws.com/gravityforms/releases/checksums/gravityforms_' . $version . '.md5';
		$options  = array(
			'timeout' => 30,
		);
		$headers  = array(
			'Accept' => 'text/plain',
		);
		$response = \WP_CLI\Utils\http_request( 'GET', $url, null, $headers, $options );
		if ( ! $response->success || 200 != $response->status_code ) {
			return false;
		}
		$body      = trim( $response->body );
		$checksums = explode( "\n", $body );

		return $checksums;
	}

	/**
	 * Outputs the system report from the Forms > System Status page.
	 *
	 * @since 1.0.3
	 *
	 * @alias      status
	 * @subcommand system-report
	 */
	public function system_report() {
		if ( gf_cli()->is_gravityforms_supported( '2.2' ) ) {
			require_once( GFCommon::get_base_path() . '/includes/system-status/class-gf-system-report.php' );
			$sections           = GF_System_Report::get_system_report();
			$system_report_text = GF_System_Report::get_system_report_text( $sections );
			WP_CLI::success( $system_report_text );
		} else {
			WP_CLI::error( 'The system report is only available with Gravity Forms 2.2 or greater.' );
		}
	}

}
