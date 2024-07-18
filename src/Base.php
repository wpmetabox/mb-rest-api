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

		foreach ( $field['fields'] as $subfield ) {
			if ( empty( $subfield['id'] ) || empty( $value[ $subfield['id'] ] ) ) {
				continue;
			}
			$subvalue = $value[ $subfield['id'] ];
			$subvalue = $this->normalize_value( $subfield, $subvalue );

			$value[ $subfield['id'] ] = $subvalue;
		}

		return $value;
	}

	private function normalize_media_value( array $field, $value ) {
		// Make sure values of file/image fields are always indexed 0, 1, 2, ...
		return is_array( $value ) && in_array( $field['type'], $this->media_fields, true ) ? array_values( $value ) : $value;
	}

	protected function update_values( $data, $object_id, $object_subtype ) {
		$data = is_string( $data ) ? json_decode( $data, true ) : $data;

		foreach ( $data as $field_id => $value ) {
			$field = rwmb_get_registry( 'field' )->get( $field_id, $object_subtype, $this->object_type );
			$this->check_field_exists( $field_id, $field );
			$this->update_value( $field, $value, $object_id );
		}

		rwmb_request()->set_post_data( [ 'object_type' => $this->object_type ] );
		do_action( 'rwmb_after_save_post', $object_id );
	}

	protected function update_value( array $field, $value, $object_id ) {
		$old = RWMB_Field::call( $field, 'raw_meta', $object_id );

		$new = RWMB_Field::process_value( $value, $object_id, $field );
		$new = RWMB_Field::filter( 'rest_value', $new, $field, $old, $object_id );

		// Call defined method to save meta value, if there's no methods, call common one.
		RWMB_Field::call( $field, 'save', $new, $old, $object_id );
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
