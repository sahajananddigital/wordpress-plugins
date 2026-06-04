<?php
/**
 * Admin Class.
 */

defined('ABSPATH') || exit;

class WP_Event_Manager_Admin
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_admin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Register Admin Page.
     */
    public function register_admin_page()
    {
        add_menu_page(
            __('Event Manager', 'wp-event-manager'),
            __('Event Manager', 'wp-event-manager'),
            'manage_options',
            'wp-event-manager',
            [$this, 'render_admin_page'],
            'dashicons-calendar-alt',
            25
        );

        add_submenu_page(
            'wp-event-manager',
            __('Settings', 'wp-event-manager'),
            __('Settings', 'wp-event-manager'),
            'manage_options',
            'wp-event-manager-settings',
            [$this, 'render_admin_page'] // React will handle routing
        );
    }

    /**
     * Render Admin Page (React Root).
     */
    public function render_admin_page()
    {
        echo '<div id="wp-event-manager-app">Loading Event Manager...</div>';
    }

    /**
     * Enqueue Admin Assets.
     */
    public function enqueue_admin_assets($hook)
    {
        if ('toplevel_page_wp-event-manager' !== $hook && 'event-manager_page_wp-event-manager-settings' !== $hook) {
            return;
        }

        $script_path = WP_EVENT_MANAGER_ABSPATH . 'build/index.asset.php';
        if (!file_exists($script_path)) {
            return;
        }

        $asset_file = include $script_path;

        wp_enqueue_script(
            'wp-event-manager-app',
            WP_EVENT_MANAGER_URL . 'build/index.js',
            $asset_file['dependencies'],
            $asset_file['version'],
            true
        );

        wp_enqueue_style(
            'wp-event-manager-style',
            WP_EVENT_MANAGER_URL . 'build/index.css',
            ['wp-components'],
            WP_EVENT_MANAGER_VERSION
        );

        wp_localize_script('wp-event-manager-app', 'wpEventManagerSettings', [
            'apiUrl' => rest_url('event-manager/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }
}

new WP_Event_Manager_Admin();
