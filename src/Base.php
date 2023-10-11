<?php
namespace MetaBox\RestApi;

use WP_Error;
use RWMB_Field;

abstract class Base {
	const NAMESPACE = 'meta-box/v1';
	const KEY       = 'meta_box';

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
		add_action( 'rest_api_init', [ $this, 'init' ] );
	}

	public function init() {
		register_rest_field( 'user', 'meta_box', [
			'get_callback'    => [ $this, 'get_user_meta' ],
			'update_callback' => [ $this, 'update_user_meta' ],
		] );
		register_rest_field( 'comment', 'meta_box', [
			'get_callback'    => [ $this, 'get_comment_meta' ],
			'update_callback' => [ $this, 'update_comment_meta' ],
		] );

		$taxonomies = $this->get_types( 'taxonomy' );
		if ( in_array( 'post_tag', $taxonomies, true ) ) {
			$post_tag_key                = array_search( 'post_tag', $taxonomies, true );
			$taxonomies[ $post_tag_key ] = 'tag';
		}
		register_rest_field( $taxonomies, 'meta_box', [
			'get_callback'    => [ $this, 'get_term_meta' ],
			'update_callback' => [ $this, 'update_term_meta' ],
		] );
	}

	/**
	 * Get term meta for the rest API.
	 *
	 * @param array $object Term object.
	 *
	 * @return array
	 */
	public function get_term_meta( $object ) {
		$term_id = $object['id'];
		$term    = get_term( $term_id );
		if ( is_wp_error( $term ) || ! $term ) {
			return [];
		}

		$meta_boxes = rwmb_get_registry( 'meta_box' )->get_by( [ 'object_type' => 'term' ] );
		$meta_boxes = array_filter( $meta_boxes, function ( $meta_box ) use ( $term ) {
			return in_array( $term->taxonomy, $meta_box->taxonomies, true );
		} );

		return $this->get_values( $meta_boxes, $term_id, [ 'object_type' => 'term' ] );
	}

	/**
	 * Update term meta for the rest API.
	 *
	 * @param string|array $data   Term meta values in either JSON or array format.
	 * @param object       $object Term object.
	 */
	public function update_term_meta( $data, $object ) {
		$this->update_values( $data, $object->term_id, $object->taxonomy, 'term' );
	}

	/**
	 * Get user meta for the rest API.
	 *
	 * @param array $object User object.
	 *
	 * @return array
	 */
	public function get_user_meta( $object ) {
		$meta_boxes = rwmb_get_registry( 'meta_box' )->get_by( [ 'object_type' => 'user' ] );

		// Ignore MB User Profile meta boxes.
		$meta_boxes = array_filter( $meta_boxes, function ( $meta_box ) {
			return ! in_array( $meta_box->id, [
				'rwmb-user-register',
				'rwmb-user-login',
				'rwmb-user-lost-password',
				'rwmb-user-reset-password',
				'rwmb-user-info',
			], true );
		} );

		return $this->get_values( $meta_boxes, $object['id'], [ 'object_type' => 'user' ] );
	}

	/**
	 * Update user meta for the rest API.
	 *
	 * @param string|array $data   User meta values in either JSON or array format.
	 * @param object       $object User object.
	 */
	public function update_user_meta( $data, $object ) {
		$this->update_values( $data, $object->ID, 'user', 'user' );
	}

	/**
	 * Get comment meta for the rest API.
	 *
	 * @param array $object Comment object.
	 *
	 * @return array
	 */
	public function get_comment_meta( $object ) {
		$meta_boxes = rwmb_get_registry( 'meta_box' )->get_by( [ 'object_type' => 'comment' ] );
		return $this->get_values( $meta_boxes, $object['id'], [ 'object_type' => 'comment' ] );
	}

	/**
	 * Update comment meta for the rest API.
	 *
	 * @param string|array $data   Comment meta values in either JSON or array format.
	 * @param object       $object Comment object.
	 */
	public function update_comment_meta( $data, $object ) {
		$this->update_values( $data, $object->comment_ID, 'comment', 'comment' );
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

	/**
	 * Get all fields' values from list of meta boxes.
	 *
	 * @param array $meta_boxes Array of meta box object.
	 *
	 * @param int   $object_id  Object ID.
	 * @param array $args       Additional params for helper function.
	 *
	 * @return array
	 */
	protected function get_values( $meta_boxes, $object_id, $args = [] ) {
		$fields = [];
		foreach ( $meta_boxes as $meta_box ) {
			$fields = array_merge( $fields, $meta_box->fields );
		}

		// Remove fields with no values.
		$fields = array_filter( $fields, function ( $field ) {
			return ! empty( $field['id'] ) && ! in_array( $field['type'], $this->no_value_fields, true );
		} );

		// Remove fields with hide_from_rest = true.
		$fields = array_filter( $fields, function ( $field ) {
			return empty( $field['hide_from_rest'] );
		} );

		$values = [];
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

	private function check_field_exists( $field_id, $field ) {
		if ( $field ) {
			return;
		}

		$this->send_error_message(
			'field_not_exists',
			// Translators: %s - Field ID.
			sprintf( __( 'Field "%s" does not exists', 'mb-rest-api' ), $field_id )
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
