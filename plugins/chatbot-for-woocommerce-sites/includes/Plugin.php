<?php

namespace WcChatbot;

/**
 * Core plugin class.
 *
 * Coordinates hooks and loads modules.
 */
class Plugin {

	/**
	 * @var Admin\Settings Settings module instance.
	 */
	protected $settings;

	/**
	 * @var Api\Rest_Endpoints API module instance.
	 */
	protected $api;

	/**
	 * @var Frontend\Scripts Frontend module instance.
	 */
	protected $frontend;

	/**
	 * Run the plugin logic.
	 */
	public function run() {
		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 */
	private function load_dependencies() {
		// Settings module.
		$this->settings = new Admin\Settings();
        
        // API module.
        $this->api = new Api\Rest_Endpoints();
        
        // Frontend module.
        $this->frontend = new Frontend\Scripts();
	}

	/**
	 * Register all of the hooks related to the admin area functionality.
	 */
	private function define_admin_hooks() {
		add_action( 'admin_menu', array( $this->settings, 'add_plugin_admin_menu' ) );
		add_action( 'admin_init', array( $this->settings, 'register_settings' ) );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality.
	 */
	private function define_public_hooks() {
        // Only load frontend scripts if the chatbot is enabled.
        if ( 'yes' === get_option( 'wc_chatbot_enabled', 'no' ) ) {
		    add_action( 'wp_enqueue_scripts', array( $this->frontend, 'enqueue_scripts' ) );
            add_action( 'wp_footer', array( $this->frontend, 'render_chatbot_html' ) );
        }
        
        // Register API endpoints.
        add_action( 'rest_api_init', array( $this->api, 'register_routes' ) );
	}
}
