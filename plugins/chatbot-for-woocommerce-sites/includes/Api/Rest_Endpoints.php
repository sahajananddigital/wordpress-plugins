<?php

namespace WcChatbot\Api;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WcChatbot\Bot\Logic;

/**
 * Handles custom REST API endpoints for the chatbot.
 */
class Rest_Endpoints extends WP_REST_Controller {

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		$version   = '1';
		$namespace = 'wc-chatbot/v' . $version;
		$base      = 'message';

		register_rest_route(
			$namespace,
			'/' . $base,
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'handle_message' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => $this->get_endpoint_args(),
				),
			)
		);
	}

	/**
	 * Check if a given request has access to create items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool True if the request has access to create items, WP_Error object otherwise.
	 */
	public function permissions_check( $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'rest_cookie_invalid_nonce', __( 'Cookie nonce is invalid.', 'wc-chatbot' ), array( 'status' => 403 ) );
		}
		return true;
	}

	/**
	 * Handle incoming chat message.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function handle_message( $request ) {
		$message = sanitize_text_field( $request->get_param( 'message' ) );
        $context = $request->get_param( 'context' ) ? (array) $request->get_param( 'context' ) : array();

		if ( empty( $message ) ) {
			return new WP_Error( 'empty_message', __( 'Message cannot be empty.', 'wc-chatbot' ), array( 'status' => 400 ) );
		}

		$bot_logic = new Logic();
		$response_data = $bot_logic->process_message( $message, $context );

		return rest_ensure_response( $response_data );
	}

	/**
	 * Get the endpoint arguments for the message creation route.
	 *
	 * @return array Endpoint arguments.
	 */
	public function get_endpoint_args() {
		return array(
			'message' => array(
				'description'       => __( 'The user message.', 'wc-chatbot' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
            'context' => array(
				'description'       => __( 'Conversation context (state, pending variables).', 'wc-chatbot' ),
				'type'              => 'object',
				'required'          => false,
			),
		);
	}
}
