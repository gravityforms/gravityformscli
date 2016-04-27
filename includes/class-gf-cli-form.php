<?php

/**
 * Manage Gravity Forms.
 *
 * @since    1.0
 * @package  GravityForms/CLI
 * @category CLI
 * @author   Rockegenius
 * @copyright Copyright (c) 2016, Rocketgenius
 */
class GF_CLI_Form extends WP_CLI_Command {
	/**
	 * Lists the forms with entry count and view counts.
	 *
	 * @since 1.0-beta-1
	 *
	 * ## OPTIONS
	 *
	 * [--active]
	 * :  List active forms. Default: true
	 *
	 * [--trash>]
	 * : List forms in the trash. Default: false
	 *
	 * [--sort_column=<sort_column>]
	 * : The column on which to sort the list.
	 *      id|title|date_created|is_active|is_trash
	 *
	 * [--sort_dir=<sort_dir>]
	 * : The direction to use when sorting. Accepts ASC or DESC.  Defaults to ASC.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, csv, json, count. Default: table.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf form list
	 *     wp gf form list --trash
	 *     wp gf form list --active
	 *     wp gf form list --no-active
	 *
	 * @synopsis [--active] [--trash] [--sort_column=<sort_column>] [--sort_dir=<sort_dir>] [--format=<format>]
	 * @alias list
	 */
	function form_list( $args, $assoc_args ) {

		// Check if the active flag is passed
		$is_active   = WP_CLI\Utils\get_flag_value( $assoc_args, 'active', null );
		// Check for the sort column.  If not passed, default to title
		$sort_column = isset( $assoc_args['sort_column'] ) ? $assoc_args['sort_column'] : 'title';
		// Check for the sorting direction.  If not set, use ascending
		$sort_dir    = isset( $assoc_args['sort_dir'] ) ? $assoc_args['sort_dir'] : 'ASC';
		// Check if the --trash flag is set.  Default to false
		$is_trash    = WP_CLI\Utils\get_flag_value( $assoc_args, 'trash', false );

		// Check if the format is passed.  Default to table
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';

		// Get all forms based on the parameters set
		$forms = GFFormsModel::get_forms( $is_active, $sort_column, $sort_dir, $is_trash );

		// If the format is set as 'ids'
		if ( $format == 'ids' ) {
			// Start our array
			$form_ids = array();
			// For each form found, add its ID to the array
			foreach ( $forms as $form ) {
				$form_ids[] = $form->id;
			}
			// Space separate the IDs
			echo implode( ' ', $form_ids );
			return;
		}

		// Encode the JSON into an array, then decode it
		$forms_array = json_decode( json_encode( $forms ), ARRAY_A );
		// Run through each of the forms
		foreach ( $forms_array as &$form ) {
			// Change the label
			$form['entry_count'] = $form['lead_count'];
		}
		// Define each of the columns displayed
		$fields = array(
			'id',
			'title',
			'date_created',
			'is_active',
			'entry_count',
			'view_count',
		);
		// Format and output the results
		WP_CLI\Utils\format_items( $format, $forms_array, $fields );
	}

	/**
	 * Exports forms to a Gravity Forms Form export file.
	 *
	 * @since 1.0-beta-1
	 *
	 * ## OPTIONS
	 *
	 * [<form-id>]
	 * : The ID of the form to export. Defaults to all forms.
	 *
	 * [--dir=<dir>]
	 * : The directory for the form to export. Defaults to the current working directory.
	 *
	 * [--porcelain]
	 * : Overrides the standard success message with just the export file path
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf export 1
	 *     wp gf export
	 *
	 * @synopsis [<form-id>] [--dir=<dir>] [--porcelain]
	 * @alias export
	 */
	function export( $args, $assoc_args ) {

		// Needs GFExport
		require_once( GFCommon::get_base_path() . '/export.php' );

		// If the form ID is passed, use it.  Otherwise, get all form IDs
		$form_ids = isset( $args['form-id'] ) ? array( $args['form-id'] ) : GFFormsModel::get_form_ids();

		// Get all form meta for our selected forms
		$forms = RGFormsModel::get_form_meta_by_id( $form_ids );

		// Prep for export
		$forms = GFExport::prepare_forms_for_export( $forms );

		// JSON encode it
		$forms_json = json_encode( $forms );

		// Set the filename of the export
		$filename = 'gravityforms-export-' . date( 'Y-m-d' ) . '.json';

		// If the export directory is set
		if ( isset( $assoc_args['dir'] ) ) {
			// If the directory isn't writable, throw an error
			if ( ! is_writable( $assoc_args['dir'] ) ) {
				WP_CLI::error( 'Not writable: ' . $assoc_args['dir'] );
			}
			// Set the path, based on the directory and file name
			$filename = $assoc_args['dir'] . DIRECTORY_SEPARATOR . $filename;
		} else {
			// If the directory isn't writable, throw an error
			if ( ! is_writable( '.' ) ) {
				WP_CLI::error( 'The current working directory is not writable' );
			}
		}

		// Write the export output to the file
		file_put_contents( $filename, $forms_json );

		// Check if porcelain is set.  If not, default to false.
		$porcelain = isset( $assoc_args['porcelain'] ) ? $assoc_args['porcelain'] : false;

		if ( $porcelain ) {
			// If porcelain is set, output the file name
			WP_CLI::line( $filename );
		} else {
			// If not, display the standard success message
			WP_CLI::success( 'Forms exported successfully to ' . $filename );
		}
	}

	/**
	 * Imports forms from a Gravity Forms Form export file.
	 *
	 * @since 1.0-beta-1
	 *
	 * ## OPTIONS
	 *
	 * <path_to_json_file>
	 * : The path to the JSON file with the form.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf import /path/to/forms.json
	 *
	 * @synopsis <path_to_json_file>
	 * @alias import
	 */
	function import( $args, $assoc_args ) {
		// Get the path to the import file
		list( $path ) = $args;

		// Needs GFExport
		require_once( GFCommon::get_base_path() . '/export.php' );

		// Import the forms
		$count = GFExport::import_file( $path );

		// Display the success message
		WP_CLI::success( 'Forms imported: ' . absint( $count ) );
	}

	/**
	 * Creates a new form.
	 *
	 * @since 1.0-beta-1
	 *
	 * ## OPTIONS
	 *
	 * <title>
	 * : The title of the new form.  Overrides form JSON values.
	 *
	 * [<description>]
	 * : The description form setting. Overrides form JSON values.
	 *
	 * [--form-json=<form-json>]
	 * : Optionally pass the new form details with JSON
	 *
	 * [--porcelain]
	 * : If used, outputs just the form ID instead of the standard success message
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf create "My New Form" "The description"
	 *
	 * @synopsis [<title>] [<description>] [--form-json=<form-json>] [--porcelain]
	 * @subcommand create
	 * @alias create-form
	 */
	function create( $args, $assoc_args ) {
		// Check if the form details are passed via JSON
		if ( isset( $assoc_args['form-json'] ) ) {
			// Set the form JSON
			$form_json = $assoc_args['form-json'];
			// Decode the JSON to an array
			$form      = json_decode( $form_json, ARRAY_A );
			// Check if the title had been set and override the JSON setting
			if ( isset( $args[0] ) ) {
				$form['title'] = $args[0];
			}
			// Check if the description has been set and override the JSON setting
			if ( isset( $args[1] ) ) {
				$form['description'] = $args[1];
			}
		} else {
			// Set the title based on the passed argument
			$title       = $args[0];
			// Set the description based on the passed argument
			$description = isset( $args[1] ) ? $args[1] : '';

			// Create the form object
			$form = array(
				'title'                => $title,
				'description'          => $description,
				'labelPlacement'       => 'top_label',
				'descriptionPlacement' => 'below',
				'button'               => array(
					'type'     => 'text',
					'text'     => esc_html__( 'Submit', 'gravityforms' ),
					'imageUrl' => '',
				),
				'fields'               => array(),
			);

			// Create the default notification
			if ( apply_filters( 'gform_default_notification', true ) ) {

				$default_notification = array(
					'id'      => uniqid(),
					'to'      => '{admin_email}',
					'name'    => __( 'Admin Notification', 'gravityforms' ),
					'event'   => 'form_submission',
					'toType'  => 'email',
					'subject' => __( 'New submission from', 'gravityforms' ) . ' {form_title}',
					'message' => '{all_fields}',
				);

				$notifications = array( $default_notification['id'] => $default_notification );

				// Store it in the form object
				$form['notifications'] = $notifications;
			}
		}

		// If confirmations aren't already passed (they shouldn't be)
		if ( ! isset( $form['confirmations'] ) ) {

			// Set the confirmation ID
			$confirmation_id = uniqid();

			// Initialize our empty array
			$confirmations = array();

			// Build the confirmation
			$confirmations[ $confirmation_id ] = array(
				'id'          => $confirmation_id,
				'name'        => __( 'Default Confirmation', 'gravityforms' ),
				'isDefault'   => true,
				'type'        => 'message',
				'message'     => __( 'Thanks for contacting us! We will get in touch with you shortly.', 'gravityforms' ),
				'url'         => '',
				'pageId'      => '',
				'queryString' => '',
			);

			// Add the confirmation to the form object
			$form['confirmations'] = $confirmations;
		}

		// Create the form using the created form object
		$form_id = GFAPI::add_form( $form );

		// If there's an error creating the form, throw an error
		if ( is_wp_error( $form_id ) ) {
			WP_CLI::error( $form_id->get_error_message() );
		}

		// Check if porcelain is set.  Default to false.
		$porcelain = isset( $assoc_args['porcelain'] ) ? $assoc_args['porcelain'] : false;

		if ( $porcelain ) {
			// If porcelain is set, only display the form ID
			WP_CLI::line( $form_id );
		} else {
			// Otherwise, set our success message
			WP_CLI::success( 'Created Form with ID: ' . $form_id );
		}
	}

	/**
	 * Returns the form JSON.
	 *
	 * @since 1.0-beta-1
	 *
	 * ## OPTIONS
	 *
	 * <form-id>
	 * : The Form ID
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf get 1
	 *
	 * @synopsis <form-id>
	 * @subcommand get
	 * @alias get-form
	 */
	function get( $args, $assoc_args ) {
		// Get the form ID passed
		$form_id = $args[0];

		// Get the form based on the form ID
		$form = GFAPI::get_form( $form_id );
		if ( empty( $form ) ) {
			// If not found, throw an error
			WP_CLI::error( 'Form not found' );
		} else {
			// Otherwise, output the form JSON
			WP_CLI::line( json_encode( $form ) );
		}
	}

	/**
	 * Deletes a form.
	 *
	 * @since 1.0-beta-1
	 *
	 * ## OPTIONS
	 *
	 * <form-id>...
	 * : One or more IDs of the forms to delete.
	 *
	 * [--force]
	 * : Skip the trash
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf delete 1
	 *
	 * @synopsis <form-id>... [--force]
	 */
	function delete( $args, $assoc_args ) {

		// Run through each of the passed form IDs
		foreach ( $args as $form_id ) {

			// Get the form, based on the ID
			$form = GFAPI::get_form( $form_id );
			// If the form is already in the trash or --force is passed, set force to true.  Otherwise, false.
			$force = $form['is_trash'] ? true : WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );

			// If the force flag is set to true
			if ( $force ) {
				// Delete the form and store the result
				$result = GFAPI::delete_form( $form_id );

				// If the result is an error, throw an error message
				if ( is_wp_error( $result ) ) {
					/* @var WP_Error $result */
					WP_CLI::error( $result->get_error_message(), false );
				} else {
					// If there isn't an error, display success message
					WP_CLI::success( 'Deleted form ' . $form_id );
				}
			} else {
				// If force is not set, move it to trash instead
				$success = GFFormsModel::trash_form( $form_id );
				if ( $success ) {
					// If there was an issue, throw an error
					WP_CLI::error( 'Error deleting form: ' . $form_id, false );
				} else {
					// If all went well, display the success message
					WP_CLI::success( 'Trashed form ' . $form_id );
				}
			}

		}
	}

	/**
	 * Duplicates a form.
	 *
	 * @since 1.0-beta-1
	 *
	 * ## OPTIONS
	 *
	 * <form-id>
	 * : The Form ID
	 *
	 * ## EXAMPLES
	 *
	 *     wp gravityforms duplicate 1
	 *
	 * @synopsis <form-id> [--porcelain]
	 * @subcommand duplicate
	 * @alias duplicate-form
	 */
	function duplicate( $args, $assoc_args ) {
		// Set the form ID that was passed
		$form_id = $args[0];

		// Override the user capabilities
		add_filter( 'user_has_cap', function ( $all_caps, $cap, $args ) {
			$all_caps['gform_full_access'] = true;

			return $all_caps;
		}, 9, 3 );

		// Duplicate the form, and store the new form ID
		$new_form_id = GFFormsModel::duplicate_form( $form_id );

		// If porcelain is set, use it.  Otherwise, set as false
		$porcelain = isset( $assoc_args['porcelain'] ) ? $assoc_args['porcelain'] : false;

		// If the porcelain flag is set, do stuff
		if ( $porcelain ) {
			// If set, only return the new form ID
			WP_CLI::line( $new_form_id );
		} else {
			// Otherwise, display the success message
			WP_CLI::success( 'Form duplicated successfully. New Form ID: ' . $new_form_id );
		}
	}

	/**
	 * Updates a form.
	 *
	 * @since 1.0-beta-1
	 *
	 * ## OPTIONS
	 *
	 * <form-id>
	 * : The Form ID
	 *
	 * --form-json=<form-json>
	 * : The JSON representation of the form
	 *
	 * ## EXAMPLES
	 *
	 *     wp gravityforms update 1 --form-json='{snip}'
	 *
	 * @synopsis <form-id> --form-json=<form-json>
	 */
	function update( $args, $assoc_args ) {
		// Set the form ID from the arguments passed
		$form_id     = $args[0];
		// Set the JSON data to be used for the update
		$json_config = $assoc_args['form-json'];
		// Decode the JSON
		$form        = json_decode( $json_config, ARRAY_A );

		// If the form data passed is empty, throw an error
		if ( empty( $form ) ) {
			WP_CLI::error( 'Form not valid' );
		}

		// Add the form ID to the form object
		$form['id'] = $form_id;

		// Pass the form object to update_form and store the result
		$result = GFAPI::update_form( $form );
		if ( is_wp_error( $result ) ) {
			// If there was an error, throw an error
			WP_CLI::error( $result );
		} else {
			// Otherwise, display the success message
			WP_CLI::success( 'Form updated successfully' );
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
	 * : The ID of the form to edit.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf form edit 123
	 */
	public function edit( $args, $assoc_args ) {
		// Set the form ID from the passed arguments
		$form_id = $args[0];
		// Get the form object based on the form ID
		$form = GFAPI::get_form( $form_id );
		// If nothing was returned, throw an error
		if ( empty( $form ) ) {
			WP_CLI::error( 'Form not found' );
		}

		// Encode the form object to JSON
		$form_json = json_encode( $form, JSON_PRETTY_PRINT );
		// Opern the editor, setting the content and title
		$r = $this->_edit( $form_json, "WP-CLI gf form {$form_id}" );
		if ( $r === false ) {
			// If no changes were made, throw a warning
			\WP_CLI::warning( 'No change made to form.', 'Aborted' );
		} else {
			// Otherwise, update the form using the edited content
			$this->update( $args, array( 'form-json' => $r ) );
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
}
