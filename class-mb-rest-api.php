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
	 * List of media fields to filter.
	 *
	 * @var array
	 */
	protected $media_fields = array(
		'media',
		'file',
		'file_upload',
		'file_advanced',
		'image',
		'image_upload',
		'image_advanced',
		'plupload_image',
		'thickbox_image',
	);

	/**
	 * List of fields that have no values.
	 *
	 * @var array
	 */
	protected $no_value_fields = array(
		'heading',
		'custom_html',
		'divider',
		'button',
	);

	/**
	 * Register new field 'meta_box' for all meta box's fields.
	 */
	public function init() {
		register_rest_field(
			$this->get_types(),
			'meta_box',
			array(
				'get_callback'    => array( $this, 'get_post_meta' ),
				'update_callback' => array( $this, 'update_post_meta' ),
			)
		);

		$taxonomies = $this->get_types( 'taxonomy' );
		if ( in_array( 'post_tag', $taxonomies, true ) ) {
			$post_tag_key                = array_search( 'post_tag', $taxonomies, true );
			$taxonomies[ $post_tag_key ] = 'tag';
		}
		register_rest_field(
			$taxonomies,
			'meta_box',
			array(
				'get_callback'    => array( $this, 'get_term_meta' ),
				'update_callback' => array( $this, 'update_term_meta' ),
			)
		);
		register_rest_field(
			'user',
			'meta_box',
			array(
				'get_callback'    => array( $this, 'get_user_meta' ),
				'update_callback' => array( $this, 'update_user_meta' ),
			)
		);
	}

	/**
	 * Get post meta for the rest API.
	 *
	 * @param array $object Post object.
	 *
	 * @return array
	 */
	public function get_post_meta( $object ) {
		$meta_boxes = rwmb_get_registry( 'meta_box' )->get_by( array( 'object_type' => 'post' ) );
		foreach ( $meta_boxes as $key => $meta_box ) {
			if ( ! in_array( $object['type'], $meta_box->post_types, true ) ) {
				unset( $meta_boxes[ $key ] );
			}
		}
		return $this->get_values( $meta_boxes, $object['id'] );
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
			if ( JSON_ERROR_NONE !== json_last_error() ) {
				return;
			}
		}

		foreach ( $data as $field_id => $value ) {
			$field = rwmb_get_registry( 'field' )->get( $field_id, $object->post_type );
			$this->update_value( $field, $value, $object->ID );
		}

		do_action( 'rwmb_after_save_post', $object->ID );
	}

	/**
	 * Get term meta for the rest API.
	 *
	 * @param array $object Term object.
	 *
	 * @return array
	 */
	public function get_term_meta( $object ) {
		$output     = array();
		$meta_boxes = rwmb_get_registry( 'meta_box' )->get_by(
			array(
				'object_type' => 'term',
			)
		);
		foreach ( $meta_boxes as $key => $meta_box ) {
			if ( ! in_array( $object['taxonomy'], $meta_box->taxonomies, true ) ) {
				unset( $meta_boxes[ $key ] );
			}
		}

		return $this->get_values( $meta_boxes, $object['id'], array( 'object_type' => 'term' ) );
	}

	/**
	 * Update term meta for the rest API.
	 *
	 * @param string|array $data   Term meta values in either JSON or array format.
	 * @param object       $object Term object.
	 */
	public function update_term_meta( $data, $object ) {
		if ( is_string( $data ) ) {
			$data = json_decode( $data, true );
			if ( JSON_ERROR_NONE !== json_last_error() ) {
				return;
			}
		}

		foreach ( $data as $field_id => $value ) {
			$field = rwmb_get_registry( 'field' )->get( $field_id, $object->taxonomy, 'term' );
			$this->update_value( $field, $value, $object->term_id );
		}

		do_action( 'rwmb_after_save_post', $object->term_id );
	}

	/**
	 * Get user meta for the rest API.
	 *
	 * @param array $object User object.
	 *
	 * @return array
	 */
	public function get_user_meta( $object ) {
		$output     = array();
		$meta_boxes = rwmb_get_registry( 'meta_box' )->get_by(
			array(
				'object_type' => 'user',
			)
		);

		return $this->get_values( $meta_boxes, $object['id'], array( 'object_type' => 'user' ) );
	}

	/**
	 * Update user meta for the rest API.
	 *
	 * @param string|array $data   User meta values in either JSON or array format.
	 * @param object       $object User object.
	 */
	public function update_user_meta( $data, $object ) {
		if ( is_string( $data ) ) {
			$data = json_decode( $data, true );
			if ( JSON_ERROR_NONE !== json_last_error() ) {
				return;
			}
		}

		foreach ( $data as $field_id => $value ) {
			$field = rwmb_get_registry( 'field' )->get( $field_id, 'user', 'user' );
			$this->update_value( $field, $value, $object->ID );
		}

		do_action( 'rwmb_after_save_post', $object->ID );
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
	protected function get_values( $meta_boxes, $object_id, $args = array() ) {
		$fields = array();
		foreach ( $meta_boxes as $meta_box ) {
			$fields = array_merge( $fields, $meta_box->fields );
		}
		$fields = array_filter( $fields, array( $this, 'has_value' ) );

		$values = array();
		foreach ( $fields as $field ) {
			$value = rwmb_get_value( $field['id'], $args, $object_id );
			$value = $this->normalize_value( $field, $value );

			$values[ $field['id'] ] = $value;
		}

		return $values;
	}

	/**
	 * Check if a field has values.
	 *
	 * @param array $field Field settings.
	 * @return bool
	 */
	public function has_value( $field ) {
		return ! empty( $field['id'] ) && ! in_array( $field['type'], $this->no_value_fields, true );
	}

	/**
	 * Normalize group value.
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
}
