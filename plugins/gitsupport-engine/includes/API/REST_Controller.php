<?php
namespace GitSupport\Engine\API;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Response;
use WP_Error;
use GitSupport\Engine\Database;

/**
 * REST API Controller class.
 */
class REST_Controller extends WP_REST_Controller {

	/**
	 * Namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'gitsupport/v1';

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		// GET /settings
		register_rest_route(
			$this->namespace,
			'/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			)
		);

		// POST /settings
		register_rest_route(
			$this->namespace,
			'/settings',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_settings' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			)
		);

		// GET /metrics
		register_rest_route(
			$this->namespace,
			'/metrics',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_metrics' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
			)
		);

		// POST /inbound
		register_rest_route(
			$this->namespace,
			'/inbound',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'handle_inbound' ),
					'permission_callback' => array( $this, 'check_inbound_permissions' ),
				),
			)
		);

		// POST /github-comment
		register_rest_route(
			$this->namespace,
			'/github-comment',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'handle_github_comment' ),
					'permission_callback' => '__return_true', // signature verification happens in the handler
				),
			)
		);
	}

	/**
	 * Check admin permissions for settings & metrics.
	 *
	 * @return bool|WP_Error
	 */
	public function check_admin_permissions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'rest_forbidden', __( 'You do not have permission to access this endpoint.', 'gitsupport-engine' ), array( 'status' => 403 ) );
		}
		return true;
	}

	/**
	 * Check permissions for inbound email API.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return bool|WP_Error
	 */
	public function check_inbound_permissions( $request ) {
		$secret = get_option( 'gitsupport_inbound_secret', '' );
		if ( empty( $secret ) ) {
			return new WP_Error( 'rest_forbidden', __( 'Inbound secret is not configured.', 'gitsupport-engine' ), array( 'status' => 403 ) );
		}

		// Check Authorization or custom header.
		$auth_header = $request->get_header( 'Authorization' );
		$token       = '';
		if ( $auth_header && preg_match( '/Bearer\s+(.*)$/i', $auth_header, $matches ) ) {
			$token = $matches[1];
		} else {
			$token = $request->get_header( 'X-Inbound-Token' );
		}

		if ( empty( $token ) ) {
			$token = $request->get_param( 'secret' );
		}

		if ( hash_equals( $secret, $token ) ) {
			return true;
		}

		return new WP_Error( 'rest_forbidden', __( 'Invalid inbound secret.', 'gitsupport-engine' ), array( 'status' => 403 ) );
	}

	/**
	 * Get Settings.
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings() {
		$settings = array(
			'github_api_token'       => get_option( 'gitsupport_github_token', '' ),
			'github_repo_owner'      => get_option( 'gitsupport_github_owner', '' ),
			'github_repo_name'       => get_option( 'gitsupport_github_repo', '' ),
			'github_webhook_secret'  => get_option( 'gitsupport_github_webhook_secret', '' ),
			'inbound_email_secret'   => get_option( 'gitsupport_inbound_secret', '' ),
			'ai_api_key'             => get_option( 'gitsupport_ai_api_key', '' ),
			'ai_provider'            => get_option( 'gitsupport_ai_provider', 'gemini' ),
		);

		return new WP_REST_Response( $settings, 200 );
	}

	/**
	 * Save Settings.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public function save_settings( $request ) {
		$params = $request->get_json_params();

		if ( isset( $params['github_api_token'] ) ) {
			update_option( 'gitsupport_github_token', sanitize_text_field( $params['github_api_token'] ) );
		}
		if ( isset( $params['github_repo_owner'] ) ) {
			update_option( 'gitsupport_github_owner', sanitize_text_field( $params['github_repo_owner'] ) );
		}
		if ( isset( $params['github_repo_name'] ) ) {
			update_option( 'gitsupport_github_repo', sanitize_text_field( $params['github_repo_name'] ) );
		}
		if ( isset( $params['github_webhook_secret'] ) ) {
			update_option( 'gitsupport_github_webhook_secret', sanitize_text_field( $params['github_webhook_secret'] ) );
		}
		if ( isset( $params['inbound_email_secret'] ) ) {
			update_option( 'gitsupport_inbound_secret', sanitize_text_field( $params['inbound_email_secret'] ) );
		}
		if ( isset( $params['ai_api_key'] ) ) {
			update_option( 'gitsupport_ai_api_key', sanitize_text_field( $params['ai_api_key'] ) );
		}
		if ( isset( $params['ai_provider'] ) ) {
			update_option( 'gitsupport_ai_provider', sanitize_text_field( $params['ai_provider'] ) );
		}

		return new WP_REST_Response( array( 'success' => true, 'message' => __( 'Settings saved successfully.', 'gitsupport-engine' ) ), 200 );
	}

	/**
	 * Get metrics.
	 *
	 * @return WP_REST_Response
	 */
	public function get_metrics() {
		$metrics = Database::get_metrics();
		return new WP_REST_Response( $metrics, 200 );
	}

	/**
	 * Handle inbound email.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_inbound( $request ) {
		$handler = new Inbound_Email_Handler();
		return $handler->handle( $request );
	}

	/**
	 * Handle GitHub webhook comment.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_github_comment( $request ) {
		$handler = new Github_Webhook_Handler();
		return $handler->handle( $request );
	}
}
