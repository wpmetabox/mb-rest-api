<?php
namespace MetaBox\RestApi;

use WP_REST_Server;
use WP_REST_Request;

class SettingsPage extends Base {

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {
		register_rest_route( self::NAMESPACE, '/settings-page/', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_settings_meta' ],
			'permission_callback' => [ $this, 'has_permission' ],
		) );

		register_rest_route( self::NAMESPACE, '/settings-page/', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'update_settings_meta' ],
			'permission_callback' => [ $this, 'has_permission' ],
		) );
	}

	public function has_permission() {
		return current_user_can( 'manage_options' );
	}

	public function get_settings_meta( WP_REST_Request $request ) {
		$settings_pages_id = $request->get_param( 'id' );
		if ( ! $settings_pages_id ) {
			return [];
		}

		$meta_boxes = rwmb_get_registry( 'meta_box' )->get_by( [ 'object_type' => 'setting' ] );
		$meta_boxes = array_filter( $meta_boxes, function ( $meta_box ) use ( $settings_pages_id ) {
			return in_array( $settings_pages_id, $meta_box->settings_pages, true );
		} );

		$option_name = $this->get_option_name_from_settings_page_id( $settings_pages_id );
		return $this->get_values( $meta_boxes, $option_name, [ 'object_type' => 'setting' ] );
	}

	public function update_settings_meta( WP_REST_Request $request ) {
		$settings_pages_id = $request->get_param( 'id' );
		if ( ! $settings_pages_id ) {
			$this->send_error_message(
				'no_settings_page',
				__( 'No settings page.', 'mb-rest-api' )
			);
		}

		$option_name = $this->get_option_name_from_settings_page_id( $settings_pages_id );
		$data        = $request->get_param( 'data' );

		$this->update_values( $data, $option_name, $option_name, 'setting' );

		return $this->get_settings_meta( $request );
	}

	private function get_option_name_from_settings_page_id( string $settings_pages_id ) {
		$settings_pages = apply_filters( 'mb_settings_pages', [] );
		foreach ( $settings_pages as $settings_page ) {
			if ( $settings_page['id'] === $settings_pages_id ) {
				return $settings_page['option_name'] ?: $settings_page['id'];
			}
		}

		$this->send_error_message(
			'no_settings_page',
			// Translators: %s - Settings page ID.
			sprintf( __( 'There is no settings page "%s" on your website.', 'mb-rest-api' ), $settings_pages_id )
		);
	}
}
