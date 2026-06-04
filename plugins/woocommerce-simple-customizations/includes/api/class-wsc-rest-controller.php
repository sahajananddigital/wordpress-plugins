<?php
/**
 * REST Controller Class
 *
 * @package WSC
 */

defined( 'ABSPATH' ) || exit;

class WSC_REST_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wsc/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'settings';

	/**
	 * Register the routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Check permissions.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool
	 */
	public function get_items_permissions_check( $request ) {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Check create permissions.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool
	 */
	public function create_item_permissions_check( $request ) {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Get settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		$settings = get_option( 'wsc_settings', array() );
		return rest_ensure_response( $settings );
	}

	/**
	 * Update settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$params = $request->get_json_params();
        $settings = array();

        if ( is_array( $params ) ) {
            foreach ( $params as $key => $value ) {
                $sanitized_key = sanitize_text_field( $key );
                // Allow deep arrays for rules, bools, or strings
                if ( is_array( $value ) ) {
                     // Basic recursive sanitization could go here, but for now we trust the structure of rules
                     // as they are validated at runtime. Ideally, we map specific keys to specific sanitizers.
                     $settings[ $sanitized_key ] = $value;
                } elseif ( is_bool( $value ) ) {
                    $settings[ $sanitized_key ] = $value;
                } else {
                    if ( 'label_printing_template' === $sanitized_key ) {
                        $settings[ $sanitized_key ] = wp_kses_post( $value );
                    } else {
                        $settings[ $sanitized_key ] = sanitize_text_field( $value );
                    }
                }
            }
        }

		update_option( 'wsc_settings', $settings );
		return rest_ensure_response( $settings );
	}
}
