<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WSCH_REST_Controller extends WP_REST_Controller {

    public function register_routes() {
        register_rest_route( 'wsch/v1', '/settings', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_settings' ),
                'permission_callback' => array( $this, 'permissions_check' ),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'update_settings' ),
                'permission_callback' => array( $this, 'permissions_check' ),
            ),
        ) );
    }

    public function permissions_check() {
        return current_user_can( 'manage_options' );
    }

    public function get_settings() {
        $settings = get_option( 'wsch_settings', array() );
        return rest_ensure_response( $settings );
    }

    public function update_settings( $request ) {
        $settings = $request->get_json_params();
        update_option( 'wsch_settings', $settings );
        return rest_ensure_response( $settings );
    }
}
