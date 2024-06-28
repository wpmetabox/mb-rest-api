<?php
namespace MetaBox\RestApi;

use WP_Post;

class Post extends Base {
	public function update( $data, $post ) {
		if ( property_exists( $post, 'post_type' ) && 'product' === $post->post_type ) {
			$post_id = $post->get_id();
		} else {
			$post_id = $post->ID;
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
