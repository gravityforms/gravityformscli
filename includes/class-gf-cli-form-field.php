<?php

/**
 * Manage Gravity Forms Form Fields.
 *
 * @since    1.0
 * @package  GravityForms/CLI
 * @category CLI
 * @author   Rockegenius
 * @copyright Copyright (c) 2016, Rocketgenius
 */
class GF_CLI_Form_Field extends WP_CLI_Command {

	/**
	 * Creates a field and adds it to a form.
	 *
	 * @since 1.0-beta-1
	 * @access public
	 *
	 * ## OPTIONS
	 *
	 * <form-id>
	 * : The Form ID
	 *
	 * <type>
	 * : The field type. Examples: text, textarea, email
	 *
	 * [<label>]
	 * : The field label. Defaults to 'Untitled'
	 *
	 * [--field-json=<field-json>]
	 * : A JSON string.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf field create 1 text 'My Label'
	 *     wp gf field create 1 --field-json=<field_json>
	 *
	 * @synopsis <form-id> [<type>] [<label>] [--field-json=<field-json>]
	 */
	public function create( $args, $assoc_args ) {

		// Define the form ID based on the first argument
		$form_id = $args[0];

		// Get the form based on the form ID
		$form = GFAPI::get_form( $form_id );
		// If the form can't be found, throw an error
		if ( empty( $form ) ) {
			WP_CLI::error( 'Form not found' );
		}

		// If the JSON flag is set, assign the content to $json
		$json = isset( $assoc_args['field-json'] ) ? $assoc_args['field-json'] : '';

		$field = array();
		// Get the JSON data and decode it, if using JSON
		if ( ! empty( $json ) ) {
			$field = json_decode( $json, ARRAY_A );
		}

		// Assign the second argument as the field type
		if ( isset( $args[1] ) ) {
			$field['type'] = $args[1];
		}

		// Assign the third argument as the field label
		if ( isset( $args[2] ) ) {
			$field['label'] = $args[2];
		}

		// If the field type was not passed, throw an error
		if ( empty( $field['type'] ) ) {
			WP_CLI::error( 'Field type not specified' );
		}

		// If the field label was not passed, default to Untitled
		if ( ! isset( $field['label'] ) ) {
			$field['label'] = __( 'Untitled', 'gravityforms' );
		}

		// If the form does not have any fields, set the field ID to 1
		if ( empty( $form['fields'] ) ) {
			$field_id = 1;
		} else {
			// Run through each field until we find the highest field ID
			$max_field_id = 1;
			foreach ( $form['fields'] as $existing_field ) {
				if ( $existing_field->id > $max_field_id ) {
					$max_field_id = $existing_field->id;
				}
			}
			// Add 1 to the highest field ID
			$field_id = $max_field_id + 1;
		}

		// Set the field ID
		$field['id'] = $field_id;

		// Create the field
		$field = GF_Fields::create( $field );

		// Add the field to the form
		$form['fields'][] = $field;
		GFAPI::update_form( $form );

		// If the porcelain flag is set, just spit out the field ID
		$porcelain = isset( $assoc_args['porcelain'] ) ? $assoc_args['porcelain'] : false;
		if ( $porcelain ) {
			WP_CLI::line( $field_id );
		} else {
			// Otherwise, send the success message
			WP_CLI::success( 'Field ID: ' . $field_id );
		}
	}

	/**
	 * Returns the JSON representation of a field.
	 *
	 * @since 1.0-beta-1
	 * @access public
	 *
	 * ## OPTIONS
	 *
	 * <form-id>
	 * : The Form ID
	 *
	 * <field-id>
	 * : The Field ID
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf field get 1 3
	 *
	 * @synopsis <form-id> <field-id>
	 */
	public function get( $args, $assoc_args ) {

		// Set the first argument as the form ID
		$form_id = $args[0];

		// Get the form based on the form ID
		$form = GFAPI::get_form( $form_id );
		// If there was an error getting the form, throw an error
		if ( empty( $form ) ) {
			WP_CLI::error( 'Form not found' );
		}

		// If the 2rd argument (field ID) isn't set, throw an error
		if ( ! isset( $args[1] ) ) {
			WP_CLI::error( 'Please specify the field ID' );
		}

		// Set the first argument as the field ID
		$field_id = $args[1];

		// If the field ID id set as 1 or less (or not numeric), throw an error
		if ( $field_id <= 1 ) {
			WP_CLI::error( 'Field ID not valid' );
		}
		// Get the field based on the field and form ID
		$field = GFFormsModel::get_field( $form, $field_id );
		// If unable to get the field data, throw an error
		if ( empty( $field ) ) {
			WP_CLI::error( 'Field not found' );
		}
		// If everything worked out alright, encode the data as JSON and output it
		WP_CLI::line( json_encode( $field ) );
	}

	/**
	 * Displays a list of fields for a form.
	 *
	 * @since 1.0-beta-1
	 * @access public
	 *
	 * ## OPTIONS
	 *
	 * <form-id>
	 * : The Form ID
	 *
	 * [--format=<output-format>]
	 * : The format that the form will be output in.  Defaults to table.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf list 1
	 *
	 * @synopsis <form-id> [--format=<output-format>]
	 * @subcommand list
	 */
	public function field_list( $args, $assoc_args ) {
		// Set the first argument as the form ID
		$form_id = $args[0];

		// Get the form based on the form ID
		$form = GFAPI::get_form( $form_id );
		// If the form can't be gotten, throw an error
		if ( empty( $form ) ) {
			WP_CLI::error( 'Form not found' );
		}

		// Check if the format is defined.  If not, default to table.
		$format        = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';
		// Ger the fields from the form object.
		$fields        = $form['fields'];
		// Define field headers (columns)
		$field_headers = array(
			'id',
			'type',
			'label',
		);
		// Format the items and output them
		WP_CLI\Utils\format_items( $format, $fields, $field_headers );
	}

	/**
	 * Updates a field.  If it doesn't exist, creates it.
	 *
	 * @since 1.0-beta-1
	 * @access public
	 *
	 * ## OPTIONS
	 *
	 * <form-id>
	 * : The form ID
	 *
	 * <field-id>
	 * : The field ID
	 *
	 * [--<field-property>=<field-value>]
	 * : The field properties to update
	 *
	 * [--field-json=<field-json>]
	 * : JSON representation of the field data.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf update 1 2 --type='text' --label='My Field'
	 *
	 * @synopsis <form-id> <field-id> [--<field-property>=<field-value>] [--field-json=<field-json>]
	 */
	public function update( $args, $assoc_args ) {

		// Get the form ID
		$form_id = $args[0];

		// Get the form using the form ID
		$form = GFAPI::get_form( $form_id );
		if ( empty( $form ) ) {
			// If the form doesn't exist, throw an error
			WP_CLI::error( 'Form not found' );
		}

		// If the field ID isn't set, throw an error
		if ( ! isset( $args[1] ) ) {
			WP_CLI::error( 'Please specify the field ID' );
		}

		// Get the field ID
		$field_id = $args[1];

		// Get the field based on the field ID
		$field = GFFormsModel::get_field( $form, $field_id );

		// If the field doesn't exist, throw an error
		if ( empty( $field ) ) {
			WP_CLI::error( 'Field not found' );
		}

		// If the field JSON doesn't exist, assign an empty string
		$json = isset( $assoc_args['field-json'] ) ? $assoc_args['field-json'] : '';

		// If the JSON is set
		if ( ! empty( $json ) ) {
			// Decode the field JSON
			$field = json_decode( $json, ARRAY_A );

			// If the field type isn't set, throw an error
			if ( empty( $field['type'] ) ) {
				WP_CLI::error( 'Field type not specified in the field json' );
			}

			// Make sure it's the right ID.
			$field['id'] = $field_id;

			// If the label isn't set, set it to Untitled
			if ( ! isset( $field['label'] ) ) {
				$field['label'] = __( 'Untitled', 'gravityforms' );
			}
		} else {
			// Get the property/value for the update data
			foreach ( $assoc_args as $field_property => $value ) {
				// If the field property is the form ID, skip it
				if ( $field_property == 'form_id' ) {
					continue;
				}
				// Set the field property and value into the array
				$field[ $field_property ] = $value;
			}
		}

		// Default to false
		$field_updated = false;

		// Run through each of the form fields
		foreach ( $form['fields'] as $key => $existing_field ) {
			// If the field ID matches, update
			if ( (string) $existing_field->id == (string) $field_id ) {
				// Create the field
				$field                  = GF_Fields::create( $field );
				// Set the field information
				$form['fields'][ $key ] = $field;
				// Set updated as true
				$field_updated          = true;
				// Jump out of the loop
				break;
			}
		}

		// If the field was updated properly, update the form with the new data
		if ( $field_updated ) {
			GFAPI::update_form( $form );
		}

		if ( $field_updated ) {
			// If updated, throw a success message
			WP_CLI::success( 'Field ID: ' . $field_id . ' updated' );
		} else {
			// If failed, throw an error
			WP_CLI::error( 'Field ID: ' . $field_id . ' not found' );
		}
	}

	/**
	 * Deletes a field.
	 *
	 * @since 1.0-beta-1
	 * @access public
	 *
	 * ## OPTIONS
	 *
	 * <form-id>
	 * : The Form ID
	 *
	 * <field-id>
	 * : The field ID
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf field 1 2
	 *
	 * @synopsis <form-id> <field-id>
	 */
	public function delete( $args, $assoc_args ) {

		// Get the form ID from the arguments passed
		$form_id = $args[0];

		// Gets the form based on the ID passed
		$form = GFAPI::get_form( $form_id );
		// If there was an issue getting the form, throw an error
		if ( empty( $form ) ) {
			WP_CLI::error( 'Form not found' );
		}

		// Get the field ID that was passed
		$field_id = $args[1];

		// Run through each field in the form
		foreach ( $form['fields'] as $key => $field ) {
			// If the field ID matches, pull it from the array
			if ( $field->id == $field_id ) {
				unset( $form['fields'][ $key ] );
			}
		}
		// Prep the fields to be sent
		$form['fields'] = array_values( $form['fields'] );
		// Update the form
		GFAPI::update_form( $form );
		// If everything was cool, display the success message
		WP_CLI::success( 'Field ID: ' . $field_id . ' deleted' );
	}

	/**
	 * Duplicates a field.
	 *
	 * @since 1.0-beta-1
	 * @access public
	 *
	 * ## OPTIONS
	 *
	 * <form-id>
	 * : The Form ID
	 *
	 * <field-id>
	 * : The ID of the field to duplicate.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf field 1 2
	 *
	 * @synopsis <form-id> <field-id>
	 */
	public function duplicate( $args, $assoc_args ) {

		// Get the form ID
		$form_id = $args[0];

		// Get the form based on the form ID passed
		$form = GFAPI::get_form( $form_id );
		// If there's an issue, throw an error
		if ( empty( $form ) ) {
			WP_CLI::error( 'Form not found' );
		}

		// Get the field ID
		$field_id = $args[1];

		// Set the max field ID to count later
		$max_field_id = 0;

		// Set the field position to count later
		$duplicated_field_position = 0;

		// Run through each of the fields
		foreach ( $form['fields'] as $index => $field ) {
			// If the field ID is the one passed, do stuff
			if ( $field->id == $field_id ) {
				// Create the field
				$duplicated_field = GF_Fields::create( $field );
				// Set the position as the location of the field
				$duplicated_field_position = $index;
			}
			// If field ID is larger than the max field ID (almost always) set it.
			if ( $field->id > $max_field_id ) {
				$max_field_id = $field->id;
			}
		}

		// Increment the new field ID by 1
		$new_id = $max_field_id + 1;

		// If there are multiple inputs on the duplicated field, handle them.
		if ( is_array( $duplicated_field->inputs ) ) {
			foreach ( $duplicated_field->inputs as $input ) {
				$input['id'] = str_replace( $duplicated_field->id . '.', $new_id . '.', $input['id'] );
			}
		}
		// Set the duplicated field ID
		$duplicated_field->id = $new_id;

		// Only get the duplicated field
		array_splice( $form['fields'], $duplicated_field_position + 1, 0, array( $duplicated_field ) );

		// Process the duplication
		GFAPI::update_form( $form );
		// Display a success message
		WP_CLI::success( 'Field ID: ' . $field_id . ' duplicated' );
	}

	/**
	 * Launch system editor to edit the Field configuration.
	 *
	 * @since 1.0-beta-1
	 * @access public
	 *
	 * ## OPTIONS
	 *
	 * <form-id>
	 * : The ID of the form.
	 *
	 * <field_id>
	 * : The ID of the field to edit.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf field edit 1 12
	 */
	public function edit( $args, $assoc_args ) {
		// Get the passed form ID
		$form_id = $args[0];

		// Get the form based on the form ID
		$form = GFAPI::get_form( $form_id );
		// If there's an issue getting for the them, throw an error
		if ( empty( $form ) ) {
			WP_CLI::error( 'Form not found' );
		}

		// Get the passed field ID
		$field_id = $args[1];

		// Get the field to be edited
		$field = GFFormsModel::get_field( $form, $field_id );

		// If the field doesn't exist, throw an error
		if ( empty( $field ) ) {
			WP_CLI::error( 'Field not found' );
		}

		// Decode the field meta
		$field_json = json_encode( $field, JSON_PRETTY_PRINT );
		// Start up the editor
		$r = $this->_edit( $field_json, "WP-CLI gf field {$form_id} {$field_id}" );
		// If no changes are detected, throw a warning
		if ( $r === false ) {
			\WP_CLI::warning( 'No change made to field.', 'Aborted' );
		} else {
			// Yay!  It worked!
			$this->update( $args, array( 'field-json' => $r ) );
		}
	}

	/**
	 * Launches the system editor
	 *
	 * @since 1.0-beta-1
	 * @access protected
	 *
	 * @param string $content The content to be placed in the editor
	 * @param string $title The title displayed in the editor
	 *
	 * @return mixed The edited content, if edited.  If no changes are made, false.
	 */
	protected function _edit( $content, $title ) {
		$output = \WP_CLI\Utils\launch_editor_for_input( $content, $title );
		return $output;
	}
}
