<?php
/**
 * Plugin Name: MB Rest API
 * Plugin URI: https://metabox.io
 * Description: Add Meta Box custom fields to WordPress Rest API.
 * Version: 1.0.1
 * Author: Rilwis
 * Author URI: http://www.deluxeblogtips.com
 * License: GPL2+
 * Text Domain: mb-rest-api
 * Domain Path: /lang/
 */

/**
 * Meta Box Rest API class
 * @package    Meta Box
 * @subpackage MB Rest API
 */
class MB_Rest_API {
	/**
	 * Register new field 'meta_box' for all meta box's fields.
	 */
	public function init() {
		register_rest_field( $this->get_types(), 'meta_box', array(
			'get_callback' => array( $this, 'get_post_meta' ),
		) );
		register_rest_field( $this->get_types( 'taxonomy' ), 'meta_box', array(
			'get_callback' => array( $this, 'get_term_meta' ),
		) );
	}

	/**
	 * Get post meta for the rest API.
	 *
	 * @param array $object Post object.
	 *
	 * @return array
	 */
	public function get_post_meta( $object ) {
		$output     = array();
		$meta_boxes = RWMB_Core::get_meta_boxes();
		foreach ( $meta_boxes as $meta_box ) {
			$meta_box = RW_Meta_Box::normalize( $meta_box );
			if ( ! in_array( $object['type'], $meta_box['post_types'] ) ) {
				continue;
			}
			foreach ( $meta_box['fields'] as $field ) {
				if ( empty( $field['id'] ) ) {
					continue;
				}
				$output[ $field['id'] ] = rwmb_get_value( $field['id'] );
			}
		}

		return $output;
	}

	/**
	 * Get term meta for the rest API.
	 *
	 * @param array $object Term object
	 *
	 * @return array
	 */
	public function get_term_meta( $object ) {
		$output = array();
		if ( ! class_exists( 'MB_Term_Meta_Box' ) ) {
			return $output;
		}

		RWMB_Core::get_meta_boxes();
		$meta_boxes = MB_Term_Meta_Loader::$meta_boxes;

		foreach ( $meta_boxes as $meta_box ) {
			if ( ! in_array( $object['taxonomy'], (array) $meta_box['taxonomies'] ) ) {
				continue;
			}
			$fields = RW_Meta_Box::normalize_fields( $meta_box['fields'] );
			foreach ( $fields as $field ) {
				if ( empty( $field['id'] ) ) {
					continue;
				}
				$single                 = $field['clone'] || ! $field['multiple'];
				$output[ $field['id'] ] = get_term_meta( $object['id'], $field['id'], $single );
			}
		}

		return $output;
	}

	/**
	 * Get supported types in Rest API.
	 *
	 * @param string $type 'post' or 'taxonomy'
	 *
	 * @return array
	 */
	protected function get_types( $type = 'post' ) {
		$types = get_post_types( array(), 'objects' );
		if ( 'taxonomy' == $type ) {
			$types = get_taxonomies( array(), 'objects' );
		}
		foreach ( $types as $type => $object ) {
			if ( empty( $object->show_in_rest ) ) {
				unset( $types[ $type ] );
			}
		}

		return array_keys( $types );
	}
}

$mb_rest_api = new MB_Rest_API;
add_action( 'rest_api_init', array( $mb_rest_api, 'init' ) );
