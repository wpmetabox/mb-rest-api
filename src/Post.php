<?php
namespace MetaBox\RestApi;

use WP_Post;

class Post extends Base {
	public function update( $data, WP_Post $post ) {
		$this->update_values( $data, $post->ID, $post->post_type );
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
