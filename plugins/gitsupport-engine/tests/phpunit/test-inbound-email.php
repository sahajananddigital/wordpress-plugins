<?php
/**
 * Class Inbound_Email_Test
 *
 * @package Gitsupport_Engine
 */

class Inbound_Email_Test extends WP_UnitTestCase {

	/**
	 * REST server instance.
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * Track mocked requests.
	 *
	 * @var array
	 */
	protected $http_requests = array();

	/**
	 * Set up test environment.
	 */
	public function set_up() {
		parent::set_up();

		global $wp_rest_server;
		$this->server = new WP_REST_Server();
		do_action( 'rest_api_init', $this->server );

		// Configure plugin options.
		update_option( 'gitsupport_inbound_secret', 'my-inbound-secret-key' );
		update_option( 'gitsupport_github_token', 'mock-github-token' );
		update_option( 'gitsupport_github_owner', 'test-owner' );
		update_option( 'gitsupport_github_repo', 'test-repo' );
		update_option( 'gitsupport_ai_api_key', 'mock-ai-key' );
		update_option( 'gitsupport_ai_provider', 'gemini' );

		// Register mock filter.
		add_filter( 'pre_http_request', array( $this, 'mock_http_requests' ), 10, 3 );
		$this->http_requests = array();
	}

	/**
	 * Clean up test environment.
	 */
	public function tear_down() {
		global $wp_rest_server;
		$wp_rest_server = null;

		delete_option( 'gitsupport_inbound_secret' );
		delete_option( 'gitsupport_github_token' );
		delete_option( 'gitsupport_github_owner' );
		delete_option( 'gitsupport_github_repo' );
		delete_option( 'gitsupport_ai_api_key' );
		delete_option( 'gitsupport_ai_provider' );

		remove_filter( 'pre_http_request', array( $this, 'mock_http_requests' ), 10 );

		// Clean up custom table.
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE " . \GitSupport\Engine\Database::get_table_name() );

		parent::tear_down();
	}

	/**
	 * Mock HTTP requests for Gemini and GitHub.
	 */
	public function mock_http_requests( $preempt, $parsed_args, $url ) {
		$this->http_requests[] = array(
			'url'  => $url,
			'args' => $parsed_args,
		);

		// Mock Gemini API request
		if ( strpos( $url, 'generativelanguage.googleapis.com' ) !== false ) {
			return array(
				'headers'  => array(),
				'body'     => wp_json_encode( array(
					'candidates' => array(
						array(
							'content' => array(
								'parts' => array(
									array(
										'text' => wp_json_encode( array(
											'summary'   => 'Customer has a database issue.',
											'sentiment' => 'negative',
											'priority'  => 'high',
										) ),
									),
								),
							),
						),
					),
				) ),
				'response' => array( 'code' => 200, 'message' => 'OK' ),
				'cookies'  => array(),
				'filename' => null,
			);
		}

		// Mock OpenAI API request
		if ( strpos( $url, 'api.openai.com' ) !== false ) {
			return array(
				'headers'  => array(),
				'body'     => wp_json_encode( array(
					'choices' => array(
						array(
							'message' => array(
								'content' => wp_json_encode( array(
									'summary'   => 'Customer needs help resetting password.',
									'sentiment' => 'neutral',
									'priority'  => 'medium',
								) ),
							),
						),
					),
				) ),
				'response' => array( 'code' => 200, 'message' => 'OK' ),
				'cookies'  => array(),
				'filename' => null,
			);
		}

		// Mock GitHub Issue creation
		if ( strpos( $url, 'api.github.com' ) !== false && strpos( $url, '/issues' ) !== false ) {
			return array(
				'headers'  => array(),
				'body'     => wp_json_encode( array(
					'number'   => 123,
					'title'    => '[Support] Need database help',
					'html_url' => 'https://github.com/test-owner/test-repo/issues/123',
				) ),
				'response' => array( 'code' => 201, 'message' => 'Created' ),
				'cookies'  => array(),
				'filename' => null,
			);
		}

		return false;
	}

	/**
	 * Test inbound request authentication checks.
	 */
	public function test_inbound_authentication() {
		// No secret provided.
		$request = new WP_REST_Request( 'POST', '/gitsupport/v1/inbound' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array() ) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 403, $response->get_status() );

		// Wrong secret.
		$request->set_header( 'X-Inbound-Token', 'wrong-secret' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 403, $response->get_status() );

		// Correct secret.
		$request->set_header( 'X-Inbound-Token', 'my-inbound-secret-key' );
		// Should progress past auth and return 400 due to empty payload/missing email.
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test Postmark payload style parsing.
	 */
	public function test_inbound_postmark_style() {
		$request = new WP_REST_Request( 'POST', '/gitsupport/v1/inbound' );
		$request->set_header( 'X-Inbound-Token', 'my-inbound-secret-key' );
		$request->set_header( 'Content-Type', 'application/json' );

		$payload = array(
			'From'      => 'Jane Doe <jane@example.com>',
			'Subject'   => 'Need database help',
			'TextBody'  => 'My database is failing to connect.',
			'MessageID' => 'postmark-msg-12345',
		);
		$request->set_body( wp_json_encode( $payload ) );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertEquals( '123', $data['github_issue_id'] );

		// Check ticket saved in database correctly.
		$ticket = \GitSupport\Engine\Database::get_ticket_by_email_message_id( 'postmark-msg-12345' );
		$this->assertNotNull( $ticket );
		$this->assertEquals( 'jane@example.com', $ticket->customer_email );
		$this->assertEquals( '123', $ticket->github_issue_id );
		$this->assertEquals( 'open', $ticket->status );

		// Check HTTP calls happened.
		$this->assertCount( 2, $this->http_requests ); // 1 Gemini, 1 GitHub
		$this->assertStringContainsString( 'generativelanguage.googleapis.com', $this->http_requests[0]['url'] );
		$this->assertStringContainsString( 'api.github.com', $this->http_requests[1]['url'] );

		// Check Gemini prompt content.
		$gemini_body = json_decode( $this->http_requests[0]['args']['body'], true );
		$this->assertStringContainsString( 'Need database help', $gemini_body['contents'][0]['parts'][0]['text'] );
	}

	/**
	 * Test Mailgun payload style parsing.
	 */
	public function test_inbound_mailgun_style() {
		// Test OpenAI provider.
		update_option( 'gitsupport_ai_provider', 'openai' );

		$request = new WP_REST_Request( 'POST', '/gitsupport/v1/inbound' );
		$request->set_header( 'Authorization', 'Bearer my-inbound-secret-key' );
		$request->set_header( 'Content-Type', 'application/json' );

		$payload = array(
			'from'          => 'Bob Smith <bob@example.com>',
			'subject'       => 'Lost password',
			'stripped-text' => 'Please reset my password.',
			'Message-Id'    => 'mailgun-msg-54321',
		);
		$request->set_body( wp_json_encode( $payload ) );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertEquals( '123', $data['github_issue_id'] );

		// Check ticket saved.
		$ticket = \GitSupport\Engine\Database::get_ticket_by_email_message_id( 'mailgun-msg-54321' );
		$this->assertNotNull( $ticket );
		$this->assertEquals( 'bob@example.com', $ticket->customer_email );
		$this->assertEquals( '123', $ticket->github_issue_id );
		$this->assertEquals( 'open', $ticket->status );

		// Check HTTP calls.
		$this->assertCount( 2, $this->http_requests ); // 1 OpenAI, 1 GitHub
		$this->assertStringContainsString( 'api.openai.com', $this->http_requests[0]['url'] );
		$this->assertStringContainsString( 'api.github.com', $this->http_requests[1]['url'] );
	}
}
