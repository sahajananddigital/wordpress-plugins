<?php
namespace GitSupport\Engine;

use GitSupport\Engine\API\REST_Controller;

/**
 * Main Plugin class.
 */
class Plugin {

	/**
	 * Instance of this class.
	 *
	 * @var Plugin
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 */
	private function init_hooks() {
		// Admin hooks.
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// REST API hook.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register Admin Menu Page.
	 */
	public function register_admin_menu() {
		add_management_page(
			__( 'GitSupport Dashboard', 'gitsupport-engine' ),
			__( 'GitSupport Engine', 'gitsupport-engine' ),
			'manage_options',
			'gitsupport-engine',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Render the admin page.
	 */
	public function render_admin_page() {
		echo '<div id="gitsupport-dashboard-root"></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Enqueue Admin Assets.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'tools_page_gitsupport-engine' !== $hook ) {
			return;
		}

		$asset_file = GITSUPPORT_ENGINE_PATH . 'build/index.asset.php';
		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_script(
			'gitsupport-engine-admin',
			GITSUPPORT_ENGINE_URL . 'build/index.js',
			$assets['dependencies'],
			$assets['version'],
			true
		);

		wp_enqueue_style(
			'gitsupport-engine-admin-style',
			GITSUPPORT_ENGINE_URL . 'build/index.css',
			array( 'wp-components' ),
			GITSUPPORT_ENGINE_VERSION
		);

		// Localize Script for REST API and Nonce.
		wp_localize_script(
			'gitsupport-engine-admin',
			'gitSupportEngineData',
			array(
				'root'    => esc_url_raw( rest_url( 'gitsupport/v1' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'siteUrl' => esc_url( get_site_url() ),
			)
		);
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {
		$controller = new REST_Controller();
		$controller->register_routes();
	}
}
