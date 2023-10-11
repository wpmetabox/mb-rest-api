<?php
namespace MetaBox\RestApi;

class Post extends Base {
	/**
	 * Update post meta for the rest API.
	 *
	 * @param string|array $data   Post meta values in either JSON or array format.
	 * @param object       $object Post object.
	 */
	public function update( $data, $object ) {
		$this->update_values( $data, $object->ID, $object->post_type, 'post' );
	}

	protected function get_types(): array {
		$post_types = get_post_types( [], 'objects' );
		foreach ( $post_types as $key => $post_type_object ) {
			if ( empty( $post_type_object->show_in_rest ) ) {
				unset( $post_types[ $key ] );
			}
		}

		return array_keys( $post_types );
	}
}
