<?php
/**
 * Class Test_Rest_Endpoints
 *
 * @package Wc_Chatbot
 */

namespace WcChatbot\Tests;

use WP_UnitTestCase;
use WP_REST_Request;
use WcChatbot\Api\Rest_Endpoints;

/**
 * Rest Endpoints test case.
 */
class Test_Rest_Endpoints extends WP_UnitTestCase {

	/**
	 * Test empty message.
	 */
	public function test_empty_message() {
		$endpoint = new Rest_Endpoints();
		$request = new WP_REST_Request( 'POST', '/wc-chatbot/v1/message' );
		$request->set_param( 'message', '' );
		$response = $endpoint->handle_message( $request );
		
		$this->assertWPError( $response );
		$this->assertEquals( 'empty_message', $response->get_error_code() );
	}

	/**
	 * Test valid message.
	 */
	public function test_valid_message() {
		$endpoint = new Rest_Endpoints();
		$request = new WP_REST_Request( 'POST', '/wc-chatbot/v1/message' );
		$request->set_param( 'message', 'hello' );
		$response = $endpoint->handle_message( $request );
		
		$this->assertNotWPError( $response );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'text', $data );
	}

	/**
	 * Test invalid nonce.
	 */
	public function test_invalid_nonce() {
		$endpoint = new Rest_Endpoints();
		$request = new WP_REST_Request( 'POST', '/wc-chatbot/v1/message' );
		$request->add_header( 'X-WP-Nonce', 'invalid_nonce' );
		$response = $endpoint->permissions_check( $request );
		
		$this->assertWPError( $response );
		$this->assertEquals( 'rest_cookie_invalid_nonce', $response->get_error_code() );
	}

	/**
	 * Test valid nonce.
	 */
	public function test_valid_nonce() {
		$endpoint = new Rest_Endpoints();
		$request = new WP_REST_Request( 'POST', '/wc-chatbot/v1/message' );
		
		$nonce = wp_create_nonce( 'wp_rest' );
		$request->add_header( 'X-WP-Nonce', $nonce );
		
		$response = $endpoint->permissions_check( $request );
		$this->assertTrue( $response );
	}
}
