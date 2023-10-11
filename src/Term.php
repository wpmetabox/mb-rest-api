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
			'get_callback'    => [ $this, 'get_term_meta' ],
			'update_callback' => [ $this, 'update_term_meta' ],
		] );
	}

	/**
	 * Get term meta for the rest API.
	 *
	 * @param array $object Term object.
	 *
	 * @return array
	 */
	public function get_term_meta( $object ) {
		$term_id = $object['id'];
		$term    = get_term( $term_id );
		if ( is_wp_error( $term ) || ! $term ) {
			return [];
		}

		$meta_boxes = rwmb_get_registry( 'meta_box' )->get_by( [ 'object_type' => 'term' ] );
		$meta_boxes = array_filter( $meta_boxes, function ( $meta_box ) use ( $term ) {
			return in_array( $term->taxonomy, $meta_box->taxonomies, true );
		} );

		return $this->get_values( $meta_boxes, $term_id, [ 'object_type' => 'term' ] );
	}

	/**
	 * Update term meta for the rest API.
	 *
	 * @param string|array $data   Term meta values in either JSON or array format.
	 * @param object       $object Term object.
	 */
	public function update_term_meta( $data, $object ) {
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
