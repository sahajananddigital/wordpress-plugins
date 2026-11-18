<?php
/**
 * Plugin Name: GitSupport Engine
 * Plugin URI: https://github.com/sahajananddigital/wordpress-plugins
 * Description: Scaffold and implement core architecture for the GitSupport Engine plugin.
 * Version: 1.0.0
 * Author: Staff WordPress Engineer
 * Author URI: https://github.com/sahajananddigital
 * License: GPL2
 * Text Domain: gitsupport-engine
 * Domain Path: /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define Constants.
define( 'GITSUPPORT_ENGINE_VERSION', '1.0.0' );
define( 'GITSUPPORT_ENGINE_PATH', plugin_dir_path( __FILE__ ) );
define( 'GITSUPPORT_ENGINE_URL', plugin_dir_url( __FILE__ ) );

// Initialize Autoloader.
require_once GITSUPPORT_ENGINE_PATH . 'includes/Autoloader.php';
\GitSupport\Engine\Autoloader::register();

// Register Activation Hook.
register_activation_hook( __FILE__, array( '\GitSupport\Engine\Database', 'activate' ) );

// Initialize the main plugin.
function gitsupport_engine_run() {
	\GitSupport\Engine\Plugin::get_instance();
}
add_action( 'plugins_loaded', 'gitsupport_engine_run' );
