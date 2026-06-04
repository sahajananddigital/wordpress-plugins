<?php
/**
 * Label Printing Module
 *
 * @package WSC
 */

defined( 'ABSPATH' ) || exit;

class WSC_Label_Printing {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Add "Print Label" action to Order Actions
		add_filter( 'woocommerce_admin_order_actions', array( $this, 'add_print_label_action' ), 10, 2 );

		// Register AJAX handler
		add_action( 'wp_ajax_wsc_print_label', array( $this, 'handle_print_label' ) );
		
		// Enqueue scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		require_once dirname( __FILE__ ) . '/class-wsc-barcode-generator.php';
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || 'shop_order' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script( 
			'wsc-label-printing', 
			WSC_PLUGIN_URL . 'assets/js/admin-label-printing.js', 
			array( 'jquery' ), 
			'1.0.1',  // Bump version to bust cache
			true 
		);
	}

	/**
	 * Handle "Print Label" action.
	 */
	public function handle_print_label() {
		check_ajax_referer( 'wsc_print_label' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( __( 'You do not have permission to access this page.', 'woocommerce-simple-customizations' ) );
		}

		$order_id = isset( $_GET['order_id'] ) ? intval( $_GET['order_id'] ) : 0;
		$order    = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_die( __( 'Invalid order ID.', 'woocommerce-simple-customizations' ) );
		}

		$settings = get_option( 'wsc_settings', array() );
		$header   = ! empty( $settings['label_printing_header'] ) ? $settings['label_printing_header'] : get_bloginfo( 'name' );
		$size     = ! empty( $settings['label_printing_size'] ) ? $settings['label_printing_size'] : '4x6';

		$template = ! empty( $settings['label_printing_template'] ) ? $settings['label_printing_template'] : '';

		// Fallback Default
		if ( empty( $template ) ) {
			$template = '<div style="text-align: center; font-family: sans-serif;"><h1>{header}</h1><div style="margin: 20px 0; font-size: 16px;">{shipping_address}</div><div style="font-size: 12px;">Order #{order_number}</div></div>';
		}


		// Calculate Weight
		$total_weight = 0;
		foreach ( $order->get_items() as $item_id => $item ) {
			$product = $item->get_product();
			if ( $product && $product->has_weight() ) {
				$total_weight += $product->get_weight() * $item->get_quantity();
			}
		}
		$weight_unit = get_option( 'woocommerce_weight_unit' );

		// Items Summary
		$items_list = array();
		foreach ( $order->get_items() as $item ) {
			$items_list[] = $item->get_name() . ' x ' . $item->get_quantity();
		}
		$items_summary = implode( ', ', $items_list );

		// Return Address
		$base_country = WC()->countries->get_base_country();
		$base_state   = WC()->countries->get_base_state();
		$base_address = WC()->countries->get_base_address();
		$base_city    = WC()->countries->get_base_city();
		$base_postcode = WC()->countries->get_base_postcode();

		$return_address = implode( '<br>', array_filter( array(
			get_bloginfo( 'name' ),
			$base_address,
			$base_city . ' ' . $base_postcode,
			WC()->countries->states[ $base_country ][ $base_state ] ?? $base_state,
			WC()->countries->countries[ $base_country ] ?? $base_country,
		) ) );

		// Generate Barcodes
		$generator = new WSC_Barcode_Generator();
		$barcode_order = $generator->get_barcode_svg( $order->get_order_number(), 40 );
		$barcode_awb   = $generator->get_barcode_svg( 'AWB' . $order->get_id(), 50 ); // Dummy AWB logic

		// Placeholders
		$placeholders = array(
			'{header}'           => esc_html( $header ),
			'{shipping_address}' => wp_kses_post( $order->get_formatted_shipping_address() ),
			'{billing_address}'  => wp_kses_post( $order->get_formatted_billing_address() ),
			'{order_number}'     => esc_html( $order->get_order_number() ),
			'{order_id}'         => esc_html( $order->get_id() ),
			'{phone}'            => esc_html( $order->get_billing_phone() ),
			'{payment_method}'   => esc_html( $order->get_payment_method_title() ),
			'{order_total}'      => wp_kses_post( $order->get_formatted_order_total() ),
			'{weight}'           => esc_html( $total_weight . ' ' . $weight_unit ),
			'{items_summary}'    => esc_html( $items_summary ),
			'{return_address}'   => wp_kses_post( $return_address ),
			'{current_date}'     => date_i18n( get_option( 'date_format' ) ),
			'{barcode_order}'    => $barcode_order,
			'{barcode_awb}'      => $barcode_awb,
		);

		$content = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $template );

		// Simple CSS Based on Size
		$css = 'body { font-family: sans-serif; margin: 0; padding: 0; }';
		if ( $size === '4x6' ) {
			$css .= '@page { size: 4in 6in; margin: 0; } body { width: 4in; height: 6in; }';
		} elseif ( $size === 'a4' ) {
			$css .= '@page { size: A4; margin: 0; }';
		}

		// Prepare Allowed Tags including SVG
		$allowed_tags = wp_kses_allowed_html( 'post' );
		$allowed_tags['svg'] = array(
			'xmlns' => true,
			'viewbox' => true,
			'preserveaspectratio' => true,
			'style' => true,
			'width' => true,
			'height' => true,
		);
		$allowed_tags['path'] = array(
			'd' => true,
			'fill' => true,
		);
		$allowed_tags['g'] = array(
			'fill' => true,
		);
		$allowed_tags['rect'] = array(
			'x' => true,
			'y' => true,
			'width' => true,
			'height' => true,
			'fill' => true,
		);

		?>
		<!DOCTYPE html>
		<html>
		<head>
			<title><?php echo esc_html( __( 'Print Label', 'woocommerce-simple-customizations' ) ); ?></title>
			<style>
				<?php echo $css; ?>
			</style>
		</head>
		<body onload="window.print();">
			<?php echo wp_kses( $content, $allowed_tags ); ?>
		</body>
		</html>
		<?php
		exit;
	}

	/**
	 * Add "Print Label" action button to Order Actions.
	 *
	 * @param array    $actions Existing actions.
	 * @param WC_Order $order   Order object.
	 * @return array Modified actions.
	 */
	public function add_print_label_action( $actions, $order ) {
		$settings = get_option( 'wsc_settings', array() );
		if ( empty( $settings['label_printing_enabled'] ) ) {
			return $actions;
		}

		// Add custom action
		$actions['wsc_print_label'] = array(
			'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=wsc_print_label&order_id=' . $order->get_id() ), 'wsc_print_label' ),
			'name'   => __( 'Print Label', 'woocommerce-simple-customizations' ),
			'action' => 'view print-label', // Uses generic view icon for now
		);

		return $actions;
	}
}

new WSC_Label_Printing();
