<?php
/**
 * Plugin Name:       WooCommerce Chatbot
 * Plugin URI:        https://example.com/wc-chatbot
 * Description:       A secure, robust chatbot for WooCommerce that assists with product suggestions and order tracking.
 * Version:           1.0.0
 * Author:            Your Name
 * Author URI:        https://example.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wc-chatbot
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 *
 * @package           WcChatbot
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define plugin constants.
define( 'WC_CHATBOT_VERSION', '1.0.0' );
define( 'WC_CHATBOT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WC_CHATBOT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WC_CHATBOT_PLUGIN_FILE', __FILE__ );

// Autoload dependencies if Composer was used.
if ( file_exists( WC_CHATBOT_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once WC_CHATBOT_PLUGIN_DIR . 'vendor/autoload.php';
} else {
    // Basic fallback autoloader if composer is not run.
    spl_autoload_register(function ($class) {
        $prefix = 'WcChatbot\\';
        $base_dir = WC_CHATBOT_PLUGIN_DIR . 'includes/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    });
}

/**
 * Check if WooCommerce is active before starting the plugin.
 */
function wc_chatbot_check_dependencies() {
	if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
		// If multisite, check network active plugins.
		if ( is_multisite() ) {
			$network_active_plugins = get_site_option( 'active_sitewide_plugins', array() );
			if ( array_key_exists( 'woocommerce/woocommerce.php', $network_active_plugins ) ) {
				return true;
			}
		}
		return false;
	}
	return true;
}

/**
 * Initialize the plugin.
 */
function run_wc_chatbot() {
	if ( wc_chatbot_check_dependencies() ) {
		$plugin = new \WcChatbot\Plugin();
		$plugin->run();
	} else {
        add_action( 'admin_notices', 'wc_chatbot_missing_wc_notice' );
    }
}

/**
 * Admin notice if WooCommerce is not active.
 */
function wc_chatbot_missing_wc_notice() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php esc_html_e( 'WooCommerce Chatbot requires WooCommerce to be installed and active.', 'wc-chatbot' ); ?></p>
    </div>
    <?php
}

// Start the plugin.
add_action( 'plugins_loaded', 'run_wc_chatbot', 10 );
