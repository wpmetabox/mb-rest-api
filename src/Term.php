<?php
namespace MetaBox\RestApi;

use WP_Term;

class Term extends Base {
	public function update( $data, WP_Term $term ) {
		$this->update_values( $data, $term->term_id, $term->taxonomy );
	}

	protected function get_types(): array {
		$taxonomies = get_taxonomies( [], 'objects' );
		foreach ( $taxonomies as $key => $taxonomy_object ) {
			if ( empty( $taxonomy_object->show_in_rest ) ) {
				unset( $taxonomies[ $key ] );
			}
		}

		$taxonomies = array_keys( $taxonomies );
		if ( in_array( 'post_tag', $taxonomies, true ) ) {
			$index                = array_search( 'post_tag', $taxonomies, true );
			$taxonomies[ $index ] = 'tag';
		}

		return $taxonomies;
	}
}
