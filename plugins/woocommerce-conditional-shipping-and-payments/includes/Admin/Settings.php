<?php
/**
 * Admin Settings Class.
 *
 * @package WooCommerceConditionalShipping
 */

namespace SahajanandDigital\WooCommerceConditionalShipping\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings class.
 */
class Settings {

	/**
	 * Init.
	 */
	public static function init() {
		add_filter( 'woocommerce_settings_tabs_array', array( __CLASS__, 'add_settings_tab' ), 50 );
		add_action( 'woocommerce_settings_tabs_conditions', array( __CLASS__, 'settings_tab' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	/**
	 * Add a new settings tab to the WooCommerce settings tabs array.
	 *
	 * @param array $settings_tabs Array of WooCommerce settings tabs.
	 * @return array $settings_tabs Array of WooCommerce settings tabs.
	 */
	public static function add_settings_tab( $settings_tabs ) {
		$settings_tabs['conditions'] = __( 'Conditions', 'woocommerce-conditional-shipping-and-payments' );
		return $settings_tabs;
	}

	/**
	 * Output the settings tab.
	 */
	public static function settings_tab() {
		echo '<div id="wc-csp-admin-app"></div>';
	}

    /**
     * Enqueue scripts.
     */
    public static function enqueue_scripts() {
        // Only enqueue on our settings tab
        if ( ! isset( $_GET['page'] ) || 'wc-settings' !== $_GET['page'] || ! isset( $_GET['tab'] ) || 'conditions' !== $_GET['tab'] ) {
            return;
        }

        $asset_path = WC_CSP_PLUGIN_DIR . 'build/index.asset.php';

        if ( ! file_exists( $asset_path ) ) {
            return;
        }

        $asset_file = include $asset_path;

        wp_enqueue_script(
            'wc-csp-admin-app',
            WC_CSP_PLUGIN_URL . 'build/index.js',
            $asset_file['dependencies'],
            $asset_file['version'],
            true
        );

        wp_enqueue_style(
            'wc-csp-admin-app',
            WC_CSP_PLUGIN_URL . 'build/index.css',
            array( 'wp-components' ),
            $asset_file['version']
        );

        $payment_gateways = array();
        if ( class_exists( 'WC_Payment_Gateways' ) ) {
            $gateways = WC()->payment_gateways->payment_gateways();
            foreach ( $gateways as $gateway ) {
                if ( 'yes' === $gateway->enabled ) {
                    $payment_gateways[] = $gateway->id;
                }
            }
        }

        wp_localize_script( 'wc-csp-admin-app', 'wcCspSettings', array(
            'apiUrl' => esc_url_raw( rest_url( 'wc-csp/v1/conditions' ) ),
            'nonce'  => wp_create_nonce( 'wp_rest' ),
            'paymentMethods' => $payment_gateways,
        ) );
    }
}
