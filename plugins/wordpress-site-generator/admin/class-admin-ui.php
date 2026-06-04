<?php

if ( ! class_exists( 'Ai_Site_Gen_Admin_UI' ) ) {
	class Ai_Site_Gen_Admin_UI {

		private $ai_generator;
		private $content_manager;

		public function __construct() {
			$this->ai_generator = new Ai_Site_Gen_Generator();
			$this->content_manager = new Ai_Site_Gen_Content_Manager();

			add_action( 'admin_menu', array( $this, 'register_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_action( 'wp_ajax_ai_site_gen_generate_plan', array( $this, 'handle_generate_plan' ) );
			add_action( 'wp_ajax_ai_site_gen_finalize', array( $this, 'handle_finalize' ) );
			add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		}

		public function register_menu() {
			add_menu_page(
				'AI Site Generator',
				'AI Site Gen',
				'manage_options',
				'ai-site-generator',
				array( $this, 'render_page' ),
				'dashicons-superhero',
				60
			);
		}

		public function register_rest_routes() {
			register_rest_route( 'ai-site-gen/v1', '/settings', array(
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'get_settings' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					}
				),
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'update_settings' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					}
				),
			) );
		}

		public function get_settings() {
			return rest_ensure_response( array(
				'mcp_endpoint' => get_option( 'ai_site_gen_mcp_endpoint', 'http://127.0.0.1:11434/v1/chat/completions' ),
			) );
		}

		public function update_settings( $request ) {
			$endpoint = $request->get_param( 'mcp_endpoint' );
			if ( $endpoint !== null ) {
				update_option( 'ai_site_gen_mcp_endpoint', esc_url_raw( $endpoint ) );
			}
			return rest_ensure_response( array( 'success' => true ) );
		}

		public function enqueue_assets( $hook ) {
			if ( 'toplevel_page_ai-site-generator' !== $hook ) {
				return;
			}

			$asset_file = include( AI_SITE_GEN_PATH . 'build/index.asset.php' );

			wp_enqueue_script(
				'ai-site-gen-script',
				AI_SITE_GEN_URL . 'build/index.js',
				$asset_file['dependencies'],
				$asset_file['version'],
				true
			);

			wp_enqueue_style(
				'ai-site-gen-style',
				AI_SITE_GEN_URL . 'build/style-index.css',
				array( 'wp-components' ),
				$asset_file['version']
			);
			
			wp_localize_script( 'ai-site-gen-script', 'aiSiteGen', array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ai_site_gen_nonce' )
			));
		}

		public function render_page() {
			echo '<div id="ai-site-generator-root"></div>';
		}

		public function handle_generate_plan() {
			try {
				check_ajax_referer( 'ai_site_gen_nonce', 'nonce' );

				if ( ! current_user_can( 'manage_options' ) ) {
					throw new Exception( 'Permission denied' );
				}

				$prompt = isset($_POST['prompt']) ? sanitize_text_field( wp_unslash($_POST['prompt']) ) : '';
				
				$extra_context = array(
					'email'         => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
					'phone'         => isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '',
					'address'       => isset($_POST['address']) ? sanitize_text_field(wp_unslash($_POST['address'])) : '',
					'services'      => isset($_POST['services']) ? sanitize_textarea_field(wp_unslash($_POST['services'])) : '',
					'reference_url' => isset($_POST['reference_url']) ? esc_url_raw($_POST['reference_url']) : '',
				);

				if ( empty( $prompt ) ) {
					throw new Exception( 'Prompt is required' );
				}

				// Generate JSON Plan (Strategy + Content)
				$plan = $this->ai_generator->generate_site( $prompt, $extra_context );

				if ( is_wp_error( $plan ) ) {
					throw new Exception( $plan->get_error_message() );
				}

				wp_send_json_success( array(
					'message' => 'Site plan generated!',
					'plan' => $plan
				) );

			} catch ( Throwable $e ) {
				wp_send_json_error( array('message' => 'Error: ' . $e->getMessage()) );
			}
		}

		public function handle_finalize() {
			try {
				check_ajax_referer( 'ai_site_gen_nonce', 'nonce' );

				if ( ! current_user_can( 'manage_options' ) ) {
					throw new Exception( 'Permission denied' );
				}

				$plan_json = isset($_POST['plan']) ? wp_unslash($_POST['plan']) : '';
				$plan = json_decode($plan_json, true);

				if ( empty( $plan ) || !is_array($plan) ) {
					throw new Exception( 'Invalid site plan received.' );
				}

				// Create Posts from reviewed plan
				$created_pages = $this->content_manager->create_pages( $plan );

				$url = !empty($created_pages) ? $created_pages[0]['link'] : home_url();

				wp_send_json_success( array(
					'message' => 'Site finalized successfully!',
					'pages' => $created_pages,
					'url' => $url
				) );

			} catch ( Throwable $e ) {
				wp_send_json_error( array('message' => 'Error: ' . $e->getMessage()) );
			}
		}
	}
}
?>
