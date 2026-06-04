<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WSCH_Core {

    public function run() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->load_modules();
    }

    private function load_dependencies() {
        require_once WSCH_PLUGIN_DIR . 'includes/admin/class-wsch-settings-page.php';
        require_once WSCH_PLUGIN_DIR . 'includes/api/class-wsch-rest-controller.php';
    }

    private function define_admin_hooks() {
        $settings_page = new WSCH_Settings_Page();
        add_action( 'admin_menu', array( $settings_page, 'add_plugin_page' ) );
        add_action( 'admin_enqueue_scripts', array( $settings_page, 'enqueue_scripts' ) );

        $rest_controller = new WSCH_REST_Controller();
        add_action( 'rest_api_init', array( $rest_controller, 'register_routes' ) );
    }

    private function load_modules() {
        // Load independent modules here
        require_once WSCH_PLUGIN_DIR . 'includes/modules/contact-icons/class-wsch-contact-icons.php';
        new WSCH_Contact_Icons();
    }
}
