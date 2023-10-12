<?php
namespace MetaBox\RestApi;

use WP_User;

class User extends Base {
	public function update( $data, WP_User $user ) {
		$this->update_values( $data, $user->ID, 'user' );
	}
}
