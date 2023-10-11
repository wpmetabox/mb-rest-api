<?php
namespace MetaBox\RestApi;

class Comment extends Base {
	/**
	 * Update comment meta for the rest API.
	 *
	 * @param string|array $data   Comment meta values in either JSON or array format.
	 * @param object       $object Comment object.
	 */
	public function update( $data, $object ) {
		$this->update_values( $data, $object->comment_ID, 'comment' );
	}
}
