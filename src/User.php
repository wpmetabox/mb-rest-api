<?php
namespace MetaBox\RestApi;

class User extends Base {
	public function init() {
		register_rest_field( 'user', self::KEY, [
			'get_callback'    => [ $this, 'get_user_meta' ],
			'update_callback' => [ $this, 'update_user_meta' ],
		] );
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
}
