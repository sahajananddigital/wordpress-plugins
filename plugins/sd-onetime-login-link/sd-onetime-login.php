<?php
/**
 * Plugin Name: One Time Login Link 
 * Description: Custom plugin for One Time Login.
 * Version: 1.0.0
 * Author: Sahajanand Digital
 * Text Domain: One Time Login Link
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'SAHAJANAND_OTL_VERSION', '1.0.0' );
define( 'SAHAJANAND_OTL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SAHAJANAND_OTL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once SAHAJANAND_OTL_PLUGIN_DIR . 'rest-apis/class-sd-one-time-login.php';