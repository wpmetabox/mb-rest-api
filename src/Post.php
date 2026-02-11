<?php
namespace MetaBox\RestApi;

use WP_Post;

class Post extends Base {
	public function update( $data, $post ) {
		if ( is_object( $post ) && method_exists( $post, 'get_id' ) && ! ( $post instanceof \WP_Post ) ) {
			$post_id = $post->get_id();
		} elseif ( $post instanceof \WP_Post ) {
			$post_id = $post->ID;
		} else {
			// Fallback value
			$post_id = isset( $post->ID ) ? $post->ID : ( isset( $post['id'] ) ? $post['id'] : 0 );
		}

		$this->update_values( $data, $post_id, $post->post_type );
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
