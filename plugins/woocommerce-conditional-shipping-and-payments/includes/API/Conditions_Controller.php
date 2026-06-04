<?php
/**
 * REST API Conditions Controller Class.
 *
 * @package WooCommerceConditionalShipping
 */

namespace SahajanandDigital\WooCommerceConditionalShipping\API;

use WP_REST_Controller;
use WP_REST_Server;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conditions_Controller class.
 */
class Conditions_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-csp/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'conditions';

	/**
	 * Init.
	 */
	public static function init() {
		$instance = new self();
		$instance->register_routes();
	}

	/**
	 * Register the routes for the objects of the controller.
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
					'callback'            => array( $this, 'create_items' ),
					'permission_callback' => array( $this, 'create_items_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error( 'wc_csp_rest_cannot_view', __( 'Sorry, you cannot view these resources.', 'woocommerce-conditional-shipping-and-payments' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Check if a given request has access to create items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has create access, WP_Error object otherwise.
	 */
	public function create_items_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error( 'wc_csp_rest_cannot_create', __( 'Sorry, you cannot create these resources.', 'woocommerce-conditional-shipping-and-payments' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Get all conditions.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$conditions = get_option( 'wc_csp_conditions', array() );
        // Ensure it's always an array
        if ( ! is_array( $conditions ) ) {
            $conditions = array();
        }
		return rest_ensure_response( $conditions );
	}

	/**
	 * Update conditions (Create/Replace).
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_items( $request ) {
		$conditions = $request->get_json_params();

        if ( ! is_array( $conditions ) ) {
             return new WP_Error( 'wc_csp_invalid_data', __( 'Invalid data format.', 'woocommerce-conditional-shipping-and-payments' ), array( 'status' => 400 ) );
        }

        // Sanitize? 
        // For simplicity, we are trusting the admin input but in real world we should sanitize each field.
        // Let's do basic sanitization loop
        $sanitized_conditions = array();
        foreach ( $conditions as $condition ) {
            $sanitized = array(
                'id' => isset($condition['id']) ? sanitize_text_field($condition['id']) : uniqid(),
                'title' => isset($condition['title']) ? sanitize_text_field($condition['title']) : '',
                'enabled' => isset($condition['enabled']) ? (bool) $condition['enabled'] : false,
                'action' => isset($condition['action']) ? sanitize_text_field($condition['action']) : 'disable',
                'payment_methods' => isset($condition['payment_methods']) && is_array($condition['payment_methods']) ? array_map('sanitize_text_field', $condition['payment_methods']) : array(),
                'countries' => isset($condition['countries']) && is_array($condition['countries']) ? array_map('sanitize_text_field', $condition['countries']) : array(),
            );
            $sanitized_conditions[] = $sanitized;
        }

		update_option( 'wc_csp_conditions', $sanitized_conditions );

		return rest_ensure_response( $sanitized_conditions );
	}
}
