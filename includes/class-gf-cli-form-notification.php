<?php

/**
 * Manage Gravity Forms Notifications.
 *
 * @since    1.0-beta-5
 * @package  GravityForms/CLI
 * @category CLI
 * @author   Rockegenius
 * @copyright Copyright (c) 2017-2018, Rocketgenius
 */
class GF_CLI_Form_Notification extends WP_CLI_Command {
	/**
	 * Lists the notifications form a form.
	 *
	 * @since 1.0-beta-5
	 *
	 * ## OPTIONS
	 *
	 * <form-id>
	 * : The ID of the form.
	 *
	 * [--active]
	 * :  List only active notifications.
	 *
	 *
	 * [--format=<format>]
	 * : Accepted values: table, csv, json, count. Default: table.
	 *
	 * [--raw]
	 * : Specifying raw will output the raw json with all the properties. Best used for passing input to the create command.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf form notification list 1
	 *     wp gf form notification list 1 --active
	 *     wp gf form notification list 1 --no-active
	 *
	 * @synopsis <form-id> [--active] [--format=<format>] [--raw]
	 * @alias list
	 */
	function notification_list( $args, $assoc_args ) {

		$form_id = $args[0];

		// Check if the active flag is passed
		$active_flag = WP_CLI\Utils\get_flag_value( $assoc_args, 'active', null );

		// Check if the format is passed.  Default to table
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';

		$form = GFAPI::get_form( $form_id );
		if ( empty( $form ) ) {
			WP_CLI::error( 'Form not found' );
		}

		if ( ! $form ) {
			WP_CLI::error( 'Form not found with ID ' . $form_id );
		}

		$notifications = $form['notifications'];

		$notification_ids    = array();

		$notifications_table = array();

		foreach ( $notifications as $key => $notification ) {

			if ( ! isset( $active_flag ) || ( isset( $active_flag ) && $notification['isActive'] == $active_flag ) ) {

				$active = isset( $notification['isActive'] ) ? $notification['isActive'] : true;

				$notification['active'] = $active ? 'yes' : 'no';

				$notification['event'] = isset( $notification['event'] ) ? $notification['event'] : '';

				$notification_ids[] = $notification['id'];

				$notifications_table[ $notification['id'] ] = $notification;

			} else {

				unset( $notifications[ $key ] );

			}
		}

		if ( $format == 'ids' ) {
			// Space separate the IDs
			echo implode( ' ', $notification_ids );
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
	 * Creates a new notification.
	 *
	 * @since 1.0-beta-5
	 *
	 * ## OPTIONS
	 *
	 * <form-id>
	 * : The ID of the form.
	 *
	 * [<name>]
	 * : The name of the notification.
	 *
	 * <--to=<to>->
	 * : The to field. Default: {admin_email}
	 *
	 * <--subject=<subject>>
	 * : The subject field. Default: New submission from {form_title}
	 *
	 * <--message=<message>>
	 * : The message body. Default: {all_fields}
	 *
	 * [--to-type=<to-type>]
	 * : The event for the notification. Default: email
	 *
	 * [--event=<event>]
	 * : The event for the notification. Default: form_submission
	 *
	 * [--notification-json=<notification-json>]
	 * : Optionally pass the new form details with JSON
	 *
	 * [--porcelain]
	 * : If used, outputs just the form ID instead of the standard success message
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf form notification create 1 "My Notification"
	 *     wp gf form notification create 1 "My Notification" --to="admin@mysite.com"
	 *
	 * @synopsis [<title>] [<description>] [--form-json=<form-json>] [--porcelain]
	 */
	function create( $args, $assoc_args ) {

		$form_id = $args[0];

		$form = GFAPI::get_form( $form_id );
		if ( empty( $form ) ) {
			WP_CLI::error( 'Form not found' );
		}

		$notifications = $form['notifications'];

		// Check if the form details are passed via JSON
		if ( isset( $assoc_args['notification-json'] ) ) {
			// Set the notification JSON
			$notification_json = $assoc_args['notification-json'];
			// Decode the JSON to an array
			$notification = json_decode( $notification_json, ARRAY_A );
			// Check if the name had been set and override the JSON setting
			if ( isset( $args[1] ) ) {
				$notification['name'] = $args[0];
			}

			if ( ! isset( $notification['id'] ) || ( isset( $notification['id'] ) && isset( $notifications[ $notification['id'] ] ) ) ) {
				$notification['id'] = uniqid();
			}
		} else {
			// Set the name based on the passed argument
			$name = isset( $args[1] ) ? $args[1] : __( 'Admin Notification', 'gravityforms' );

			$to = isset( $assoc_args['to'] ) ? $assoc_args['to'] : '{admin_email}';

			$subject = isset( $assoc_args['subject'] ) ? $assoc_args['subject'] : 'New submission from {form_title}';

			$message = isset( $assoc_args['message'] ) ? $assoc_args['message'] : '{all_fields}';

			$to_type = isset( $assoc_args['toType'] ) ? $assoc_args['to-type'] : 'email';

			$event = isset( $assoc_args['event'] ) ? $assoc_args['event'] : 'form_submission';

			// Create the form object
			$notification = array(
				'id'      => uniqid(),
				'name'    => $name,
				'to'      => $to,
				'subject' => $subject,
				'message' => $message,
				'toType'  => $to_type,
				'event'   => $event,
			);
		}

		$notifications[ $notification['id'] ] = $notification;

		// Create the form using the created form object
		$result = GFFormsModel::update_form_meta( $form_id, $notifications, 'notifications' );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		// Check if porcelain is set.  Default to false.
		$porcelain = isset( $assoc_args['porcelain'] ) ? $assoc_args['porcelain'] : false;

		if ( $porcelain ) {
			// If porcelain is set, only display the notification ID
			WP_CLI::line( $notification['id'] );
		} else {
			// Otherwise, set our success message
			WP_CLI::success( 'Created Notification with ID: ' . $notification['id'] );
		}
	}

	/**
	 * Returns the notification JSON.
	 *
	 * @since 1.0-beta-5
	 *
	 * ## OPTIONS
	 *
	 * <form-id>
	 * : The Form ID
	 *
	 * <notification-id>
	 * : The Notification ID
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf form notification get 1 596e4794a13a2
	 *
	 * @synopsis <form-id> <notification-id>
	 */
	function get( $args, $assoc_args ) {
		// Get the form ID passed
		$form_id = $args[0];

		$form = GFAPI::get_form( $form_id );
		if ( empty( $form ) ) {
			WP_CLI::error( 'Form not found' );
		}

		// Get the notification ID passed
		$notification_id = $args[1];

		$get_notification =  $this->get_notification( $form, $notification_id );

		if ( empty( $get_notification ) ) {
			WP_CLI::error( 'Notification not found' );
		}

		WP_CLI::line( json_encode( $get_notification ) );
	}

	/**
	 * Deletes a notification.
	 *
	 * @since 1.0-beta-5
	 *
	 * ## OPTIONS
	 *
	 * <form-id>...
	 * : The form ID.
	 *
	 * <notification-id>...
	 * : The IDs of the notification to delete.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf form delete 1 596e4794a13a2
	 *     wp gf form delete 1 596e4794a13a2 574ff8257d864
	 *
	 * @synopsis <form-id> <notification-id>...
	 */
	function delete( $args, $assoc_args ) {

		$form_id = $args[0];

		unset( $args[0] );

		$form = GFAPI::get_form( $form_id );
		if ( empty( $form ) ) {
			WP_CLI::error( 'Form not found' );
		}

		$notifications = $form['notifications'];

		// Run through each of the passed form IDs
		$deleted = 0;
		foreach ( $args as $notification_id ) {
			foreach ( $notifications as $key => $notification ) {
				if ( $notification['id'] == $notification_id ) {
					unset( $notifications[ $key ] );
					$deleted++;
				}
			}
		}

		// Create the form using the created form object
		$result = GFFormsModel::update_form_meta( $form_id, $notifications, 'notifications' );

		// If there's an error creating the form, throw an error
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		WP_CLI::success( 'Deleted notifications: ' . $deleted );
	}

	/**
	 * Duplicates a notification.
	 *
	 * @since 1.0-beta-5
	 *
	 * ## OPTIONS
	 *
	 * <form-id>
	 * : The Form ID
	 *
	 * <notification-id>
	 * : The ID of the notification to duplicate.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf form notification duplicate 1
	 *
	 * @synopsis <form-id> <notification-id> [--porcelain]
	 */
	function duplicate( $args, $assoc_args ) {
		// Set the form ID that was passed
		$form_id = $args[0];

		$form = GFAPI::get_form( $form_id );
		if ( empty( $form ) ) {
			WP_CLI::error( 'Form not found' );
		}

		// Get the notification ID passed
		$notification_id = $args[1];

		$duplicate_notification = $this->get_notification( $form, $notification_id );

		if ( empty( $duplicate_notification ) ) {
			WP_CLI::error( 'Notification not found' );
		}

		$new_notification_id = uniqid();

		$duplicate_notification['id'] = $new_notification_id;

		$notifications[ $new_notification_id ] = $duplicate_notification;

		// Create the form using the created form object
		$result = GFFormsModel::update_form_meta( $form_id, $notifications, 'notifications' );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		$porcelain = isset( $assoc_args['porcelain'] ) ? $assoc_args['porcelain'] : false;

		if ( $porcelain ) {
			// If set, only return the new form ID
			WP_CLI::line( $new_notification_id );
		} else {
			// Otherwise, display the success message
			WP_CLI::success( 'Notification duplicated successfully. New Notification ID: ' . $new_notification_id );
		}
	}

	/**
	 * Updates a Notification JSON.
	 *
	 * @since 1.0-beta-5
	 *
	 * ## OPTIONS
	 *
	 * <form-id>
	 * : The Form ID
	 *
	 * --notification-json=<notification-json>
	 * : The JSON representation of the form
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf form notification update 1 --notification-json='{snip}'
	 *
	 * @synopsis <form-id> --notification-json=<notification-json>
	 */
	function update( $args, $assoc_args ) {
		$form_id = $args[0];

		$notification_id = $args[1];

		$form = GFAPI::get_form( $form_id );
		if ( empty( $form ) ) {
			WP_CLI::error( 'Form not found' );
		}

		$notifications = $form['notifications'];

		$json_config = $assoc_args['notification-json'];

		$new_notification = json_decode( $json_config, ARRAY_A );

		if ( empty( $new_notification ) ) {
			WP_CLI::error( 'Notification not valid' );
		}

		$found = false;
		foreach ( $notifications as $key => $notification ) {
			if ( $notification['id'] == $notification_id ) {
				$notifications[ $key ] = $new_notification;
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			WP_CLI::error( 'Notification not found' );
		}

		// Create the form using the created form object
		$result = GFFormsModel::update_form_meta( $form_id, $notifications, 'notifications' );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		} else {
			WP_CLI::success( 'Notification updated successfully' );
		}
	}

	/**
	 * Launch system editor to edit the Form configuration.
	 *
	 * @since 1.0-beta-1
	 *
	 * ## OPTIONS
	 *
	 * <form-id>
	 * : The ID of the form.
	 *
	 * <notification-id>
	 * : The ID of the notification to edit.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf form notification edit 123
	 *
	 * @synopsis <form-id> <notification-id>
	 */
	public function edit( $args, $assoc_args ) {
		// Set the form ID from the passed arguments
		$form_id = $args[0];
		$form = GFAPI::get_form( $form_id );
		if ( empty( $form ) ) {
			WP_CLI::error( 'Form not found' );
		}

		$notification_id = $args[1];

		$notification = $this->get_notification( $form, $notification_id );

		$notification_json = json_encode( $notification, JSON_PRETTY_PRINT );
		// Open the editor, setting the content and title
		$r = $this->_edit( $notification_json, "WP-CLI gf form notification {$form_id} {$notification_id}" );

		if ( $r === false ) {
			// If no changes were made, throw a warning
			\WP_CLI::warning( 'No change made to notification.', 'Aborted' );
		} else {
			// Otherwise, update the notification using the edited content
			$this->update( $args, array( 'notification-json' => $r ) );
		}
	}

	/**
	 * Launches the editor, setting the content and title
	 * 
	 * @since 1.0-beta-1
	 * @access protected
	 *
	 * @param string $content
	 * @param string $title
	 *
	 * @return mixed
	 */
	protected function _edit( $content, $title ) {
		$output = \WP_CLI\Utils\launch_editor_for_input( $content, $title );
		return $output;
	}

	private function get_notification( $form, $notification_id ) {

		$notifications = $form['notifications'];

		$get_notification = isset( $notifications[ $notification_id ] ) ?  $notifications[ $notification_id ] : null;

		if ( empty( $get_notification ) ) {
			// Try looping through the array
			foreach ( $notifications as $notification ) {
				if ( $notification['id'] == $notification_id ) {
					$get_notification = $notification;
				}
			}
		}
		return $get_notification;
	}
}
