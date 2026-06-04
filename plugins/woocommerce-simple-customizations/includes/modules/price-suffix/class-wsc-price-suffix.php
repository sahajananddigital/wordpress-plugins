<?php
/**
 * Price Suffix Module
 *
 * @package WSC
 */

defined( 'ABSPATH' ) || exit;

class WSC_Price_Suffix {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Add Custom Field to Product General Tab
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_price_suffix_field' ) );
		
		// Save Custom Field
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_price_suffix_field' ) );
		
		// Filter Price HTML
		add_filter( 'woocommerce_get_price_html', array( $this, 'filter_price_html' ), 10, 2 );
	}

	/**
	 * Add "Price Suffix" field to General tab.
	 */
	public function add_price_suffix_field() {
		$settings = get_option( 'wsc_settings', array() );
		if ( empty( $settings['price_suffix_enabled'] ) ) {
			return;
		}

		echo '<div class="options_group">';

		woocommerce_wp_text_input(
			array(
				'id'          => '_wsc_price_suffix',
				'label'       => __( 'Price Suffix', 'woocommerce-simple-customizations' ),
				'placeholder' => __( 'e.g. per box', 'woocommerce-simple-customizations' ),
				'desc_tip'    => 'true',
				'description' => __( 'Enter text to display after the price.', 'woocommerce-simple-customizations' ),
			)
		);

		echo '</div>';
	}

	/**
	 * Save "Price Suffix" field.
	 *
	 * @param int $post_id Product ID.
	 */
	public function save_price_suffix_field( $post_id ) {
		$suffix = isset( $_POST['_wsc_price_suffix'] ) ? sanitize_text_field( $_POST['_wsc_price_suffix'] ) : '';
		update_post_meta( $post_id, '_wsc_price_suffix', $suffix );
	}

	/**
	 * Append suffix to price HTML.
	 *
	 * @param string     $price       Price HTML.
	 * @param WC_Product $product     Product object.
	 * @return string Modified price HTML.
	 */
	public function filter_price_html( $price, $product ) {
		$settings = get_option( 'wsc_settings', array() );
		if ( empty( $settings['price_suffix_enabled'] ) ) {
			return $price;
		}

		$suffix = get_post_meta( $product->get_id(), '_wsc_price_suffix', true );

		if ( ! empty( $suffix ) ) {
			$price .= ' <span class="wsc-price-suffix">' . esc_html( $suffix ) . '</span>';
		}

		return $price;
	}
}

new WSC_Price_Suffix();
