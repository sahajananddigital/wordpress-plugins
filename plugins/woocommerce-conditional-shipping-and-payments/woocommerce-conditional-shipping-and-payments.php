<?php
/**
 * Plugin Name: WooCommerce Conditional Shipping and Payments
 * Description: Restrict shipping and payment methods based on conditions.
 * Version: 1.0.0
 * Author: Sahajanand Digital
 * Text Domain: woocommerce-conditional-shipping-and-payments
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package WooCommerceConditionalShipping
 */

namespace SahajanandDigital\WooCommerceConditionalShipping;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Autoloader.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Main plugin class.
 */
final class Plugin {

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Define constants.
	 */
	private function define_constants() {
		define( 'WC_CSP_VERSION', '1.0.0' );
		define( 'WC_CSP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		define( 'WC_CSP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		define( 'WC_CSP_BASENAME', plugin_basename( __FILE__ ) );
	}

	/**
	 * Include required files.
	 */
	private function includes() {
        // Will include core classes here via autoloader
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );
	}

    /**
     * On plugins loaded.
     */
    public function on_plugins_loaded() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        // Initialize components
        Admin\Settings::init();
        Conditions\Condition_Evaluator::init();
        
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            CLI\Commands::init();
        }

        // Initialize REST API
        add_action( 'rest_api_init', array( __CLASS__, 'init_rest_api' ) );
    }

    /**
     * Init REST API.
     */
    public static function init_rest_api() {
        API\Conditions_Controller::init();
    }
}

// Instantiate the plugin.
Plugin::get_instance();
