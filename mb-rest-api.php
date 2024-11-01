<?php
/**
 * Plugin Name: MB Rest API
 * Plugin URI:  https://metabox.io/plugins/mb-rest-api/
 * Description: Add Meta Box custom fields to WordPress Rest API.
 * Version:     2.0.5
 * Author:      MetaBox.io
 * Author URI:  https://metabox.io
 * License:     GPL2+
 * Text Domain: mb-rest-api
 * Domain Path: /languages/
 */

// Prevent loading this file directly.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! function_exists( 'mb_rest_api_load' ) ) {
	// Load necessary admin files.
	require_once ABSPATH . 'wp-admin/includes/template.php';
	require_once ABSPATH . 'wp-admin/includes/post.php';
	require_once ABSPATH . 'wp-admin/includes/comment.php';

	if ( file_exists( __DIR__ . '/vendor' ) ) {
		require __DIR__ . '/vendor/autoload.php';
	}

	add_action( 'init', 'mb_rest_api_load', 5 );

	function mb_rest_api_load() {
		new MetaBox\RestApi\Post;
		new MetaBox\RestApi\Term;
		new MetaBox\RestApi\User;
		new MetaBox\RestApi\Comment;
		new MetaBox\RestApi\Setting;
	}
}
