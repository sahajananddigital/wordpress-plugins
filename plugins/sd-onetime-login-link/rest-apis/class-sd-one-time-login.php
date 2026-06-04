<?php
/**
 * One-Time Login Link REST API
 * @version 1.0.0
 * Author : Sahajanand Digital
 */

class SD_One_Time_Login {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_api_routes' ) );
		add_action( 'init', array( $this, 'handle_one_time_login' ) );
	}

	public function register_api_routes() {
		register_rest_route(
			'sd-onetime-login',
			'/get-one-time-login-link',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'generate_login_link' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'user_id' => array(
						'required'          => true,
						'validate_callback' => function( $param ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);
	}

	public function check_permissions() {
		// Use standard WP capability check.
		// 'manage_options' is for admins. We might want something broader if a bot with lower perms calls it,
        // but typically a bot would use an Application Password associated with an admin or specific service account.
		return current_user_can( 'manage_options' );
	}

	public function generate_login_link( $request ) {
		$user_id = $request->get_param( 'user_id' );
		$user     = get_userdata( $user_id );

		if ( ! $user ) {
			return new WP_Error( 'invalid_user', 'user not found', array( 'status' => 404 ) );
		}

		// Check for existing token.
		$token = get_user_meta( $user_id, '_user_one_time_token', true );

		if ( ! $token ) {
			// Generate new token.
			$token = bin2hex( random_bytes( 32 ) );
			update_user_meta( $user_id, '_user_one_time_token', $token );
		}

		$login_url = add_query_arg(
			array(
				'user_login_token' => $token,
			),
			site_url( '/' )
		);

		return rest_ensure_response(
			array(
				'user_id'  => $user_id,
				'login_url' => $login_url,
				'message'   => 'Login link generated successfully.',
			)
		);
	}

	public function handle_one_time_login() {
		if ( ! isset( $_GET['user_login_token'] ) ) {
			return;
		}

		$token = sanitize_text_field( $_GET['user_login_token'] );

		// Find user with this token.
		$users = get_users(
			array(
				'meta_key'   => '_user_one_time_token',
				'meta_value' => $token,
				'number'     => 1,
			)
		);

		// Determine redirect URL.
		$redirect_to = home_url( '/calendar' );
		if ( isset( $_GET['redirect_to'] ) ) {
			$redirect_to = esc_url( $_GET['redirect_to'] );
		}

		if ( ! empty( $users ) ) {
			$user = $users[0];

			// Log the user in.
			wp_set_auth_cookie( $user->ID );

			// Invalidate the token.
			delete_user_meta( $user->ID, '_user_one_time_token' );

			wp_safe_redirect( $redirect_to );
			exit;
		}

		// Token is invalid or expired.
		if ( is_user_logged_in() ) {
			// If already logged in, just redirect to the target page.
			wp_safe_redirect( $redirect_to );
			exit;
		}

		wp_die( 'Invalid or expired login link.', 'Login Error', array( 'response' => 403 ) );
	}
}

SD_One_Time_Login::get_instance();
