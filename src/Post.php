<?php
namespace MetaBox\RestApi;

class Post extends Base {
	public function init() {
		register_rest_field( $this->get_post_types(), self::KEY, [
			'get_callback'    => [ $this, 'get_post_meta' ],
			'update_callback' => [ $this, 'update_post_meta' ],
		] );
	}

	/**
	 * Get post meta for the rest API.
	 *
	 * @param array $object Post object.
	 *
	 * @return array
	 */
	public function get_post_meta( $object ) {
		$post_id   = $object['id'];
		$post_type = get_post_type( $post_id );
		if ( ! $post_type ) {
			return [];
		}

		$meta_boxes = rwmb_get_registry( 'meta_box' )->get_by( [ 'object_type' => 'post' ] );
		$meta_boxes = array_filter( $meta_boxes, function ( $meta_box ) use ( $post_type ) {
			return in_array( $post_type, $meta_box->post_types, true );
		} );

		return $this->get_values( $meta_boxes, $post_id );
	}

	/**
	 * Update post meta for the rest API.
	 *
	 * @param string|array $data   Post meta values in either JSON or array format.
	 * @param object       $object Post object.
	 */
	public function update_post_meta( $data, $object ) {
		$this->update_values( $data, $object->ID, $object->post_type, 'post' );
	}

	private function get_post_types(): array {
		$post_types = get_post_types( [], 'objects' );
		foreach ( $post_types as $key => $post_type_object ) {
			if ( empty( $post_type_object->show_in_rest ) ) {
				unset( $post_types[ $key ] );
			}
		}

		return array_keys( $post_types );
	}
}
