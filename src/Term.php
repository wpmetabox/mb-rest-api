<?php
namespace MetaBox\RestApi;

class Term extends Base {
	/**
	 * Update term meta for the rest API.
	 *
	 * @param string|array $data   Term meta values in either JSON or array format.
	 * @param object       $object Term object.
	 */
	public function update( $data, $object ) {
		$this->update_values( $data, $object->term_id, $object->taxonomy );
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
