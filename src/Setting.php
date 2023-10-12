<?php
namespace MetaBox\RestApi;

use WP_REST_Server;
use WP_REST_Request;

class Setting extends Base {
	public function init() {
		register_rest_route( self::NAMESPACE, '/settings-page/', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_data' ],
			'permission_callback' => [ $this, 'has_permission' ],
		] );

		register_rest_route( self::NAMESPACE, '/settings-page/', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'update_data' ],
			'permission_callback' => [ $this, 'has_permission' ],
		] );
	}

	public function has_permission( WP_REST_Request $request ) {
		$settings_page = $this->get_settings_page( $request );
		return current_user_can( $settings_page['capability'] ?? 'edit_theme_options' );
	}

	public function get_data( WP_REST_Request $request ) {
		$settings_page = $this->get_settings_page( $request );
		$option_name   = $settings_page['option_name'] ?: $settings_page['id'];
		$fields        = $this->get_fields( $settings_page['id'] );
		return $this->get_values( $option_name, $fields );
	}

	public function update_data( WP_REST_Request $request ) {
		$settings_page = $this->get_settings_page( $request );
		$option_name   = $settings_page['option_name'] ?: $settings_page['id'];
		$data          = $request->get_param( 'data' );

		$this->update_values( $data, $option_name, $option_name );

		return $this->get_data( $request );
	}

	private function get_settings_page( WP_REST_Request $request ): array {
		$id = $request->get_param( 'id' );
		if ( ! $id ) {
			$this->send_error_message( 'no_settings_page_id', __( 'No settings page id.', 'mb-rest-api' ) );
		}

		$settings_pages = apply_filters( 'mb_settings_pages', [] );
		foreach ( $settings_pages as $settings_page ) {
			if ( $settings_page['id'] === $id ) {
				return $settings_page;
			}
		}

		// Translators: %s - settings page id.
		$this->send_error_message( 'settings_page_not_exists', sprintf( __( "Settings page '%s' does not exist.", 'mb-rest-api' ), $id ) );
	}
}
