<?php
/**
 * Product Inquiry Module
 *
 * @package WSC
 */

defined( 'ABSPATH' ) || exit;

class WSC_Product_Inquiry {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Replace Add to Cart Text
		add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'replace_add_to_cart_text' ) );
		add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'replace_add_to_cart_text' ) );

		// Modify Checkout
		add_filter( 'woocommerce_cart_needs_payment', array( $this, 'disable_payment_requirement' ), 1000, 2 );
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'restrict_payment_gateways' ) );
		add_filter( 'woocommerce_order_button_text', array( $this, 'replace_order_button_text' ) );
        add_filter( 'woocommerce_no_available_payment_methods_message', array( $this, 'suppress_no_payment_method_message' ) );
        add_action( 'woocommerce_after_checkout_validation', array( $this, 'remove_payment_validation_errors' ), 10, 2 );
        add_filter( 'woocommerce_order_needs_payment', array( $this, 'disable_order_payment_requirement' ), 10, 2 );
        add_filter( 'gettext', array( $this, 'replace_cart_text' ), 20, 3 );
        add_filter( 'gettext_with_context', array( $this, 'replace_text_with_context' ), 20, 4 );
        add_filter( 'woocommerce_button_proceed_to_checkout', array( $this, 'replace_proceed_to_checkout_text' ), 20 );
        add_filter( 'woocommerce_widget_shopping_cart_proceed_to_checkout_text', array( $this, 'replace_proceed_to_checkout_text' ), 20 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        
        // Thank You Page
        add_filter( 'woocommerce_endpoint_order-received_title', array( $this, 'replace_order_received_title' ) );
        add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'replace_order_received_text' ), 10, 2 );

        // Allow Inquiry for Priceless Products
        add_filter( 'woocommerce_is_purchasable', array( $this, 'make_products_purchasable' ), 10, 2 );
        add_filter( 'woocommerce_product_get_price', array( $this, 'force_price_for_cart' ), 10, 2 );
        add_filter( 'woocommerce_get_price_html', array( $this, 'hide_empty_price' ), 10, 2 );
		
		// Optional: Filter gettext for "Add to Cart" messages if needed, but button text filters should cover most cases.
	}

	/**
	 * Check if the module is enabled.
	 * 
	 * @return bool
	 */
	private function is_enabled() {
		$settings = get_option( 'wsc_settings', array() );
		return ! empty( $settings['product_inquiry_enabled'] );
	}

	/**
	 * Check if button replacement is enabled.
	 * 
	 * @return bool
	 */
	private function is_replace_button_enabled() {
		$settings = get_option( 'wsc_settings', array() );
		return ! empty( $settings['product_inquiry_replace_atc'] );
	}

	/**
	 * Get Custom Button Text.
	 * 
	 * @return string
	 */
	private function get_button_text() {
		$settings = get_option( 'wsc_settings', array() );
		return ! empty( $settings['product_inquiry_btn_text'] ) ? $settings['product_inquiry_btn_text'] : __( 'Add to Inquiry', 'woocommerce-simple-customizations' );
	}

	/**
	 * Replace "Add to Cart" text.
	 * 
	 * @param string $text Default text.
	 * @return string Modified text.
	 */
	public function replace_add_to_cart_text( $text ) {
		if ( ! $this->is_enabled() || ! $this->is_replace_button_enabled() ) {
			return $text;
		}

		return $this->get_button_text();
	}

	/**
	 * Disable Payment Requirement.
	 * 
	 * @param bool $needs_payment Default value.
	 * @param WC_Cart $cart Cart object.
	 * @return bool False if enabled.
	 */
	public function disable_payment_requirement( $needs_payment, $cart ) {
		if ( $this->is_enabled() ) {
			return false;
		}
		return $needs_payment;
	}

	/**
	 * Restrict Payment Gateways.
	 * 
	 * @param array $gateways Available gateways.
	 * @return array Modified gateways.
	 */
	public function restrict_payment_gateways( $gateways ) {
		if ( $this->is_enabled() ) {
			// If we want to be safe, we can leave COD or create a dummy one, 
			// but returning empty usually works fine when needs_payment is false.
			// However, sometimes WC requires at least one gateway to process checkout even if free.
			// Usually needs_payment = false is enough to skip the payment step.
			return array();
		}
		return $gateways;
	}

	/**
	 * Replace "Place Order" button text.
	 * 
	 * @param string $text Default text.
	 * @return string Modified text.
	 */
	public function replace_order_button_text( $text ) {
		if ( $this->is_enabled() ) {
            $settings = get_option( 'wsc_settings', array() );
			return ! empty( $settings['product_inquiry_order_btn_text'] ) ? $settings['product_inquiry_order_btn_text'] : __( 'Send Inquiry', 'woocommerce-simple-customizations' );
		}
		return $text;
	}

    /**
     * Suppress "No payment method provided" message.
     *
     * @param string $message Default message.
     * @return string Modified message.
     */
    public function suppress_no_payment_method_message( $message ) {
        if ( $this->is_enabled() ) {
            return ''; // Return empty string to suppress the message
        }
        return $message;
    }
    /**
     * Remove validation errors related to payment methods.
     *
     * @param array    $data   Posted data.
     * @param WP_Error $errors Validation errors.
     */
    public function remove_payment_validation_errors( $data, $errors ) {
        if ( ! $this->is_enabled() ) {
            return;
        }

        // List of error codes or messages to remove
        // WooCommerce core often adds errors without codes, or using generic codes.
        // We might need to look at the error messages or codes if available.
        // Common error codes for payment: 'payment_method_required'
        
        $codes_to_remove = array( 'payment_method_required' );
        
        foreach ( $codes_to_remove as $code ) {
            if ( $errors->get_error_message( $code ) ) {
                $errors->remove( $code );
            }
        }

        // Also iterate through all errors to find messages matching standard payment errors
        // This is a bit hacky but effective for legacy errors without codes
        $error_codes = $errors->get_error_codes();
        foreach ( $error_codes as $code ) {
            $messages = $errors->get_error_messages( $code );
            foreach ( $messages as $message ) {
                if ( 
                    strpos( $message, 'payment method' ) !== false || 
                    strpos( $message, 'Payment method' ) !== false 
                ) {
                    $errors->remove( $code );
                    break; 
                }
            }
        }
    }
    /**
     * Disable Order Payment Requirement.
     * 
     * @param bool $needs_payment Default value.
     * @param WC_Order $order Order object.
     * @return bool False if enabled.
     */
    public function disable_order_payment_requirement( $needs_payment, $order ) {
        if ( $this->is_enabled() ) {
            return false;
        }
        return $needs_payment;
    }
    /**
     * Rename "Cart" text to "Inquiry List" (or custom).
     *
     * @param string $translated_text Translated text.
     * @param string $text Original text.
     * @param string $domain Text domain.
     * @return string Modified text.
     */
    public function replace_cart_text( $translated_text, $text, $domain ) {
        if ( ! $this->is_enabled() ) {
            return $translated_text;
        }

        $settings = get_option( 'wsc_settings', array() );
        
        // Handle specific button texts regardless of domain
        switch ( $text ) {
            case 'Place Order':
            case 'Place order':
                return ! empty( $settings['product_inquiry_order_btn_text'] ) ? $settings['product_inquiry_order_btn_text'] : __( 'Send Inquiry', 'woocommerce-simple-customizations' );
            case 'Proceed to checkout':
            case 'Proceed to Checkout':
                return ! empty( $settings['product_inquiry_checkout_btn_text'] ) ? $settings['product_inquiry_checkout_btn_text'] : __( 'Proceed to Inquiry', 'woocommerce-simple-customizations' );
            case 'Order received':
                return __( 'Inquiry Received', 'woocommerce-simple-customizations' );
        }

        // Handle Cart specific texts (usually specific to woocommerce domain)
        if ( 'woocommerce' === $domain ) {
            $label = ! empty( $settings['product_inquiry_cart_label'] ) ? $settings['product_inquiry_cart_label'] : __( 'Inquiry List', 'woocommerce-simple-customizations' );
            
            switch ( $text ) {
                case 'Cart':
                case 'Shopping Cart':
                case 'View cart':
                    return $label;
                case 'Add to cart':
                    return $this->get_button_text();
            }
        }
        return $translated_text;
    }

    /**
     * Replace text with context.
     *
     * @param string $translated_text Translated text.
     * @param string $text Original text.
     * @param string $context Context.
     * @param string $domain Text domain.
     * @return string Modified text.
     */
    public function replace_text_with_context( $translated_text, $text, $context, $domain ) {
        return $this->replace_cart_text( $translated_text, $text, $domain );
    }

    /**
     * Replace "Proceed to checkout" button text.
     * 
     * @param string $html Button HTML.
     * @return string Modified HTML.
     */
    public function replace_proceed_to_checkout_text( $html ) {
        if ( ! $this->is_enabled() ) {
            return $html;
        }

        $settings = get_option( 'wsc_settings', array() );
        $text = ! empty( $settings['product_inquiry_checkout_btn_text'] ) ? $settings['product_inquiry_checkout_btn_text'] : __( 'Proceed to Inquiry', 'woocommerce-simple-customizations' );

        // Regex to replace text inside tag
        return preg_replace( '/(>)(.*?)(<\/)/', '$1' . $text . '$3', $html );
    }
    /**
     * Enqueue frontend styles.
     */
    public function enqueue_styles() {
        if ( ! $this->is_enabled() ) {
            return;
        }

        $settings = get_option( 'wsc_settings', array() );
        $checkout_btn_text = ! empty( $settings['product_inquiry_checkout_btn_text'] ) ? $settings['product_inquiry_checkout_btn_text'] : __( 'Proceed to Inquiry', 'woocommerce-simple-customizations' );
        $order_btn_text    = ! empty( $settings['product_inquiry_order_btn_text'] ) ? $settings['product_inquiry_order_btn_text'] : __( 'Send Inquiry', 'woocommerce-simple-customizations' );

        $custom_css = "
            /* Force Button Text Replacement via CSS to avoid React DOM conflicts */
            .wc-block-cart__submit-button,
            .wc-block-components-checkout-place-order-button,
            .wc-block-components-checkout-place-order-button .wc-block-components-button__text {
                position: relative;
                color: transparent !important; 
            }
            
            .wc-block-cart__submit-button::after {
                content: '" . esc_js( $checkout_btn_text ) . "';
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff; /* Assuming white text, ideally inherit but transparent makes it hard */
                color: var(--wp--preset--color--white, #ffffff);
            }

            .wc-block-components-checkout-place-order-button .wc-block-components-button__text::after {
                content: '" . esc_js( $order_btn_text ) . "';
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                color: var(--wp--preset--color--white, #ffffff);
            }
            
            /* Hide the original SVG or other elements if needed, but color:transparent mostly handles text */
        ";

        wp_add_inline_style( 'wp-block-library', $custom_css );
        // Also hook quite late to ensure we override others
        wp_register_style( 'wsc-inline-styles', false );
        wp_enqueue_style( 'wsc-inline-styles' );
        wp_add_inline_style( 'wsc-inline-styles', $custom_css );
    }

    /**
     * Replace "Order received" title.
     * 
     * @param string $title Original title.
     * @return string Modified title.
     */
    public function replace_order_received_title( $title ) {
        if ( ! $this->is_enabled() ) {
            return $title;
        }
        return __( 'Inquiry Received', 'woocommerce-simple-customizations' );
    }

    /**
     * Replace "Thank you. Your order has been received." text.
     * 
     * @param string $text Original text.
     * @param WC_Order $order Order object.
     * @return string Modified text.
     */
    public function replace_order_received_text( $text, $order ) {
        if ( ! $this->is_enabled() ) {
            return $text;
        }
        return __( 'Thank you. Your inquiry has been received.', 'woocommerce-simple-customizations' );
    }
    /**
     * Check if priceless inquiry is enabled.
     * 
     * @return bool
     */
    public function is_priceless_inquiry_enabled() {
        $settings = get_option( 'wsc_settings', array() );
        return isset( $settings['product_inquiry_allow_priceless'] ) && $settings['product_inquiry_allow_priceless'];
    }

    /**
     * Make products purchasable even if they don't have a price.
     * 
     * @param bool $purchasable Default value.
     * @param WC_Product $product Product object.
     * @return bool
     */
    public function make_products_purchasable( $purchasable, $product ) {
        if ( ! $this->is_enabled() || ! $this->is_priceless_inquiry_enabled() ) {
            return $purchasable;
        }
        return true;
    }

    /**
     * Force price to 0 if empty so it can be added to cart.
     * 
     * @param string $price Price.
     * @param WC_Product $product Product object.
     * @return string
     */
    public function force_price_for_cart( $price, $product ) {
        if ( ! $this->is_enabled() || ! $this->is_priceless_inquiry_enabled() ) {
            return $price;
        }

        if ( '' === $price || null === $price ) {
            return '0';
        }
        return $price;
    }

    /**
     * Hide "Free" or "₹0.00" if price was empty but forced to 0.
     * 
     * @param string $price_html Price HTML.
     * @param WC_Product $product Product object.
     * @return string
     */
    public function hide_empty_price( $price_html, $product ) {
        if ( ! $this->is_enabled() || ! $this->is_priceless_inquiry_enabled() ) {
            return $price_html;
        }

        // Check raw price directly from data to avoid our own filter if possible, 
        // or checks if it equals 0.
        // But `force_price_for_cart` might have already run.
        // We can check if the product's regular price is empty.
        
        if ( '' === $product->get_regular_price() && '' === $product->get_sale_price() ) {
            return ''; 
        }

        return $price_html;
    }
}
new WSC_Product_Inquiry();
