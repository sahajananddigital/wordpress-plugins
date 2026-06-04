<?php
/**
 * Plugin Name: Multisite Subsite Admin User Editor (Edit Only)
 * Description: Allows subsite administrators to ONLY edit users in their subsite. They cannot create or delete users. Protects Super Admin accounts.
 * Version: 1.1.0
 * Author: Sahajanand Digital (adapted)
 * License: GPL-2.0+
 * Text Domain: multisite-subsite-admin-user-editor
 * Domain Path: /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Fallback PSR-4 Autoloader mapping to WordPress 'class-slug.php' naming structure
spl_autoload_register( function ( $class ) {
    $prefix = 'MultisiteSubsiteAdminUserEditor\\';
    $base_dir = __DIR__ . '/includes/';

    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }

    $relative_class = substr( $class, $len );
    $parts = explode( '\\', $relative_class );
    $class_name = array_pop( $parts );
    
    // Transform CamelCase to lowercase-with-dash (e.g. CapabilityManager to class-capability-manager.php)
    $class_file = 'class-' . strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', $class_name ) ) . '.php';
    $parts[] = $class_file;
    $file_path = $base_dir . implode( '/', $parts );

    if ( file_exists( $file_path ) ) {
        require_once $file_path;
    }
} );

/**
 * Retrieves the main instance of the plugin.
 *
 * @return \MultisiteSubsiteAdminUserEditor\Plugin
 */
function multisite_subsite_admin_user_editor() {
    return \MultisiteSubsiteAdminUserEditor\Plugin::get_instance();
}

// Boot the plugin.
add_action( 'plugins_loaded', function() {
    multisite_subsite_admin_user_editor()->boot();
} );
