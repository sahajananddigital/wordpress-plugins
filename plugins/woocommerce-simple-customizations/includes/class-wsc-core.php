<?php
/**
 * Core Class
 *
 * @package WSC
 */

defined( 'ABSPATH' ) || exit;

class WSC_Core {

	/**
	 * The single instance of the class.
	 *
	 * @var WSC_Core
	 */
	protected static $_instance = null;

	/**
	 * Main WSC_Core Instance.
	 *
	 * @return WSC_Core
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required core files.
	 */
	public function includes() {
		require_once WSC_PLUGIN_DIR . 'includes/api/class-wsc-rest-controller.php';
		require_once WSC_PLUGIN_DIR . 'includes/class-wsc-condition-evaluator.php';
		
		// Load Modules
		$this->load_modules();
	}

	/**
	 * Load Modules.
	 */
	private function load_modules() {
		$modules = glob( WSC_PLUGIN_DIR . 'includes/modules/*/class-*.php' );
		foreach ( $modules as $module ) {
			require_once $module;
		}
	}

	/**
	 * Hook into actions and filters.
	 */
	public function init_hooks() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings_page' ) );
	}

	/**
	 * Add Settings Page.
	 */
	public function add_settings_page( $settings ) {
		$settings[] = include WSC_PLUGIN_DIR . 'includes/admin/class-wsc-settings-page.php';
		return $settings;
	}

	/**
	 * Register REST Routes.
	 */
	public function register_rest_routes() {
		$controller = new WSC_REST_Controller();
		$controller->register_routes();
	}
}
