<?php
/**
 * The REST API main class.
 *
 * @package    Meta Box
 * @subpackage MB Rest API
 */

/**
 * Meta Box Rest API class.
 */
class MB_Rest_API {
	/**
	 * Register new field 'meta_box' for all meta box's fields.
	 */
	public function init() {
		register_rest_field( $this->get_types(), 'meta_box', array(
			'get_callback'    => array( $this, 'get_post_meta' ),
			'update_callback' => array( $this, 'update_post_meta' ),
		) );
		register_rest_field( $this->get_types( 'taxonomy' ), 'meta_box', array(
			'get_callback' => array( $this, 'get_term_meta' ),
		) );
	}

	/**
	 * Get post meta for the rest API.
	 *
	 * @param array $object Post object.
	 *
	 * @return array
	 */
	public function get_post_meta( $object ) {
		$output     = array();
		$meta_boxes = rwmb_get_registry( 'meta_box' )->all();
		foreach ( $meta_boxes as $meta_box ) {
			if ( ! in_array( $object['type'], $meta_box->post_types, true ) ) {
				continue;
			}
			foreach ( $meta_box->fields as $field ) {
				if ( empty( $field['id'] ) ) {
					continue;
				}
				$field_value = rwmb_get_value( $field['id'] );

				/*
				 * Make sure values of file/image fields are always indexed 0, 1, 2, ...
				 * @link https://github.com/malfborger/mb-rest-api/commit/31aa8fa445c188e8a71ebff80027acbcaa0fd268
				 */
				if ( is_array( $field_value ) && in_array( $field['type'], array( 'media', 'file', 'file_upload', 'file_advanced', 'image', 'image_upload', 'image_advanced', 'plupload_image', 'thickbox_image' ), true ) ) {
					$field_value = array_values( $field_value );
				}
				$output[ $field['id'] ] = $field_value;
			}
		}

		return $output;
	}

	/**
	 * Update post meta for the rest API.
	 *
	 * @param string|array $data   Post meta values in either JSON or array format.
	 * @param object       $object Post object.
	 */
	public function update_post_meta( $data, $object ) {
		if ( is_string( $data ) ) {
			$data = json_decode( $data, true );
			if ( JSON_ERROR_NONE === json_last_error() ) {
				return;
			}
		}

		foreach ( $data as $field_id => $value ) {
			$field = rwmb_get_registry( 'field' )->get( $field_id, $object->post_type );
			$old   = RWMB_Field::call( $field, 'raw_meta', $object->ID );
			$new   = $value;

			// Allow field class change the value.
			if ( $field['clone'] ) {
				$new = RWMB_Clone::value( $new, $old, $object->ID, $field );
			} else {
				$new = RWMB_Field::call( $field, 'value', $new, $old, $object->ID );
				$new = RWMB_Field::filter( 'sanitize', $new, $field );
			}
			$new = RWMB_Field::filter( 'value', $new, $field, $old );
			$new = RWMB_Field::filter( 'rest_value', $new, $field, $old, $object->ID );

			// Call defined method to save meta value, if there's no methods, call common one.
			RWMB_Field::call( $field, 'save', $new, $old, $object->ID );
		}
	}

	/**
	 * Get term meta for the rest API.
	 *
	 * @param array $object Term object.
	 *
	 * @return array
	 */
	public function get_term_meta( $object ) {
		$output = array();
		if ( ! class_exists( 'MB_Term_Meta_Box' ) ) {
			return $output;
		}

		$meta_boxes = MB_Term_Meta_Loader::$meta_boxes;

		foreach ( $meta_boxes as $meta_box ) {
			if ( ! in_array( $object['taxonomy'], (array) $meta_box['taxonomies'], true ) ) {
				continue;
			}
			$fields = RW_Meta_Box::normalize_fields( $meta_box['fields'] );
			foreach ( $fields as $field ) {
				if ( empty( $field['id'] ) ) {
					continue;
				}
				$single      = $field['clone'] || ! $field['multiple'];
				$field_value = get_term_meta( $object['id'], $field['id'], $single );

				/*
				 * Make sure values of file/image fields are always indexed 0, 1, 2, ...
				 * @link https://github.com/malfborger/mb-rest-api/commit/31aa8fa445c188e8a71ebff80027acbcaa0fd268
				 */
				if ( is_array( $field_value ) && in_array( $field['type'], array( 'media', 'file', 'file_upload', 'file_advanced', 'image', 'image_upload', 'image_advanced', 'plupload_image', 'thickbox_image' ), true ) ) {
					$field_value = array_values( $field_value );
				}
				$output[ $field['id'] ] = $field_value;
			}
		}

		return $output;
	}

	/**
	 * Get supported types in Rest API.
	 *
	 * @param string $type 'post' or 'taxonomy'.
	 *
	 * @return array
	 */
	protected function get_types( $type = 'post' ) {
		$types = get_post_types( array(), 'objects' );
		if ( 'taxonomy' === $type ) {
			$types = get_taxonomies( array(), 'objects' );
		}
		foreach ( $types as $type => $object ) {
			if ( empty( $object->show_in_rest ) ) {
				unset( $types[ $type ] );
			}
		}

		return array_keys( $types );
	}
}
