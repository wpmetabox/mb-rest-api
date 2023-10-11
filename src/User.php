<?php
namespace MetaBox\RestApi;

class User extends Base {
	/**
	 * Update user meta for the rest API.
	 *
	 * @param string|array $data   User meta values in either JSON or array format.
	 * @param object       $object User object.
	 */
	public function update( $data, $object ) {
		$this->update_values( $data, $object->ID, 'user' );
	}
}
