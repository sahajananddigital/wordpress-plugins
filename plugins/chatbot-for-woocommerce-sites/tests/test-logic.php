<?php
/**
 * Class Test_Logic
 *
 * @package Wc_Chatbot
 */

namespace WcChatbot\Tests;

use WP_UnitTestCase;
use WcChatbot\Bot\Logic;

/**
 * Logic test case.
 */
class Test_Logic extends WP_UnitTestCase {

	/**
	 * Test greeting fallback.
	 */
	public function test_get_greeting() {
		$logic = new Logic();
		$response = $logic->process_message( 'hello' );
		$this->assertIsArray( $response );
		$this->assertArrayHasKey( 'text', $response );
		$this->assertStringContainsString( 'Hi there', $response['text'] );
	}

	/**
	 * Test order lookup start.
	 */
	public function test_start_order_lookup() {
		$logic = new Logic();
		$response = $logic->process_message( 'track order' );
		$this->assertIsArray( $response );
		$this->assertArrayHasKey( 'text', $response );
		$this->assertArrayHasKey( 'context', $response );
		$this->assertEquals( 'awaiting_order_id', $response['context']['state'] );
	}

	/**
	 * Test order ID validation.
	 */
	public function test_order_id_validation() {
		$logic = new Logic();
		$context = array( 'state' => 'awaiting_order_id' );
		$response = $logic->process_message( 'abc', $context );
		$this->assertStringContainsString( 'valid Order ID', $response['text'] );
		
		$response2 = $logic->process_message( '123', $context );
		$this->assertStringContainsString( 'billing email', $response2['text'] );
		$this->assertEquals( 'awaiting_order_email', $response2['context']['state'] );
		$this->assertEquals( 123, $response2['context']['order_id'] );
	}

	/**
	 * Test email validation.
	 */
	public function test_email_validation() {
		$logic = new Logic();
		$context = array( 'state' => 'awaiting_order_email', 'order_id' => 123 );
		$response = $logic->process_message( 'not-an-email', $context );
		$this->assertStringContainsString( 'valid email address', $response['text'] );
	}

}
