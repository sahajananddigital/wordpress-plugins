<?php
/**
 * Plugin Name: WordPress Simple Customise Helper
 * Description: A modular helper plugin for general WordPress customizations with a React Admin UI.
 * Plugin URI: https://github.com/sahajananddigital/wordpress-simple-customise-helper
 * GitHub Plugin URI: https://github.com/sahajananddigital/wordpress-simple-customise-helper
 * Primary Branch: main
 * Release Asset: true
 * Version: 1.0.0
 * Author: Sahajanand Digital
 * Author URI: https://sahajananddigital.in/
 * Text Domain: wordpress-simple-customise-helper
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WSCH_VERSION', '1.0.0' );
define( 'WSCH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WSCH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WSCH_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once WSCH_PLUGIN_DIR . 'includes/class-wsch-core.php';

function wsch_init() {
    $plugin = new WSCH_Core();
    $plugin->run();
}
add_action( 'plugins_loaded', 'wsch_init' );
