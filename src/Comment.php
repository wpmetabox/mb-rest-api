<?php
namespace MetaBox\RestApi;

class Comment extends Base {
	public function init() {
		register_rest_field( 'comment', 'meta_box', [
			'get_callback'    => [ $this, 'get' ],
			'update_callback' => [ $this, 'update' ],
		] );
	}

	/**
	 * Get comment meta for the rest API.
	 *
	 * @param array $object Comment object.
	 */
	public function get( $object ): array {
		return $this->get_values( $object['id'], [ 'object_type' => 'comment' ] );
	}

	/**
	 * Update comment meta for the rest API.
	 *
	 * @param string|array $data   Comment meta values in either JSON or array format.
	 * @param object       $object Comment object.
	 */
	public function update( $data, $object ) {
		$this->update_values( $data, $object->comment_ID, 'comment', 'comment' );
	}
}
