<?php
/**
 * Plugin Name: MB Rest API
 * Plugin URI:  https://metabox.io/plugins/mb-rest-api/
 * Description: Add Meta Box custom fields to WordPress Rest API.
 * Version:     1.3.6
 * Author:      MetaBox.io
 * Author URI:  https://metabox.io
 * License:     GPL2+
 * Text Domain: mb-rest-api
 * Domain Path: /languages/
 *
 * @package    Meta Box
 * @subpackage MB Rest API
 */

// Prevent loading this file directly.
defined( 'ABSPATH' ) || die;

if ( ! function_exists( 'mb_rest_api_load' ) ) {
	// Load necessary admin files.
	require_once ABSPATH . 'wp-admin/includes/template.php';
	require_once ABSPATH . 'wp-admin/includes/post.php';

	add_action( 'init', 'mb_rest_api_load', 5 );

	/**
	 * Loader function for the plugin files.
	 */
	function mb_rest_api_load() {
		require_once __DIR__ . '/class-mb-rest-api.php';

		$mb_rest_api = new MB_Rest_API();
		add_action( 'rest_api_init', array( $mb_rest_api, 'init' ) );
	}
}
