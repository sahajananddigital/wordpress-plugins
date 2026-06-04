<?php
/**
 * Plugin Name: WooCommerce Simple Customizations
 * Plugin URI:  https://github.com/sahajananddigital/woocommerce-simple-customizations
 * Description: A modular plugin to add simple customizations to WooCommerce, starting with Cart Limits.
 * Version:     1.0.0
 * Author:      Sahajanand Digital
 * Author URI:  https://sahajananddigital.in
 * Text Domain: woocommerce-simple-customizations
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Tags: woocommerce, cart limits, customizations
 * Requires Plugins: WooCommerce
 */

defined( 'ABSPATH' ) || exit;

// Define Constants
define( 'WSC_VERSION', '1.0.0' );
define( 'WSC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WSC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WSC_PLUGIN_FILE', __FILE__ );

// Autoload Defaults
if ( file_exists( WSC_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once WSC_PLUGIN_DIR . 'vendor/autoload.php';
}

// Core Loader
require_once WSC_PLUGIN_DIR . 'includes/class-wsc-core.php';

// Initialize
function wsc_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function() {
			?>
			<div class="notice notice-error">
				<p><?php esc_html_e( 'WooCommerce Simple Customizations requires WooCommerce to be installed and active.', 'woocommerce-simple-customizations' ); ?></p>
			</div>
			<?php
		} );
		return;
	}

	if ( class_exists( 'WSC_Core' ) ) {
		\WSC_Core::instance();
	}
}
add_action( 'plugins_loaded', 'wsc_init' );
