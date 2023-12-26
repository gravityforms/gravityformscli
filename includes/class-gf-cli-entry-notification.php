<?php

defined( 'ABSPATH' ) || defined( 'WP_CLI' ) || die();

/**
 * Send Gravity Forms Notifications.
 *
 * @since    1.0-beta-5
 * @package  GravityForms/CLI
 * @category CLI
 * @author   Rockegenius
 * @copyright Copyright (c) 2017-2018, Rocketgenius
 */
class GF_CLI_Entry_Notification extends WP_CLI_Command {

	/**
	 * Returns the notifications for the given entry.
	 *
	 * @since 1.0-beta-5
	 * @access public
	 *
	 * ## OPTIONS
	 *
	 * <entry-id>
	 * : The Entry ID
	 *
	 * [<notification-id>...]
	 * : Specific notification IDs to get. If set, this will override the "event" arg.
	 *
	 * [--event=<event>]
	 * : The event. Default: form_submission
	 *
	 * [--format=<format>]
	 * : Accepted values: table, json, ids
	 *
	 * [--raw]
	 * : Specifying raw will output the raw field IDs and values. Best used for passing input to the create command.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf entry notification get 1
	 *     wp gf entry notification get 1 --event=form_submission
	 *     wp gf entry notification get 1 --event=form_submission --format=ids
	 *
	 * @synopsis <entry-id> [<notification-id>...] [--event=<event>] [--format=<format>] [--raw]
	 */
	public function get( $args, $assoc_args ) {

		$entry_id = $args[0];
		unset( $args[0] );

		$entry = GFAPI::get_entry( $entry_id );

		if ( is_wp_error( $entry ) ) {
			WP_CLI::error( $entry->get_error_message() );
		}

		$form_id = $entry['form_id'];

		$form = GFAPI::get_form( $form_id );

		if ( empty( $form ) ) {
			WP_CLI::error( 'Form not found' );
		}

		$notification_ids = $args;
		if ( empty( $notification_ids ) ) {
			$event = isset( $assoc_args['event'] ) ? $assoc_args['event'] : 'form_submission';
			$notifications = GFCommon::get_notifications_to_send( $event, $form, $entry );
		} else {
			$notifications = $form['notifications'];
			foreach ( $notifications as $key => $notification ) {
				if ( ! in_array( $notification['id'], $notification_ids ) ) {
					unset( $notifications[ $key ] );
				}
			}
		}

		$notification_ids_out = array();

		$notifications_table = array();

		foreach ( $notifications as $key => $notification ) {

			if ( ! isset( $active_flag ) || ( isset( $active_flag ) && $notification['isActive'] == $active_flag ) ) {

				$active = isset( $notification['isActive'] ) ? $notification['isActive'] : true;

				$notification['active'] = $active ? 'yes' : 'no';

				$notification['event'] = isset( $notification['event'] ) ? $notification['event'] : '';

				$notification_ids_out[] = $notification['id'];

				$notifications_table[ $notification['id'] ] = $notification;

			} else {

				unset( $notifications[ $key ] );

			}
		}

		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';

		if ( $format == 'ids' ) {
			// Space separate the IDs
			echo implode( ' ', $notification_ids_out );
			return;
		}

		$raw = WP_CLI\Utils\get_flag_value( $assoc_args, 'raw', false );

		if ( $raw ) {
			WP_CLI::line( json_encode( $notifications ) );
			return;
		}

		// Define each of the columns displayed
		$fields = array(
			'id',
			'name',
			'subject',
			'active',
		);

		// Format and output the results
		WP_CLI\Utils\format_items( $format, $notifications_table, $fields );
	}

	/**
	 * Sends notifications for an entry.
	 *
	 * @since 1.0-beta-5
	 * @access public
	 *
	 * ## OPTIONS
	 *
	 * <entry-id>
	 * : The Entry ID
	 *
	 * [<notification-id>...]
	 * : Specific notification IDs to send. This setting will override the event arg.
	 *
	 * [--event=<event>]
	 * : The event to trigger. Default: form_submission
	 *
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf entry notification send 597
	 *     wp gf entry notification send 597 --event=form_submission
	 *     wp gf entry notification send 597 574ff8257d864 596e53f8104ed
	 *
	 * @synopsis <entry-id>... [<notification-id>...] [--event=<event>]
	 */
	public function send( $args, $assoc_args ) {

		$entry_id = $args[0];
		unset( $args[0] );

		$entry = GFAPI::get_entry( $entry_id );

		if ( is_wp_error( $entry ) ) {
			WP_CLI::error( $entry->get_error_message() );
		}

		$form_id = $entry['form_id'];

		$form = GFAPI::get_form( $form_id );

		if ( empty( $form ) ) {
			WP_CLI::error( 'Form not found' );
		}

		$event = isset( $assoc_args['event'] ) ? $assoc_args['event'] : 'form_submission';

		$notification_ids = $args;
		if ( empty( $notification_ids ) ) {
			GFAPI::send_notifications( $form, $entry, $event );
		} else {
			GFCommon::send_notifications( $notification_ids, $form, $entry, true );
		}

		WP_CLI::log( 'Notifications sent' );
	}

}
