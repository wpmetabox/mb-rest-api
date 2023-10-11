<?php
namespace MetaBox\RestApi;

class Comment extends Base {
	public function init() {
		register_rest_field( 'comment', 'meta_box', [
			'get_callback'    => [ $this, 'get_comment_meta' ],
			'update_callback' => [ $this, 'update_comment_meta' ],
		] );
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
}
