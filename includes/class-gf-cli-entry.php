<?php

defined( 'ABSPATH' ) || defined( 'WP_CLI' ) || die();

/**
 * Manage Gravity Forms Entries.
 *
 * @since    1.0
 * @package  GravityForms/CLI
 * @category CLI
 * @author   Rockegenius
 * @copyright Copyright (c) 2016-2018, Rocketgenius
 */
class GF_CLI_Entry extends WP_CLI_Command {

	/**
	 * Returns a JSON representation of an entry.
	 *
	 * @since 1.0-beta-1
	 * @access public
	 *
	 * ## OPTIONS
	 *
	 * <entry-id>
	 * : The Entry ID
	 *
	 * [--format=<format>]
	 * : Accepted values: table, json
	 *
	 * [--raw]
	 * : Specifying raw will display the raw field IDs and values. Best used for passing input to the create command.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf entry get 1
	 *
	 * @synopsis <entry-id> [--format=<format>] [--raw]
	 */
	public function get( $args, $assoc_args ) {
		// Ensure an entry ID is set
		$entry_id = isset( $args[0] ) ? $args[0] : 0;
		// Gets the entry, based on the entry ID
		$entry    = GFAPI::get_entry( $entry_id );

		// If the entry is not found, throw an error
		if ( is_wp_error( $entry ) ) {
			WP_CLI::error( $entry->get_error_message() );
		}

		// Check if format is defined and set it.  If not, default to 'table'
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';
		// Check if raw flag is defined.  If not, default to false.
		$raw = WP_CLI\Utils\get_flag_value( $assoc_args, 'raw', false );

		// Gets the form that the entry is associated with.
		$form = GFAPI::get_form( $entry['form_id'] );

		// If raw flag is set
		if ( $raw ) {

			// If the format is set to 'json'
			if ( $format == 'json' ) {
				// Encode the entry data into JSON for output, and bail.
				WP_CLI::line( json_encode( $entry ) );
				return;
			}

			$rows = array();

			// Run through the entry object returned, and add entry data to the $rows[] array.
			foreach ( $entry as $field_id => $value ) {
				$rows[] = array( 'ID' => $field_id, 'Field' => $field_id, 'Value' => (string) $value );
			}

			// Format the rows into a table format for output, then bail.
			WP_CLI\Utils\format_items( 'table', $rows, array( 'ID', 'Field', 'Value' ) );
			return;

		}

		// Check if the format flag is set.
		if ( in_array( $format, array( 'table', 'json' ) ) ) {
			$rows = array();

			// Run through each of the fields within the form that contains the entry.
			foreach ( $form['fields'] as $field ) {
				$field_id = $field->id;
				// If the raw flag is set, just set the ID and value.
				if ( $raw ) {
					$label = $field_id;
					$value = rgar( $entry, $field_id );
				} else {
					// If the raw flag isn't set, make it pretty.
					$field = GFFormsModel::get_field( $form, $field_id );
					$label = GFFormsModel::get_label( $field );
					$value = $this->get_entry_value( $entry, $field_id, $form );
				}

				// Add the data gathered to the $rows array.
				$rows[] = array( 'ID' => $field_id, 'Field' => $label, 'Value' => (string) $value );
			}

			// Format the items as defined for output.
			WP_CLI\Utils\format_items( $format, $rows, array( 'ID', 'Field', 'Value' ) );
		} else {
			// Throw an error if the format is set incorrectly.
			WP_CLI::error( 'Invalid format' );
		}

	}

	/**
	 * Deletes an entry.
	 *
	 * @since 1.0-beta-1
	 * @access public
	 *
	 * ## OPTIONS
	 *
	 * <entry-id>...
	 * : One or more IDs of entries to delete
	 *
	 * [--force]
	 * : Skip the trash
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf entry delete 1
	 *     wp gf entry delete 1 3 7 10
	 *     wp gf entry delete 1 --force
	 *     wp gf entry delete $(wp gf entry list 1 --status=trash --format=ids ) --force
	 *
	 * @synopsis <entry-id>... [--force]
	 */
	public function delete( $args, $assoc_args ) {

		// Parse the array containing the entry IDs
		foreach ( $args as $entry_id ) {

			// Get the entry object
			$entry = GFAPI::get_entry( $entry_id );

			// If the entry can't be found, throw an error but keep going.
			if ( is_wp_error( $entry ) ) {
				WP_CLI::error( $entry->get_error_message(), false );
				continue;
			}

			// Get the status of the entry
			$current_status = $entry['status'];

			// If the force flag is set, set $force to true.  Otherwise, false.
			$force = $current_status === 'trash' ? true : WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );

			// Check if force is set.
			if ( $force ) {
				// Delete the entry
				$result = GFAPI::delete_entry( $entry_id );
				// Check for errors in deletion
				if ( is_wp_error( $result ) ) {
					// If deletion throws an error, throw another one
					WP_CLI::error( 'Error deleting entry ' . $entry_id, false );
				} else {
					// Hooray!  It worked!
					WP_CLI::success( 'Deleted entry ' . $entry_id );
				}
			} else {
				// Move the entry to trash
				$result = GFAPI::update_entry_property( $entry_id, 'status', 'trash' );
				if ( is_wp_error( $result ) ) {
					// If trashing throws an error, throw another one.
					WP_CLI::error( 'Error trashing entry ' . $entry_id, false );
				} else {
					// Hooray!  It worked!
					WP_CLI::success( 'Trashed entry ' . $entry_id );
				}
			}
		}
	}

	/**
	 * Creates a new entry from either a JSON string with the raw entry or from field-value pairs.
	 *
	 * @since 1.0-beta-1
	 * @access public
	 *
	 * ## OPTIONS
	 *
	 * [<entry-json>]
	 * : A JSON representation of the complete entry.
	 *
	 * [<form-id>]
	 * : The Form ID of the new entry.
	 *
	 * [--<field>=<value>]
	 * : Associative args for the new entry.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf entry create 1 --field_1=ABC --field_2=test@test.com --field_3=1234 --field_4=Hello --field_5.3=John --field_5.6=Doe
	 *     wp gf entry create $(wp gf entry get 3 --raw --format=json)
	 * @synopsis [<entry-json>] [<form-id>] [--<field>=<value>]
	 */
	public function create( $args, $assoc_args ) {
		// Check if first parameter is JSON
		if ( isset( $args[0] ) && ! is_numeric( $args[0] ) ) {
			// Decode the JSON
			$entry = json_decode( $args[0], ARRAY_A );
			// If the JSON can't be decoded, throw an error.
			if ( empty( $entry ) ) {
				WP_CLI::error( 'Invalid entry JSON' );
			}

			// Create an entry using the entry object
			$result = GFAPI::add_entry( $entry );

			if ( is_wp_error( $result ) ) {
				// If entry creation throws an error, throw another one.
				WP_CLI::error( $result->get_error_message() );
			} else {
				// Hooray!  It worked!
				WP_CLI::success( 'Entry created successfully. New Entry ID: ' . $result );
			}

			// That's all we need for JSON entries.  Bail.
			return;
		}

		// Assign the first parameter as the form ID
		$form_id = $args[0];
		// See if the form exists
		$form    = GFAPI::get_form( $form_id );
		// If getting the form by ID fails, throw an error.
		if ( empty( $form ) ) {
			WP_CLI::error( 'The Form ID must be specified' );
		}
		// Start creating the entry object
		$entry            = array();
		$entry['form_id'] = $form_id;

		// If there isn't a value, throw an error.
		if ( empty( $assoc_args ) ) {
			WP_CLI::error( 'Please specify at least one value --<field>=<value>' );
		}

		// The the columns for this entry.
		$entry_db_columns = GFFormsModel::get_lead_db_columns();
		// Set up the parameters to be inserted
		foreach ( $assoc_args as $field_id => $value ) {
			// If the parameter is just the field ID, fix it.
			if ( strpos( $field_id, 'field_' ) === 0 ) {
				$field_id = str_replace( 'field_', '', $field_id );
			} else {
				continue;
			}

			// If the entry exists, ignore the entry ID parameter
			if ( in_array( $field_id, $entry_db_columns ) ) {
				if ( $field_id == 'id' ) {
					WP_CLI::line( 'The Entry ID value will be ignored.' );
					continue;
				}
				$entry[ $field_id ] = $value;
			} else {
				// Check the fields set in the parameters
				$field = GFFormsModel::get_field( $form, $field_id );
				if ( empty( $field ) ) {
					// Throw an error if the field doesn't exist
					WP_CLI::error( 'Field not found: ' . $field_id, false );
					continue;
				}
				$entry[ $field_id ] = $value;
			}
		}

		// If source URL isn't set, make it this site URL
		if ( ! isset( $entry['source_url'] ) ) {
			$entry['source_url'] = site_url();
		}

		// Create the entry using GFAPI.
		$result = GFAPI::add_entry( $entry );

		if ( is_wp_error( $result ) ) {
			// If entry creation had an error, throw another error.
			WP_CLI::error( $result->get_error_message() );
		} else {
			// Hooray!  It worked!
			WP_CLI::success( 'Entry created successfully. Entry ID: ' . $result );
		}
	}

	/**
	 * Updates an entry.
	 *
	 * @since 1.0-beta-1
	 * @access public
	 *
	 * ## OPTIONS
	 *
	 * [<entry-id>]
	 * : The ID of the entry to update.
	 *
	 * [--entry-json=<entry-json>]
	 * : A JSON representation of the complete Entry.
	 *
	 * [--<field>=<value>]
	 * : Field ID-Value pairs to be updated. The field id must start with --field_
	 *  Example: --field_1="My Value"
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf entry update 1 --entry-json='{snip}'
	 *     wp gf entry update 2 --field_1="My Value" --field_2="Another value"
	 *     wp gf entry update 3 --field_3.3="Harry" --field_3.6="Potter"
	 *
	 * @synopsis [<entry-id>] [--entry-json=<entry-json>] [--<field>=<value>]
	 */
	public function update( $args, $assoc_args ) {
		// Check if the  entry-json flag is set
		if ( isset( $assoc_args['entry-json'] ) ) {
			// Decode the JSON string
			$entry = json_decode( $assoc_args['entry-json'], ARRAY_A );
			// If the JSON isn't valid, throw an error
			if ( empty( $entry ) ) {
				WP_CLI::error( 'Invalid entry JSON' );
			}
			// If the entry ID is in the arguments, set it.
			if ( isset( $args[0] ) ) {
				$entry['id'] = $args[0];
			}
			// Update the entry
			$result = GFAPI::update_entry( $entry );

			if ( is_wp_error( $result ) ) {
				// If there was an error updating the entry, throw another one.
				WP_CLI::error( $result->get_error_message() );
			} else {
				// Hooray! It worked!
				WP_CLI::success( 'Entry updated successfully' );
			}

			// Bail.  We don't need anything else.
			return;
		}

		// If an entry ID isn't set, throw an error.
		if ( ! isset( $args[0] ) ) {
			WP_CLI::error( 'Please specify an entry ID.' );
		}

		// Set the entry ID to update
		$entry_id = $args[0];

		// Get the current entry with the defined entry ID
		$entry = GFAPI::get_entry( $entry_id );
		// If the entry doesn't exist, throw an error.
		if ( is_wp_error( $entry ) ) {
			WP_CLI::error( $entry->get_error_message() );
		}

		// Get the form that matches the entry
		$form = GFAPI::get_form( $entry['form_id'] );

		// Set the base value for the number of fields updated
		$fields_updated = 0;

		// Get the entry columns
		$entry_db_columns = GFFormsModel::get_lead_db_columns();
		// Start setting the entry information based on the arguments passed.
		foreach ( $assoc_args as $field_id => $value ) {
			// If the parameter is just the field ID, fix it.
			if ( strpos( $field_id, 'field_' ) === 0 ) {
				$field_id = str_replace( 'field_', '', $field_id );
			} else {
				continue;
			}

			// If the value is already the same, skip it.
			if ( $entry[ $field_id ] == $value ) {
				WP_CLI::line( 'The value of field ' . $field_id . ' is already ' . $value . '. Skipping.' );
				continue;
			}
			// If the entry ID was passed, skip it
			if ( in_array( $field_id, $entry_db_columns ) ) {
				if ( $field_id == 'id' ) {
					WP_CLI::error( "Can't change the Entry ID, sorry.", false );
					continue;
				}
				// Update the entry property.
				$result = GFAPI::update_entry_property( $entry_id, $field_id, $value );
			} else {
				// Get the field passed in the argument
				$field = GFFormsModel::get_field( $form, $field_id );
				// If the field doesn't exist, throw an error.
				if ( empty( $field ) ) {
					WP_CLI::error( 'Field not found: ' . $field_id, false );
					continue;
				}
				// Update the field value
				$result = GFAPI::update_entry_field( $entry_id, $field_id, $value );
			}
			// If everything failed, throw an error.
			if ( empty( $result ) ) {
				WP_CLI::error( 'Field ' . $field_id, false );
			} else {
				// Increase the number of fields updated
				$fields_updated ++;
				// Get the previous entry value for this field
				$previous_value = rgblank( $entry[ $field_id ]) ? '[empty]' : $entry[ $field_id ];
				// Display the field that was updated.
				WP_CLI::line( 'Updated field ' . $field_id . ' from ' . $previous_value . ' to ' . $value );
			}
		}

		if ( $fields_updated > 0 ) {
			// Hooray!  It worked!
			WP_CLI::success( 'Field values updated: ' . $fields_updated );
		} else {
			// Nothing was updated.
			WP_CLI::line( 'No fields updated' );
		}
	}

	/**
	 * Exports entries.
	 *
	 * @since 1.0-beta-1
	 * @access public
	 *
	 * ## OPTIONS
	 *
	 * <form-id>
	 * : The ID of the form.
	 *
	 * [<filename>]
	 * : The filename. Defaults to <form title>-<date>.<format>
	 *
	 * [--format=<format>]
	 * : Acceptable values: csv, json. Default: csv.
	 * The JSON format contains the raw values and can be imported using the import command.
	 *
	 * [--start_date=<date>]
	 * : Acceptable values: Date format yy-mm-dd. Default: Empty.
	 * Return only entries submitted on or after the start date.
	 *
	 * [--end_date=<date>]
	 * : Acceptable values: Date format yy-mm-dd. Default: Current system date.
	 * Return only entries submitted until and including the end date.
	 *
	 * [--dir=<dir>]
	 * : The directory to write the file. Defaults to the current working directory.
	 *
	 * Examples
	 *      --filename=entries.json
	 *      --filename=entries.csv
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf entry export 1
	 *     wp gf entry export 1 --format=json
	 *     wp gf entry export 1 --format=csv --start_date="2018-01-01" --end_date="2018-03-25"
	 *
	 * @synopsis <form-id> [<filename>] [--dir=<dir>] [--format=<format>] [--start_date=<yyyy-mm-dd>] [--end_date=<yyyy-mm-dd>]
	 */
	public function export( $args, $assoc_args ) {
		// Check is the form ID was defined.
		if ( ! isset( $args[0] ) ) {
			WP_CLI::error( 'Please specify a form ID.' );
		}
		// Set the form ID from the first argument.
		$form_id = $args[0];

		// Get the form based on the form ID
		$form = GFAPI::get_form( $form_id );

		// If the form ID was not found, throw an error.
		if ( empty( $form ) ) {
			WP_CLI::error( 'The form ID does not exist' );
		}

		// Check the format of export file
		if ( isset( $args[1] ) ) {
			$filename = $args[1];
			// If the filename ends in .json, et the format as json.  Else, csv.
			$format = substr( $filename, -4 ) === 'json' ? 'json' : 'csv';
		} else {
			// Set the format as csv
			$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'csv';
			// Generate the filename
			$filename = sanitize_title_with_dashes( $form['title'] ) . '-' . gmdate( 'Y-m-d', GFCommon::get_local_timestamp( time() ) ) . '.' . $format;
		}

		// If the dir flag is set
		if ( isset( $assoc_args['dir'] ) ) {
			// Check if target directory is writable
			if ( ! is_writable( $assoc_args['dir'] ) ) {
				// If not writable, throw an error
				WP_CLI::error( 'Not writable: ' . $assoc_args['dir'] );
			}
			// Build the full file path
			$filename = $assoc_args['dir'] . DIRECTORY_SEPARATOR . $filename;
		} else {
			// Check if working directory is writable
			if ( ! is_writable( '.' ) ) {
				// Throw an error if working directory is not writable
				WP_CLI::error( 'The current working directory is not writable' );
			}
		}

		$search_criteria = array();

		// Check to see if start date and end date are set to add to search_criteria
		if ( isset ( $assoc_args['start_date'] ) ) {
			$search_criteria['start_date'] = $assoc_args['start_date'];
		}

		if ( isset ( $assoc_args['end_date'] ) ) {
			$search_criteria['end_date'] = $assoc_args['end_date'];
		}

		// Export the entries in the defined format
		if ( $format == 'json' ) {
			$this->export_entries_json( $form_id, $filename, $search_criteria );
		} else {
			$this->export_entries_csv( $form, $filename, $search_criteria );
		}
	}

	/**
	 * Imports entries.
	 *
	 * @since 1.0-beta-1
	 * @access public
	 *
	 * ## OPTIONS
	 *
	 * <form-id>
	 * : The Form ID
	 *
	 * <file>
	 * : The full path to the file.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf entry import 1 path/to/file.json
	 *
	 * @synopsis <form-id> <file>
	 */
	public function import( $args, $assoc_args ) {

		// Set the form ID
		$form_id = $args[0];

		// Set the filename
		$filename = $args[1];

		// Get the contents of the file
		$file_contents = file_get_contents( $filename );

		// Decode the JSON entry data to an array
		$entries = json_decode( $file_contents, ARRAY_A );

		// If the entries do not exist, bail.
		if ( empty( $entries ) ) {
			WP_CLI::error( 'Invalid collection of entries' );

			return;
		}

		// Add the entries
		$result = GFAPI::add_entries( $entries, $form_id );
		if ( is_wp_error( $result ) ) {
			// If the entries can't be added, throw an error.
			WP_CLI::error( $result->get_error_message() );
		} else {
			// Hooray!  It worked!
			WP_CLI::success( 'Entries added successfully: ' . count( $result ) );
		}
	}

	/**
	 * Displays a list of entries.
	 *
	 * @since 1.0-beta-1
	 * @access public
	 *
	 * ## OPTIONS
	 *
	 * <form-id>
	 * : The Form ID
	 *
	 * [--status=<status>]
	 * : The status of the entries. Default: active
	 * Accepted values: trash, active
	 *
	 * [--format=<format>]
	 * : Accepted values: table, csv, json, count, ids. Default: table.
	 *
	 * [--page_size=<page_size>]
	 * : The size of the page. Default: 20
	 *
	 * [--offset=<offset>]
	 * : Number of entries to offset when printing the entry list. Default: 0
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf entry list 1
	 *
	 * @synopsis <form-id> [--status=<status>] [--format=<format>] [--page_size=<page_size>] [--offset=<offset>]
	 * @subcommand list
	 */
	public function entry_list( $args, $assoc_args ) {

		// Get the form ID
		$form_id = $args[0];

		// Get the form, based on the form ID
		$form = GFAPI::get_form( $form_id );

		// If not found, throw an error
		if ( empty( $form ) ) {
			WP_CLI::error( 'Form not found' );
		}

		// Check the status flag, and default to active
		$status          = isset( $assoc_args['status'] ) ? $assoc_args['status'] : 'active';
		// Check the page size flag, and default to 20
		$page_size       = isset( $assoc_args['page_size'] ) ? $assoc_args['page_size'] : 20;
		// Set the offset, if defined.
		$offset          = isset( $assoc_args['offset'] ) ? $assoc_args['offset'] : 0;
		// Set up paging, based on the offset and page size.
		$paging          = array( 'offset' => $offset, 'page_size' => $page_size );
		// Default the count to zero, to count up later
		$total_count     = 0;
		// Set the search criteria, based on the status
		$search_criteria = array(
			'status' => $status,
		);

		// Get the format, if defined and default to table
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';

		// If the fomate is set to 'ids', get the entry IDs
		if ( $format == 'ids' ) {
			$entry_ids = GFAPI::get_entry_ids( $form_id, $search_criteria, array(), $paging, $total_count );
			echo implode( ' ', $entry_ids );

			return;
		}

		// Get the entries
		$entries = GFAPI::get_entries( $form_id, $search_criteria, array(), $paging, $total_count );

		// If the format is set to 'count', display the number of entries.
		if ( $format == 'count' ) {
			echo $total_count;

			return;
		}

		// Get the columns based on form ID
		$columns = RGFormsModel::get_grid_columns( $form_id, true );

		// If the ID column exists, create an entry ID column
		if ( ! isset( $columns['id'] ) ) {
			$id_column = array( 'label' => esc_html__( 'Entry Id', 'gravityforms' ), 'type' => 'id' );
			$columns   = array( 'id' => $id_column ) + $columns;
		}
		// Get field IDs from the columns
		$field_ids = array_keys( $columns );
		// Get the form based on the form ID
		$form      = GFAPI::get_form( $form_id );
		// Prep the rows array
		$rows      = array();

		// Get the field types
		$field_types = array_keys( GF_Fields::get_all() );

		// Run through the entries
		foreach ( $entries as $entry ) {
			$row = array();
			// Run through the fields
			foreach ( $field_ids as $field_id ) {

				// Get the entry value for the field
				$value = $this->get_entry_value( $entry, $field_id, $form);
				// Get the field label
				$label = $columns[ $field_id ]['label'];
				// Get the field type
				if ( in_array( $columns[ $field_id ]['type'], $field_types ) ) {
					$label = $field_id . ': ' . $label;
				}
				$row[ $label ] = $value;
			}
			// Assign the entries to a main array
			$rows[] = $row;
		}
		// Run through the fields
		$fields = array();
		foreach ( $columns as $key => $column ) {
			$label = $column['label'];
			// Get the field type
			if ( in_array( $column['type'], $field_types ) ) {
				$label = $key . ': ' . $label;
			}
			$fields[] = $label;
		}

		// Output the data
		WP_CLI\Utils\format_items( $format, $rows, $fields );
	}

	/**
	 * Launch system editor to edit the JSON representation of the Entry.
	 *
	 * @since 1.0-beta-1
	 * @access public
	 *
	 * ## OPTIONS
	 *
	 * <entry-id>
	 * : The ID of the entry to edit.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf entry edit 123
	 *
	 * @synopsis <entry-id>
	 */
	public function edit( $args, $assoc_args ) {
		// Get the entry-id argument
		$entry_id = $args[0];
		// Get the entry object based on the entry ID
		$entry    = GFAPI::get_entry( $entry_id );
		// If there's an error in getting the entry, throw an error
		if ( is_wp_error( $entry ) ) {
			WP_CLI::error( $entry->get_error_message() );
		}

		// Encode the entry data as JSON
		$form_json = json_encode( $entry, JSON_PRETTY_PRINT );
		// Open the editor
		$r         = $this->_edit( $form_json, "WP-CLI gf entry {$entry_id}" );
		// If no changes were made, throw a warning
		if ( $r === false ) {
			WP_CLI::warning( 'No change made to entry.', 'Aborted' );
		} else {
			// If changes were made, update
			$this->update( $args, array( 'entry-json' => $r ) );
		}
	}

	/**
	 * Duplicates an entry
	 *
	 * @since 1.0-beta-1
	 * @access public
	 *
	 * ## OPTIONS
	 *
	 * <entry-id>
	 * : The ID of the entry to edit.
	 *
	 * [--count=<count>]
	 * : The number of times to duplicate. Default: 1.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gf entry edit 123
	 *
	 * @synopsis <entry-id> [--count=<count>]
	 */
	public function duplicate( $args, $assoc_args ) {
		// Get the entry ID from the arguments
		$entry_id = $args[0];

		// Get the entry object based on the entry ID
		$entry = GFAPI::get_entry( $entry_id );

		// If there's an issue getting the entry object, throw an error
		if ( is_wp_error( $entry ) ) {
			WP_CLI::error( $entry->get_error_message() );
		}

		// If the count flag is set, use it
		$count = isset( $assoc_args['count'] ) ? $assoc_args['count'] : 1;

		// Display the progress bar
		$progress = WP_CLI\Utils\make_progress_bar( 'Duplicating entry', $count );

		// For each entry added, tick up the progress bar
		for ( $i = $count; $i > 0; $i -- ) {
			// Add the entry
			GFAPI::add_entry( $entry );
			// Move the progress bar
			$progress->tick();
		}
		// Finish up the prigress bar
		$progress->finish();

		// Display the success message
		WP_CLI::success( 'Entries created: ' . $count );
	}

	/**
	 * Launches the editor
	 *
	 * @since 1.0-beta-1
	 * @access protected
	 *
	 * @param string $content The content of the editor
	 * @param string $title The title of the editor
	 *
	 * @return mixed An instance of the editor
	 */
	protected function _edit( $content, $title ) {
		$output = \WP_CLI\Utils\launch_editor_for_input( $content, $title );

		return $output;
	}

	/**
	 * Gets the field value from an entry
	 *
	 * @since 1.0-beta-1
	 * @access private
	 *
	 * @param array $entry The entry object
	 * @param int $field_id The field ID
	 * @param array $form The form object
	 *
	 * @return string The field value
	 */
	private function get_entry_value( $entry, $field_id, $form ) {
		// Get the form ID from the form object
		$form_id = $form['id'];
		// Gets the field object
		$field = RGFormsModel::get_field( $form, $field_id );
		// Gets the value of the entry for a particular field
		$value = rgar( $entry, $field_id );
		// If the field type is a post category
		if ( ! empty( $field ) && $field->type == 'post_category' ) {
			// Get the category value
			$value = GFCommon::prepare_post_category_value( $value, $field, 'entry_list' );
		}

		// Filtering lead value
		$value = apply_filters( 'gform_get_field_value', $value, $entry, $field );

		// Get the input type of the field
		$input_type = $field instanceof GF_Field ? $field->get_input_type() : $field_id;

		// Check the input type
		switch ( $input_type ) {

			// If the input type is source_url, set it.
			case 'source_url' :
				$value = $entry['source_url'];
				break;

			// If the input type is a date, format it as so.
			case 'date_created' :
			case 'payment_date' :
				$value = GFCommon::format_date( $value, false );
				break;

			// If the input type is a payment amount, format it as currency.
			case 'payment_amount' :
				$value = GFCommon::to_money( $value, $entry['currency'] );
				break;

			// If the input type is created_by, get the user login name
			case 'created_by' :
				if ( ! empty( $value ) ) {
					$userdata = get_userdata( $value );
					if ( ! empty( $userdata ) ) {
						$value = $userdata->user_login;
					}
				}
				break;

			// If the input type doesn't match any of these, get the value.
			default:
				if ( $field !== null ) {
					$value = $field->get_value_export( $entry, $field_id, true, true );
				}
		}

		// Filter the field value before returning it
		$value = apply_filters( 'gform_entries_field_value', $value, $form_id, $field_id, $entry );
		return $value;
	}

	/**
	 * Exports the form entries as a CSV
	 *
	 * @since 1.0.4 Added the $search_criteria parameter. Current only supports start date and end date.
	 * @since 1.0-beta-1
	 *
	 * @param array  $form            The form object to export entries from
	 * @param string $filename        The name of the file to export to
	 * @param array  $search_criteria The search criteria
	 */
	private function export_entries_csv( $form, $filename, $search_criteria = array() ) {

		// Require export.php for the GFExport class
		require_once( GFCommon::get_base_path() . '/export.php' );

		// Add the default export fields to the form object
		$form = GFExport::add_default_export_fields( $form );
		// Create an empty array to add fields to
		$fields = array();
		if ( is_array( $form['fields'] ) ) {
			/* @var GF_Field $field */
			foreach ( $form['fields'] as $field ) {
				// Get all field inputs
				$inputs = $field->get_entry_inputs();
				// If there are multiple inputs for the field
				if ( is_array( $inputs ) ) {
					// Add each of the input IDs to the $fields array
					foreach ( $inputs as $input ) {
						$fields[] = $input['id'];
					}
					// Add the field ID to the $fields array
				} elseif ( ! $field->displayOnly ) {
					$fields[] = $field->id;
				}
			}
		}

		$_POST['export_field'] = $fields;

		$search_criteria['status'] = 'active';

		if ( isset( $search_criteria['start_date'] ) ) {
			$_POST['export_date_start'] = $search_criteria['start_date'];
		}

		if ( isset( $search_criteria['end_date'] ) ) {
			$_POST['export_date_end'] = $search_criteria['end_date'];
		}

		$export_id = wp_hash( uniqid( 'export', true ) );
		$export_id = sanitize_key( $export_id );

		$offset = 0;

		$entry_count = GFAPI::count_entries( $form['id'], $search_criteria );

		$progress = WP_CLI\Utils\make_progress_bar( sprintf( 'Exporting %d entries', $entry_count ), $entry_count );
		do {
			$status = GFExport::start_export( $form , $offset, $export_id );
			$offset = $status['offset'];
			$progress_limit = $offset == 0 ? $entry_count : $offset;
			for ( $i = 0; $i < $progress_limit; $i++ ) {
				$progress->tick();
			}
		} while ( $status['status'] == 'in_progress' );

		$progress->finish();
		// Move the file to $filename
		$export_folder = GFFormsModel::get_upload_root() . 'export/';
		$source_path          = $export_folder . sanitize_file_name( 'export-' . $export_id . '.csv' );
		rename( $source_path, $filename );

		// Display the success message
		WP_CLI::success( 'Entries exported to ' . $filename );
	}

	/**
	 * Exports the form entries as JSON
	 *
	 * @since  1.0-beta-1
	 * @access private
	 *
	 * @param int    $form_id         The form ID
	 * @param string $filename        The file to export the entries to
	 * @param array  $search_criteria The criteria to search through entries for
	 * @param array  $sorting         The entry data to sort by
	 */
	private function export_entries_json( $form_id, $filename, $search_criteria = array(), $sorting = array() ) {
		// Process 20 entries at a time
		$page_size = 20;
		// Don't offset
		$offset    = 0;

		// Get the number of entries
		$entry_count  = GFAPI::count_entries( $form_id, $search_criteria );
		// Create the progress bar
		$progress     = WP_CLI\Utils\make_progress_bar( 'Exporting entries', (int) $entry_count / $page_size );
		// Set the number of remaining entries
		$entries_left = $entry_count;
		$all_entries  = array();
		// Start getting the entries
		while ( $entries_left > 0 ) {
			$paging      = array(
				'offset'    => $offset,
				'page_size' => $page_size,
			);
			// Get the entries, 20 at a time
			$entries     = GFAPI::get_entries( $form_id, $search_criteria, $sorting, $paging );
			// Toss the entries into our array
			$all_entries = array_merge( $all_entries, $entries );
			// Bump the offset for the next run
			$offset += $page_size;
			// Drop the entries remaining by 20
			$entries_left -= $page_size;
			// Tick up the progress bar
			$progress->tick();
		}
		// Drop the results into the file as JSON
		file_put_contents( $filename, json_encode( $all_entries, JSON_PRETTY_PRINT ) );
		// Tell the progress bar to finish
		$progress->finish();
		// Display the success message
		WP_CLI::success( $entry_count . ' entries exported to ' . $filename );
	}
}
