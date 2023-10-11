<?php
namespace MetaBox\RestApi;

class Term extends Base {
	public function init() {
		$taxonomies = $this->get_taxonomies();
		if ( in_array( 'post_tag', $taxonomies, true ) ) {
			$index                = array_search( 'post_tag', $taxonomies, true );
			$taxonomies[ $index ] = 'tag';
		}
		register_rest_field( $taxonomies, self::KEY, [
			'get_callback'    => [ $this, 'get' ],
			'update_callback' => [ $this, 'update' ],
		] );
	}

	/**
	 * Get term meta for the rest API.
	 *
	 * @param array $object Term object.
	 */
	public function get( $object ): array {
		return $this->get_values( $object['id'], [ 'object_type' => 'term' ] );
	}

	/**
	 * Update term meta for the rest API.
	 *
	 * @param string|array $data   Term meta values in either JSON or array format.
	 * @param object       $object Term object.
	 */
	public function update( $data, $object ) {
		$this->update_values( $data, $object->term_id, $object->taxonomy, 'term' );
	}

	private function get_taxonomies(): array {
		$taxonomies = get_taxonomies( [], 'objects' );
		foreach ( $taxonomies as $key => $taxonomy_object ) {
			if ( empty( $taxonomy_object->show_in_rest ) ) {
				unset( $taxonomies[ $key ] );
			}
		}

		return array_keys( $taxonomies );
	}
}
