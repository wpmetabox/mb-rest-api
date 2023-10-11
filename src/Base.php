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

	public function get( array $response_data ): array {
		return $this->get_values( $response_data['id'] );
	}

	protected function get_types(): array {
		return [ $this->object_type ];
	}

	protected function get_fields( $type_or_id ): array {
		$fields = rwmb_get_object_fields( $type_or_id, $this->object_type );

		// Remove fields with no values.
		$fields = array_filter( $fields, function ( $field ) {
			return ! empty( $field['id'] ) && ! in_array( $field['type'], $this->no_value_fields, true );
		} );

		// Remove fields with hide_from_rest = true.
		$fields = array_filter( $fields, function ( $field ) {
			return empty( $field['hide_from_rest'] );
		} );

		return $fields;
	}

	/**
	 * Get all fields' values from list of meta boxes.
	 *
	 * @param int|string $object_id  Object ID.
	 * @param array      $fields     List of fields.
	 */
	protected function get_values( $object_id, $fields = [] ): array {
		$fields = $fields ?: $this->get_fields( $object_id );

		$values = [];
		$args   = [
			'object_type' => $this->object_type,
		];
		foreach ( $fields as $field ) {
			$value = rwmb_get_value( $field['id'], $args, $object_id );
			$value = $this->normalize_value( $field, $value );

			$values[ $field['id'] ] = $value;
		}

		return $values;
	}

	/**
	 * Normalize value.
	 *
	 * @param  array $field Field settings.
	 * @param  mixed $value Field value.
	 * @return mixed
	 */
	private function normalize_value( $field, $value ) {
		$value = $this->normalize_group_value( $field, $value );
		$value = $this->normalize_media_value( $field, $value );

		return $value;
	}

	/**
	 * Normalize group value.
	 *
	 * @param  array $field Field settings.
	 * @param  mixed $value Field value.
	 * @return mixed
	 */
	private function normalize_group_value( $field, $value ) {
		if ( 'group' !== $field['type'] ) {
			return $value;
		}
		if ( isset( $value['_state'] ) ) {
			unset( $value['_state'] );
		}

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

	/**
	 * Normalize media value.
	 *
	 * @param  array $field Field settings.
	 * @param  mixed $value Field value.
	 * @return mixed
	 */
	private function normalize_media_value( $field, $value ) {
		/*
		 * Make sure values of file/image fields are always indexed 0, 1, 2, ...
		 * @link https://github.com/wpmetabox/mb-rest-api/commit/31aa8fa445c188e8a71ebff80027acbcaa0fd268
		 */
		if ( is_array( $value ) && in_array( $field['type'], $this->media_fields, true ) ) {
			$value = array_values( $value );
		}

		return $value;
	}

	protected function update_values( $data, $object_id, $object_subtype, $object_type ) {
		$data = is_string( $data ) ? json_decode( $data, true ) : $data;

		foreach ( $data as $field_id => $value ) {
			$field = rwmb_get_registry( 'field' )->get( $field_id, $object_subtype, $object_type );
			$this->check_field_exists( $field_id, $field );
			$this->update_value( $field, $value, $object_id );
		}

		rwmb_request()->set_post_data( [ 'object_type' => $object_type ] );
		do_action( 'rwmb_after_save_post', $object_id );
	}

	/**
	 * Update field value.
	 *
	 * @param array $field     Field data.
	 * @param mixed $value     Field value.
	 * @param int   $object_id Object ID.
	 */
	protected function update_value( $field, $value, $object_id ) {
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

		$this->send_error_message(
			'field_not_exists',
			// Translators: %s - Field ID.
			sprintf( __( "Field '%s' does not exists.", 'mb-rest-api' ), $field_id )
		);
	}

	protected function send_error_message( $id, $message, $status_code = 500 ) {
		// Send an error, mimic how WordPress returns an error for a Rest request.
		status_header( $status_code );

		$error    = new WP_Error(
			$id,
			$message,
			[ 'status' => $status_code ]
		);
		$response = rest_convert_error_to_response( $error );

		echo wp_json_encode( $response->data );
		die;
	}
}
