<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WSCH_Settings_Page {

    public function add_plugin_page() {
        add_menu_page(
            __( 'WP Customise', 'wordpress-simple-customise-helper' ),
            __( 'WP Customise', 'wordpress-simple-customise-helper' ),
            'manage_options',
            'wsch-settings',
            array( $this, 'create_admin_page' ),
            'dashicons-admin-tools',
            90
        );
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <div id="wsch-admin-app"></div>
        </div>
        <?php
    }

    public function enqueue_scripts( $hook ) {
        if ( 'toplevel_page_wsch-settings' !== $hook ) {
            return;
        }

        $asset_file = include( WSCH_PLUGIN_DIR . 'build/index.asset.php' );

        wp_enqueue_script(
            'wsch-admin-app',
            WSCH_PLUGIN_URL . 'build/index.js',
            $asset_file['dependencies'],
            $asset_file['version'],
            true
        );

        wp_enqueue_style(
            'wsch-admin-style',
            WSCH_PLUGIN_URL . 'build/index.css',
            array( 'wp-components' ),
            WSCH_PLUGIN_URL . 'build/index.css' // fallback version
        );
        
        wp_localize_script( 'wsch-admin-app', 'wschSettings', array(
            'root'  => esc_url_raw( rest_url() ),
            'nonce' => wp_create_nonce( 'wp_rest' )
        ) );
    }
}
