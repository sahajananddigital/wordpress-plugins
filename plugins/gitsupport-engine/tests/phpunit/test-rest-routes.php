<?php
/**
 * Class REST_Routes_Test
 *
 * @package Gitsupport_Engine
 */

class REST_Routes_Test extends WP_UnitTestCase {

	/**
	 * REST server instance.
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * Set up test environment.
	 */
	public function set_up() {
		parent::set_up();

		global $wp_rest_server;
		$this->server = new WP_REST_Server();
		do_action( 'rest_api_init', $this->server );
	}

	/**
	 * Clean up test environment.
	 */
	public function tear_down() {
		global $wp_rest_server;
		$wp_rest_server = null;
		parent::tear_down();
	}

	/**
	 * Test that all expected routes are registered.
	 */
	public function test_routes_registered() {
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( '/gitsupport/v1/settings', $routes );
		$this->assertArrayHasKey( '/gitsupport/v1/metrics', $routes );
		$this->assertArrayHasKey( '/gitsupport/v1/inbound', $routes );
		$this->assertArrayHasKey( '/gitsupport/v1/github-comment', $routes );
	}

	/**
	 * Test settings GET endpoint unauthorized.
	 */
	public function test_get_settings_unauthorized() {
		$request = new WP_REST_Request( 'GET', '/gitsupport/v1/settings' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test settings GET endpoint authorized.
	 */
	public function test_get_settings_authorized() {
		// Create an admin user.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$request = new WP_REST_Request( 'GET', '/gitsupport/v1/settings' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		
		$data = $response->get_data();
		$this->assertArrayHasKey( 'github_api_token', $data );
		$this->assertArrayHasKey( 'ai_provider', $data );
	}

	/**
	 * Test settings POST endpoint unauthorized.
	 */
	public function test_save_settings_unauthorized() {
		$request = new WP_REST_Request( 'POST', '/gitsupport/v1/settings' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'ai_provider' => 'openai' ) ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test settings POST endpoint authorized.
	 */
	public function test_save_settings_authorized() {
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$request = new WP_REST_Request( 'POST', '/gitsupport/v1/settings' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array(
			'github_api_token'      => 'test-token',
			'github_repo_owner'     => 'test-owner',
			'github_repo_name'      => 'test-repo',
			'ai_provider'           => 'openai',
		) ) );
		
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		// Check options updated.
		$this->assertEquals( 'test-token', get_option( 'gitsupport_github_token' ) );
		$this->assertEquals( 'openai', get_option( 'gitsupport_ai_provider' ) );
	}

	/**
	 * Test metrics GET endpoint unauthorized.
	 */
	public function test_get_metrics_unauthorized() {
		$request = new WP_REST_Request( 'GET', '/gitsupport/v1/metrics' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test metrics GET endpoint authorized.
	 */
	public function test_get_metrics_authorized() {
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$request = new WP_REST_Request( 'GET', '/gitsupport/v1/metrics' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'total_tickets', $data );
		$this->assertArrayHasKey( 'open_tickets', $data );
	}
}
