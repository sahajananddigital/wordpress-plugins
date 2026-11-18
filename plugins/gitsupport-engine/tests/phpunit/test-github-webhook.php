<?php
/**
 * Class GitHub_Webhook_Test
 *
 * @package Gitsupport_Engine
 */

class GitHub_Webhook_Test extends WP_UnitTestCase {

	/**
	 * REST server instance.
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * Outbound email logs.
	 *
	 * @var array
	 */
	protected $sent_emails = array();

	/**
	 * Set up test environment.
	 */
	public function set_up() {
		parent::set_up();

		global $wp_rest_server;
		$this->server = new WP_REST_Server();
		do_action( 'rest_api_init', $this->server );

		update_option( 'gitsupport_github_webhook_secret', 'my-webhook-secret-key' );

		// Hook into pre_wp_mail to capture and block outbound mail.
		add_filter( 'pre_wp_mail', array( $this, 'capture_wp_mail' ), 10, 2 );
		$this->sent_emails = array();
	}

	/**
	 * Clean up test environment.
	 */
	public function tear_down() {
		global $wp_rest_server;
		$wp_rest_server = null;

		delete_option( 'gitsupport_github_webhook_secret' );
		remove_filter( 'pre_wp_mail', array( $this, 'capture_wp_mail' ), 10 );

		// Clean up database.
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE " . \GitSupport\Engine\Database::get_table_name() );

		parent::tear_down();
	}

	/**
	 * Capture and preempt wp_mail.
	 */
	public function capture_wp_mail( $preempt, $args ) {
		$this->sent_emails[] = $args;
		return true; // indicate that mail was "sent" successfully
	}

	/**
	 * Test that webhook secret configuration check works.
	 */
	public function test_webhook_unconfigured_secret() {
		delete_option( 'gitsupport_github_webhook_secret' );

		$request = new WP_REST_Request( 'POST', '/gitsupport/v1/github-comment' );
		$request->set_body( '{"test": true}' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test webhook signature checks.
	 */
	public function test_webhook_signature_verification() {
		$body = wp_json_encode( array( 'action' => 'created' ) );

		// Missing signature header.
		$request = new WP_REST_Request( 'POST', '/gitsupport/v1/github-comment' );
		$request->set_body( $body );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );

		// Invalid signature.
		$request->set_header( 'X-Hub-Signature-256', 'sha256=invalid-signature-value' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 403, $response->get_status() );

		// Valid signature.
		$correct_signature = 'sha256=' . hash_hmac( 'sha256', $body, 'my-webhook-secret-key' );
		$request->set_header( 'X-Hub-Signature-256', $correct_signature );
		// Should succeed auth, then return 200 (ignored because action details are missing).
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test handling of issue comment event.
	 */
	public function test_handle_issue_comment_send_email() {
		// Insert mock ticket into database.
		\GitSupport\Engine\Database::insert_ticket( array(
			'email_message_id' => 'orig-msg-id-123@support.com',
			'customer_email'   => 'customer@example.com',
			'github_issue_id'  => '42',
			'status'           => 'open',
		) );

		// Prepare webhook payload.
		$payload = array(
			'action'  => 'created',
			'issue'   => array(
				'number' => 42,
				'title'  => 'Cannot login to application',
			),
			'comment' => array(
				'body' => "Hi customer,\n/send Please reset your password.\n/resolve",
			),
		);

		$body = wp_json_encode( $payload );
		$correct_signature = 'sha256=' . hash_hmac( 'sha256', $body, 'my-webhook-secret-key' );

		$request = new WP_REST_Request( 'POST', '/gitsupport/v1/github-comment' );
		$request->set_header( 'X-Hub-Signature-256', $correct_signature );
		$request->set_header( 'X-GitHub-Event', 'issue_comment' );
		$request->set_body( $body );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertEquals( 'resolved', $data['status'] );

		// Verify email was sent.
		$this->assertCount( 1, $this->sent_emails );
		$email = $this->sent_emails[0];
		$this->assertEquals( 'customer@example.com', $email['to'] );
		$this->assertEquals( 'Re: Cannot login to application', $email['subject'] );
		$this->assertStringContainsString( 'Please reset your password.', $email['message'] );

		// Verify threading headers.
		$headers = $email['headers'];
		$this->assertContains( 'In-Reply-To: orig-msg-id-123@support.com', $headers );
		$this->assertContains( 'References: orig-msg-id-123@support.com', $headers );

		// Verify ticket status updated in database.
		$ticket = \GitSupport\Engine\Database::get_ticket_by_github_issue_id( '42' );
		$this->assertEquals( 'resolved', $ticket->status );
	}

	/**
	 * Test handling of issue closed/reopened state events.
	 */
	public function test_handle_issue_events() {
		// Insert mock ticket.
		\GitSupport\Engine\Database::insert_ticket( array(
			'email_message_id' => 'msg-999@support.com',
			'customer_email'   => 'customer2@example.com',
			'github_issue_id'  => '99',
			'status'           => 'open',
		) );

		// 1. Closed event.
		$payload_closed = array(
			'action' => 'closed',
			'issue'  => array(
				'number' => 99,
			),
		);
		$body = wp_json_encode( $payload_closed );
		$correct_signature = 'sha256=' . hash_hmac( 'sha256', $body, 'my-webhook-secret-key' );

		$request = new WP_REST_Request( 'POST', '/gitsupport/v1/github-comment' );
		$request->set_header( 'X-Hub-Signature-256', $correct_signature );
		$request->set_header( 'X-GitHub-Event', 'issues' );
		$request->set_body( $body );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		// Verify database state.
		$ticket = \GitSupport\Engine\Database::get_ticket_by_github_issue_id( '99' );
		$this->assertEquals( 'resolved', $ticket->status );

		// 2. Reopened event.
		$payload_reopened = array(
			'action' => 'reopened',
			'issue'  => array(
				'number' => 99,
			),
		);
		$body = wp_json_encode( $payload_reopened );
		$correct_signature = 'sha256=' . hash_hmac( 'sha256', $body, 'my-webhook-secret-key' );

		$request = new WP_REST_Request( 'POST', '/gitsupport/v1/github-comment' );
		$request->set_header( 'X-Hub-Signature-256', $correct_signature );
		$request->set_header( 'X-GitHub-Event', 'issues' );
		$request->set_body( $body );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		// Verify database state.
		$ticket = \GitSupport\Engine\Database::get_ticket_by_github_issue_id( '99' );
		$this->assertEquals( 'open', $ticket->status );
	}
}
