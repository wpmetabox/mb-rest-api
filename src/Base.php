<?php
namespace MetaBox\RestApi;

use ReflectionClass;
use WP_Error;
use RWMB_Field;

abstract class Base {
	const NAMESPACE = 'meta-box/v1';
	const KEY       = 'meta_box';
	protected $object_type;

	private $media_fields = [
		'media',
		'file',
		'file_upload',
		'file_advanced',
		'image',
		'image_upload',
		'image_advanced',
		'plupload_image',
		'thickbox_image',
	];

	private $no_value_fields = [
		'heading',
		'custom_html',
		'divider',
		'button',
	];

	public function __construct() {
		$this->object_type = strtolower( ( new ReflectionClass( $this ) )->getShortName() );
		add_action( 'rest_api_init', [ $this, 'init' ] );
	}

	public function init() {
		register_rest_field( $this->get_types(), self::KEY, [
			'get_callback'    => [ $this, 'get' ],
			'update_callback' => [ $this, 'update' ],
		] );
	}

	protected function get_types(): array {
		return [ $this->object_type ];
	}

	public function get( array $object ): array {
		return empty( $object['id'] ) ? [] : $this->get_values( $object['id'] );
	}

	/**
	 * Get all fields' values from list of meta boxes.
	 *
	 * @param int|string $object_id  Object ID.
	 * @param array      $fields     List of fields.
	 */
	protected function get_values( $object_id, array $fields = [] ): array {
		$fields = $fields ?: $this->get_fields( $object_id );

		$values = [];
		$args   = [ 'object_type' => $this->object_type ];
		foreach ( $fields as $field ) {
			$value = rwmb_get_value( $field['id'], $args, $object_id );
			$value = $this->normalize_value( $field, $value );

			$values[ $field['id'] ] = $value;
		}

		return $values;
	}

	protected function get_fields( $type_or_id ): array {
		$fields = rwmb_get_object_fields( $type_or_id, $this->object_type );

		// Remove fields with with hide_from_rest = true or has no values.
		return array_filter( $fields, function ( $field ) {
			return empty( $field['hide_from_rest'] ) && ! empty( $field['id'] ) && ! in_array( $field['type'], $this->no_value_fields, true );
		} );
	}

	private function normalize_value( array $field, $value ) {
		$value = $this->normalize_group_value( $field, $value );
		$value = $this->normalize_media_value( $field, $value );

		return $value;
	}

	private function normalize_group_value( array $field, $value ) {
		if ( 'group' !== $field['type'] ) {
			return $value;
		}
		if ( ! is_array( $value ) ) {
			$value = [];
		}

		unset( $value['_state'] );
		
		// If no sub-fields, return as-is
		if ( empty( $field['fields'] ) ) {
			return $value;
		}

		// For each group item (clone entries)
		foreach ( $value as $index => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			
			$normalized_item = [];
			
			foreach ( $field['fields'] as $subfield ) {
				if ( empty( $subfield['id'] ) ) {
					continue;
				}
				
				$subfield_id = $subfield['id'];
				
				// Try to get value: first with full ID, then with stripped prefix
				$subvalue = $this->get_subfield_value( $item, $subfield_id, $field );
				
				if ( $subvalue !== null ) {
					$subvalue = $this->normalize_value( $subfield, $subvalue );
					$normalized_item[ $subfield_id ] = $subvalue;
				}
			}
			
			$value[ $index ] = $normalized_item;
		}

		return $value;
	}

	/**
	 * Get sub-field value from item, trying both prefixed and non-prefixed keys.
	 *
	 * @param array  $item        The group item data.
	 * @param string $subfield_id The sub-field ID (with prefix).
	 * @param array  $field       The group field configuration.
	 * @return mixed|null The sub-field value or null if not found.
	 */
	private function get_subfield_value( array $item, string $subfield_id, array $field ) {
		// Try with full ID first
		if ( isset( $item[ $subfield_id ] ) ) {
			return $item[ $subfield_id ];
		}
		
		// Extract prefix from group field ID
		// Group field: mb_user_agb_akzeptiert
		// Sub-field: mb_user_agb_akzeptiert_datum
		// Prefix: mb_user_
		$group_id = $field['id'];
		
		// Find common prefix between group ID and subfield ID
		if ( str_starts_with( $subfield_id, $group_id . '_' ) ) {
			// Extract prefix from group_id
			// Find last underscore before the actual field name
			$parts = explode( '_', $group_id );
			
			// Try different prefix lengths
			for ( $i = count( $parts ); $i > 0; $i-- ) {
				$prefix = implode( '_', array_slice( $parts, 0, $i ) ) . '_';
				
				if ( str_starts_with( $subfield_id, $prefix ) ) {
					$short_id = substr( $subfield_id, strlen( $prefix ) );
					if ( isset( $item[ $short_id ] ) ) {
						return $item[ $short_id ];
					}
				}
			}
		}
		
		return null;
	}

	private function normalize_media_value( array $field, $value ) {
		// Make sure values of file/image fields are always indexed 0, 1, 2, ...
		return is_array( $value ) && in_array( $field['type'], $this->media_fields, true ) ? array_values( $value ) : $value;
	}

	protected function update_values( $data, $object_id, $object_subtype ) {
		$data = is_string( $data ) ? json_decode( $data, true ) : $data;

		// Store group fields with prefixed values for later restoration
		$group_fields_to_restore = [];

		foreach ( $data as $field_id => $value ) {
			$field = rwmb_get_registry( 'field' )->get( $field_id, $object_subtype, $this->object_type );
			$this->check_field_exists( $field_id, $field );
			
			// Store original prefixed value for group fields
			if ( 'group' === $field['type'] && is_array( $value ) ) {
				$prefixed_value = $this->add_prefix_to_group_value( $field, $value );
				$group_fields_to_restore[ $field_id ] = [
					'field' => $field,
					'value' => $prefixed_value
				];
			}
			
			$this->update_value( $field, $value, $object_id );
		}

		rwmb_request()->set_post_data( [ 'object_type' => $this->object_type ] );
		do_action( 'rwmb_after_save_post', $object_id );

		// After all hooks have run, restore prefixed values to custom table
		foreach ( $group_fields_to_restore as $field_id => $data ) {
			$field = $data['field'];
			$value = $data['value'];
			
			// Check if storage exists
			if ( empty( $field['storage'] ) ) {
				continue;
			}
			
			$storage = $field['storage'];
			
			// Update cache
			$storage->update( $object_id, $field['id'], $value );
			
			// Directly update database row (bypass cache flush)
			if ( method_exists( $storage, 'table' ) || isset( $storage->table ) ) {
				$table = $storage->table;
				
				// Get current row from cache
				$row = \MetaBox\CustomTable\Cache::get( $object_id, $table );
				
				// Serialize values
				$row = array_map( function( $item ) {
					return is_scalar( $item ) || is_null( $item ) ? $item : serialize( $item );
				}, $row );
				
				// Update database directly
				global $wpdb;
				if ( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE ID = %d", $object_id ) ) > 0 ) {
					$wpdb->update( $table, $row, [ 'ID' => $object_id ] );
				} else {
					$row['ID'] = $object_id;
					$wpdb->insert( $table, $row );
				}
			}
		}
	}

	protected function update_value( array $field, $value, $object_id ) {
		$old = RWMB_Field::call( $field, 'raw_meta', $object_id );

		$new = RWMB_Field::process_value( $value, $object_id, $field );
		$new = RWMB_Field::filter( 'rest_value', $new, $field, $old, $object_id );

		// Call defined method to save meta value, if there's no methods, call common one.
		RWMB_Field::call( $field, 'save', $new, $old, $object_id );
	}

	/**
	 * Add full prefixes to group sub-field keys.
	 * Converts: agb_akzeptiert_datum -> mb_user_agb_akzeptiert_datum
	 */
	private function add_prefix_to_group_value( array $field, $value ) {
		if ( empty( $field['fields'] ) || ! is_array( $value ) ) {
			return $value;
		}

		// Extract object type prefix (mb_user_, mb_post_, mb_term_, mb_)
		$object_prefix = $this->extract_object_prefix( $field['id'] );
		
		if ( ! $object_prefix ) {
			return $value;
		}

		// For each group item (clone entries)
		foreach ( $value as $index => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			
			$prefixed_item = [];
			
			foreach ( $item as $key => $subvalue ) {
				// Skip internal fields (starting with underscore)
				if ( str_starts_with( $key, '_' ) ) {
					$prefixed_item[ $key ] = $subvalue;
					continue;
				}
				
				// Add prefix to keys that don't already have it
				if ( ! str_starts_with( $key, $object_prefix ) ) {
					$prefixed_key = $object_prefix . $key;
					$prefixed_item[ $prefixed_key ] = $subvalue;
				} else {
					// Already has prefix, keep as-is
					$prefixed_item[ $key ] = $subvalue;
				}
			}
			
			$value[ $index ] = $prefixed_item;
		}

		return $value;
	}

	private function extract_object_prefix( $group_id ) {
		$common_prefixes = ['mb_user_', 'mb_post_', 'mb_term_', 'mb_'];
		
		foreach ( $common_prefixes as $prefix ) {
			if ( str_starts_with( $group_id, $prefix ) ) {
				return $prefix;
			}
		}
		
		return '';
	}

	private function check_field_exists( $field_id, $field ) {
		if ( $field ) {
			return;
		}

		// Translators: %s - Field ID.
		$this->send_error_message( 'field_not_exists', sprintf( __( "Field '%s' does not exists.", 'mb-rest-api' ), $field_id ) );
	}

	protected function send_error_message( $id, $message, $status_code = 400 ) {
		// Send an error, mimic how WordPress returns an error for a Rest request.
		status_header( $status_code );

		$error    = new WP_Error( $id, $message, [ 'status' => $status_code ] );
		$response = rest_convert_error_to_response( $error );

		echo wp_json_encode( $response->data );
		die;
	}
}
