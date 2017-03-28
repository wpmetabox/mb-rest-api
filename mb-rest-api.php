<?php
/**
 * Plugin Name: MB Rest API
 * Plugin URI: https://metabox.io
 * Description: Add Meta Box custom fields to WordPress Rest API.
 * Version: 1.1
 * Author: Rilwis
 * Author URI: http://www.deluxeblogtips.com
 * License: GPL2+
 * Text Domain: mb-rest-api
 * Domain Path: /languages/
 */

/**
 * Load necessary admin files
 */
include_once ABSPATH . 'wp-admin/includes/template.php';
include_once ABSPATH . 'wp-admin/includes/post.php';

/**
 * Meta Box Rest API class
 * @package	Meta Box
 * @subpackage MB Rest API
 */
class MB_Rest_API {
	/**
	 * Register new field 'meta_box' for all meta box's fields.
	 */
	public function init() {
		register_rest_field( $this->get_types(), 'meta_box', array(
			'get_callback'	=> array( $this, 'get_post_meta_rest_api' ),
			'update_callback' => array( $this, 'update_post_meta_rest_api' )
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
	public function get_post_meta_rest_api( $object ) {
		$output	 = array();
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
				$field_value = rwmb_get_value( $field['id'] );

				/*
				 * Make sure values of file/image fields are always indexed 0, 1, 2, ...
				 * @link https://github.com/malfborger/mb-rest-api/commit/31aa8fa445c188e8a71ebff80027acbcaa0fd268
				 */
				if ( is_array( $field_value ) && in_array( $field['type'], array( 'media', 'file', 'file_upload', 'file_advanced', 'image', 'image_upload', 'image_advanced', 'plupload_image', 'thickbox_image' ) ) ) {
					$field_value = array_values( $field_value );
				}
				$output[ $field['id'] ] = $field_value;
			}
		}

		return $output;
	}

	/**
	 * Update post meta for the rest API.
	 *
	 * @param string $json Post meta values in JSON format.
	 * @param object $object Post object.
	 *
	 * @return BOOLEAN
	 */
	public function update_post_meta_rest_api( $json, $object ) {
		$result = false;
		//error_log( "json was" . var_export( $json, true )  );
		
		$json_lasterror = JSON_ERROR_NONE;
		if ( !is_array($json) ){
			$post_data = json_decode( $json, true );
			$json_lasterror = json_last_error();
		} else {
			$post_data = $json;
		}
		
		// identify meta-box
		$meta_boxes = RWMB_Core::get_meta_boxes();
		foreach ( $meta_boxes as $meta_box ) {
			$meta_box = RW_Meta_Box::normalize( $meta_box );
			//error_log( "examine meta-box " . $meta_box['title']  );
			//error_log( "post_types " . var_export( $meta_box['post_types'], true )  );
			//error_log( "need  " . var_export( $object->post_type, true )  );
			if ( ! in_array( $object->post_type, $meta_box['post_types'] ) ) {
				continue;
			}
			//error_log( "using meta-box " . $meta_box['title']  );

			
			//for each value passed in
			foreach ( $post_data as $field_name => $value ) {
				// find it in the metabox (or not)
				foreach ( $meta_box['fields'] as $field ) {
					if ($field['id'] == $field_name){
						switch ($field['type']){
							default: // most types can just be written to post-meta
									// unless they are multiple
								// if it's an array passed
								if ( is_array($value) ){
									// if multiple, 
									if (isset($field['multiple']) && $field['multiple']){
										if (isset($field['clone']) && $field['clone']){
											// clonable, write as csv
											// **** UNTESTED ****
											$strval = '';
											foreach ($value as $val) {
												if ($strval != '') $strval = $strval . ',';
												$strval = $strval . $val;
											}
											$result = (update_post_meta( $object->ID, $field_name, strip_tags( $strval ) ) !== false);
										} else {
											// and not clonable, then write as separate post_meta fields
											$result = true;
											delete_post_meta($object->ID, $field_name);
											foreach ($value as $val) {
												if(add_post_meta($object->ID, $field_name, $val) === false){
                                                    $result = false;
                                                }
											}
										}
									} else {
										// something we did not deal with
										error_log( "did not write meta-box field " . var_export( $field ) );
										$result = false;
									}
									
								} else {
									$result = true;
									if (update_post_meta( $object->ID, $field_name, strip_tags( $value ) ) === false){
                                        $result = false;
                                    }
								}
								break;
								
							case 'taxonomy': // expect { 'slug': 'name' }; ignore other fields
								// only allow valid terms
								if (term_exists( $value['slug'], $field['taxonomy'])){
									$term_taxonomy_ids = wp_set_object_terms( $object->ID, $value['slug'], $field['taxonomy'] );
									if (is_wp_error( $term_taxonomy_ids ) ) {
										//what to do?
										$result = false;
									} else {
										$result = true;
									}
								}
								break;
						}
						// push result as an 'updated' flag - good for everything except taxonomy ?
						do_action( 'mb_rest_api_set_meta', $object, $field, $value, $result );
						break; // we found the field, so loop to next value
					}
				}
			}
			break; // don't waste time on other meta-boxes.
		}
		
		return true;
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
				$single				 = $field['clone'] || ! $field['multiple'];
				$field_value = get_term_meta( $object['id'], $field['id'], $single );

				/*
				 * Make sure values of file/image fields are always indexed 0, 1, 2, ...
				 * @link https://github.com/malfborger/mb-rest-api/commit/31aa8fa445c188e8a71ebff80027acbcaa0fd268
				 */
				if ( is_array( $field_value ) && in_array( $field['type'], array( 'media', 'file', 'file_upload', 'file_advanced', 'image', 'image_upload', 'image_advanced', 'plupload_image', 'thickbox_image' ) ) ) {
					$field_value = array_values( $field_value );
				}
				$output[ $field['id'] ] = $field_value;
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

?>