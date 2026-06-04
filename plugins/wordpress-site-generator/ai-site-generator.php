<?php
/**
 * Plugin Name: AI Site Generator
 * Description: Generate complete WordPress sites using AI and Pexels.
 * Version: 1.0.0
 * Author: Antigravity
 * Text Domain: ai-site-generator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define Constants
define( 'AI_SITE_GEN_PATH', plugin_dir_path( __FILE__ ) );
define( 'AI_SITE_GEN_URL', plugin_dir_url( __FILE__ ) );

// Composer Autoloader
if ( file_exists( AI_SITE_GEN_PATH . 'vendor/autoload.php' ) ) {
	require_once AI_SITE_GEN_PATH . 'vendor/autoload.php';
}

// Include Classes
require_once AI_SITE_GEN_PATH . 'includes/class-ai-generator.php';
require_once AI_SITE_GEN_PATH . 'includes/class-mcp-client.php';
require_once AI_SITE_GEN_PATH . 'includes/class-content-manager.php';
require_once AI_SITE_GEN_PATH . 'admin/class-admin-ui.php';

// Initialize Plugin
if ( ! function_exists( 'ai_site_gen_init' ) ) {
	function ai_site_gen_init() {
		new Ai_Site_Gen_Admin_UI();
	}
	add_action( 'plugins_loaded', 'ai_site_gen_init' );
}
