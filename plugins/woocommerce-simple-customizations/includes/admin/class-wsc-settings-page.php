<?php
/**
 * WooCommerce Settings Page Class
 *
 * @package WSC
 */

defined( 'ABSPATH' ) || exit;

class WSC_Settings_Page extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'wsc_settings';
		$this->label = __( 'Simple Customizations', 'woocommerce-simple-customizations' );

		parent::__construct();

		// Enqueue scripts
		add_action( 'woocommerce_admin_field_wsc_react_root', array( $this, 'render_react_root' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			'' => __( 'General', 'woocommerce-simple-customizations' ),
		);
		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}

	/**
	 * Get settings array.
	 *
	 * @param string $current_section Current section name.
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {
		$settings = array(
			array(
				'title' => __( 'Simple Customizations', 'woocommerce-simple-customizations' ),
				'type'  => 'title',
				'desc'  => '',
				'id'    => 'wsc_settings_section',
			),
			array(
				'type' => 'wsc_react_root',
				'id'   => 'wsc_react_root_field',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wsc_settings_section_end',
			),
		);
		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );
	}

	/**
	 * Render the React Root element.
	 */
	public function render_react_root() {
		?>
		<div id="wsc-settings-root"></div>
		<?php
	}

	/**
	 * Enqueue scripts.
	 * 
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		global $current_tab;

		// Check if we are on WooCommerce Settings -> Simple Customizations
		if ( 'woocommerce_page_wc-settings' !== $hook || 'wsc_settings' !== $current_tab ) {
			return;
		}

		$asset_file = include( WSC_PLUGIN_DIR . 'build/index.asset.php' );

		wp_enqueue_script(
			'wsc-settings-app',
			WSC_PLUGIN_URL . 'build/index.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		wp_enqueue_style(
			'wsc-settings-style',
			WSC_PLUGIN_URL . 'build/index.css',
			array( 'wp-components' ),
			$asset_file['version']
		);

		wp_localize_script(
			'wsc-settings-app',
			'wscSettings',
			array(
				'apiUrl' => esc_url_raw( rest_url( 'wsc/v1/settings' ) ),
				'nonce'  => wp_create_nonce( 'wp_rest' ),
			)
		);
	}
}

return new WSC_Settings_Page();
