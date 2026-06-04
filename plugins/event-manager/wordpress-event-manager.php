<?php
/**
 * Plugin Name: WordPress Event Manager
 * Description: A complete Event Management system with Gutenberg-style backend.
 * Version: 1.1.0
 * Author: Sahajanand Digital
 * License: GPL-2.0+
 */

defined('ABSPATH') || exit;

if (!defined('WP_EVENT_MANAGER_PLUGIN_FILE')) {
    define('WP_EVENT_MANAGER_PLUGIN_FILE', __FILE__);
}
if (!defined('WP_EVENT_MANAGER_ABSPATH')) {
    define('WP_EVENT_MANAGER_ABSPATH', plugin_dir_path(__FILE__));
}

// Ensure Install class is loaded
require_once dirname(__FILE__) . '/includes/class-wp-event-manager-install.php';

// Check DB updates
add_action('admin_init', ['WP_Event_Manager_Install', 'update_db_check']);


// Include the main class.
if (!class_exists('WP_Event_Manager')) {
    include_once dirname(__FILE__) . '/includes/class-wp-event-manager.php';
}

/**
 * Main instance of WP_Event_Manager.
 *
 * @return WP_Event_Manager
 */
function wp_event_manager()
{
    return WP_Event_Manager::instance();
}

// Global for backwards compatibility.
$GLOBALS['wp_event_manager'] = wp_event_manager();
