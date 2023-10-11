<?php
namespace MetaBox\RestApi;

class User extends Base {
	public function init() {
		register_rest_field( 'user', self::KEY, [
			'get_callback'    => [ $this, 'get' ],
			'update_callback' => [ $this, 'update' ],
		] );
	}

	/**
	 * Get user meta for the rest API.
	 *
	 * @param array $object User object.
	 */
	public function get( $object ): array {
		return $this->get_values( $object['id'], [ 'object_type' => 'user' ] );
	}

	/**
	 * Update user meta for the rest API.
	 *
	 * @param string|array $data   User meta values in either JSON or array format.
	 * @param object       $object User object.
	 */
	public function update( $data, $object ) {
		$this->update_values( $data, $object->ID, 'user', 'user' );
	}
}
