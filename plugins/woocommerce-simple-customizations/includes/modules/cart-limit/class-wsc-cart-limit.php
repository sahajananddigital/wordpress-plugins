<?php
/**
 * Cart Limit Module
 *
 * @package WSC
 */

defined( 'ABSPATH' ) || exit;

class WSC_Cart_Limit {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_check_cart_items', array( $this, 'check_cart_limits' ) );
		add_filter( 'woocommerce_add_to_cart_quantity', array( $this, 'force_quantity_on_add_to_cart' ), 10, 2 );
        add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_checkout_rules' ), 10, 2 );
        add_filter( 'woocommerce_store_api_cart_errors', array( $this, 'validate_store_api_cart' ), 10, 2 );
        add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'check_store_api_checkout' ), 10, 2 );
        add_action( 'woocommerce_proceed_to_checkout', array( $this, 'maybe_remove_checkout_button' ), 1 );
        add_filter( 'woocommerce_get_checkout_url', array( $this, 'disable_checkout_navigation' ), 10, 1 );
        add_action( 'template_redirect', array( $this, 'restrict_checkout_access' ) );
	}

	/**
	 * Force minimum quantity when adding to cart.
	 * 
	 * @param int $quantity Quantity being added.
	 * @param int $product_id Product ID.
	 * @return int Modified quantity.
	 */
	public function force_quantity_on_add_to_cart( $quantity, $product_id ) {
		$settings = get_option( 'wsc_settings', array() );
		if ( empty( $settings['cart_limit_enabled'] ) ) {
			return $quantity;
		}

		$rules = isset( $settings['cart_limit_rules'] ) ? $settings['cart_limit_rules'] : array();
		if ( empty( $rules ) ) {
			return $quantity;
		}

		foreach ( $rules as $rule ) {
            // Only apply if auto-adjust is enabled
            if ( empty( $rule['auto_adjust_qty'] ) ) {
                continue;
            }

            // Check conditions first
            if ( ! empty( $rule['conditions'] ) && ! WSC_Condition_Evaluator::evaluate( $rule['conditions'] ) ) {
                continue;
            }

            $target_type = isset( $rule['target_type'] ) ? $rule['target_type'] : 'global';
			$target_id   = isset( $rule['target_id'] ) ? absint( $rule['target_id'] ) : 0;
			$min_qty     = isset( $rule['min_qty'] ) ? absint( $rule['min_qty'] ) : 0;

            if ( ! $min_qty ) {
                continue;
            }
            
            // Check if product matches target
            $matches = false;
            if ( 'global' === $target_type ) {
                $matches = true;
            } elseif ( 'category' === $target_type ) {
                if ( has_term( $target_id, 'product_cat', $product_id ) ) {
                    $matches = true;
                }
            } elseif ( 'tag' === $target_type ) {
                if ( has_term( $target_id, 'product_tag', $product_id ) ) {
                    $matches = true;
                }
            }

            if ( ! $matches ) {
                continue;
            }

            // Calculate current quantity in cart for this target
            $current_qty_in_cart = 0;
            
            // Ensure cart is loaded especially in AJAX context
            if ( isset( WC()->cart ) ) {
                if ( WC()->cart->is_empty() && WC()->session && WC()->session->has_session() ) {
                     WC()->cart->get_cart_from_session();
                }

                if ( WC()->cart->get_cart() ) {
                 foreach ( WC()->cart->get_cart() as $cart_item ) {
                    $c_product_id = $cart_item['product_id'];
                    
                    if ( 'global' === $target_type ) {
                        $current_qty_in_cart += $cart_item['quantity'];
                    } elseif ( 'category' === $target_type ) {
                        if ( has_term( $target_id, 'product_cat', $c_product_id ) ) {
                            $current_qty_in_cart += $cart_item['quantity'];
                        }
                    } elseif ( 'tag' === $target_type ) {
                        if ( has_term( $target_id, 'product_tag', $c_product_id ) ) {
                            $current_qty_in_cart += $cart_item['quantity'];
                        }
                    }
                }
                }
            }

            $total_potential_qty = $current_qty_in_cart + $quantity;
            
            if ( $total_potential_qty < $min_qty ) {
                $quantity_needed = $min_qty - $current_qty_in_cart;
                $quantity = $quantity_needed;
                
                $term_name = 'items';
                 if ( 'category' === $target_type || 'tag' === $target_type ) {
                    $term = get_term( $target_id, $target_type === 'category' ? 'product_cat' : 'product_tag' );
                    if ( $term && ! is_wp_error( $term ) ) {
                        $term_name = $term->name;
                    }
                }
                
                
                // Only show notice if NOT AJAX and NOT on Shop/Archive
                if ( ! wp_doing_ajax() && ! is_shop() && ! is_product_taxonomy() ) {
                    wc_add_notice( sprintf( __( 'Quantity adjusted to %d to meet the minimum requirement for %s.', 'woocommerce-simple-customizations' ), $min_qty, esc_html( $term_name ) ), 'notice' );
                }
            }
		}

		return $quantity;
	}

	/**
	 * Check cart limits on Cart page.
	 */
	public function check_cart_limits() {
        $errors = $this->get_validation_errors();
        foreach ( $errors as $error ) {
            wc_add_notice( $error, 'error' );
        }
    }

    /**
     * Validate rules during Checkout process.
     * 
     * @param array    $data   Posted data.
     * @param WP_Error $errors Validation errors.
     */
    public function validate_checkout_rules( $data, $errors ) {
        $validation_errors = $this->get_validation_errors();
        foreach ( $validation_errors as $error_msg ) {
            $errors->add( 'wsc_validation_error', $error_msg );
        }
    }

    /**
     * Validate rules for Store API (Blocks).
     * 
     * @param WP_Error $errors Validation errors.
     * @param mixed    $cart   Cart object.
     * @return WP_Error $errors.
     */
    public function validate_store_api_cart( $errors, $cart = null ) {
        $validation_errors = $this->get_validation_errors( $cart );
        foreach ( $validation_errors as $error_msg ) {
            $errors->add( 'wsc_validation_error', $error_msg );
        }
        return $errors;
    }

    /**
     * Hard stop for Store API Checkout.
     * 
     * @param \WC_Order $order   Order object.
     * @param mixed     $request Request object.
     * @throws \Exception If validation fails.
     */
    public function check_store_api_checkout( $order, $request ) {
        $validation_errors = $this->get_validation_errors();
        if ( ! empty( $validation_errors ) ) {
            $message = implode( ' ', $validation_errors );
            if ( class_exists( '\Automattic\WooCommerce\StoreApi\Exceptions\RouteException' ) ) {
                throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'wsc_validation_error', $message, 400 );
            } else {
                throw new \Exception( $message );
            }
        }
    }

    /**
     * Remove the checkout button if cart limits are not met.
     */
    public function maybe_remove_checkout_button() {
        if ( ! empty( $this->get_validation_errors() ) ) {
            remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
        }
    }

    /**
     * Disable navigation to checkout if validation fails.
     * 
     * @param string $url Checkout URL.
     * @return string Modified URL.
     */
    public function disable_checkout_navigation( $url ) {
        if ( ! empty( $this->get_validation_errors() ) ) {
            return wc_get_cart_url();
        }
        return $url;
    }

    /**
     * Restrict access to the checkout page if validation fails.
     */
    public function restrict_checkout_access() {
        if ( is_checkout() && ! is_wc_endpoint_url( 'order-received' ) ) {
            if ( ! empty( $this->get_validation_errors() ) ) {
                wp_safe_redirect( wc_get_cart_url() );
                exit;
            }
        }
    }

    /**
     * Get all validation errors based on current cart and rules.
     * 
     * @param mixed $cart Optional cart object.
     * @return array List of error messages.
     */
    private function get_validation_errors( $cart = null ) {
        $errors_list = array();
		$settings = get_option( 'wsc_settings', array() );

		// Check if enabled
		if ( empty( $settings['cart_limit_enabled'] ) ) {
			return $errors_list;
		}

		$rules = isset( $settings['cart_limit_rules'] ) ? $settings['cart_limit_rules'] : array();

		// Backward Compatibility: If no rules, use legacy settings
		if ( empty( $rules ) ) {
			$rule_type = isset( $settings['cart_limit_rule_type'] ) ? $settings['cart_limit_rule_type'] : 'global';
			$term_id   = isset( $settings['cart_limit_term_id'] ) ? absint( $settings['cart_limit_term_id'] ) : 0;
			$min_qty   = isset( $settings['cart_limit_min_qty'] ) ? absint( $settings['cart_limit_min_qty'] ) : 0;

			if ( $min_qty > 0 ) {
				$rules[] = array(
					'target_type' => $rule_type,
					'target_id'   => $term_id,
					'min_qty'     => $min_qty,
					'conditions'  => array(),
				);
			}
		}

		if ( empty( $rules ) ) {
			return $errors_list;
		}

		foreach ( $rules as $rule ) {
			$error = $this->validate_rule( $rule, $cart );
            if ( $error ) {
                $errors_list[] = $error;
            }
		}

        return $errors_list;
	}

	/**
	 * Validate a single rule.
	 *
	 * @param array $rule Rule data.
     * @param mixed $cart Optional cart object.
     * @return string|bool Error message if invalid, false otherwise.
	 */
	private function validate_rule( $rule, $cart = null ) {
		$conditions = isset( $rule['conditions'] ) ? $rule['conditions'] : array();
		
		// 1. Check Conditions
		if ( ! WSC_Condition_Evaluator::evaluate( $conditions ) ) {
			return false;
		}

		// 2. Calculate Quantity based on Target
		$target_type = isset( $rule['target_type'] ) ? $rule['target_type'] : 'global';
		$target_id   = isset( $rule['target_id'] ) ? absint( $rule['target_id'] ) : 0;
		$min_qty     = isset( $rule['min_qty'] ) ? absint( $rule['min_qty'] ) : 0;

		if ( ! $min_qty ) {
			return false;
		}

		// Validate Target
		if ( 'global' !== $target_type && ! $target_id ) {
			return false;
		}

		$current_qty = 0;
		$term_name   = '';
        
        $cart_object = $cart ? $cart : WC()->cart;
        if ( ! $cart_object ) {
            return false;
        }

		foreach ( $cart_object->get_cart() as $cart_item ) {
			$product_id = $cart_item['product_id'];
			
			if ( 'global' === $target_type ) {
				$current_qty += $cart_item['quantity'];
			} elseif ( 'category' === $target_type ) {
				if ( has_term( $target_id, 'product_cat', $product_id ) ) {
					$current_qty += $cart_item['quantity'];
				}
			} elseif ( 'tag' === $target_type ) {
				if ( has_term( $target_id, 'product_tag', $product_id ) ) {
					$current_qty += $cart_item['quantity'];
				}
			}
		}

		// 3. Validate Limit
		if ( $current_qty > 0 && $current_qty < $min_qty ) {
			if ( 'global' === $target_type ) {
				/* translators: %s: Minimum Quantity */
				$message = sprintf( __( 'You must purchase at least %s items in total to proceed.', 'woocommerce-simple-customizations' ), $min_qty );
			} else {
				$term = get_term( $target_id, $target_type === 'category' ? 'product_cat' : 'product_tag' );
				$term_name = $term && ! is_wp_error( $term ) ? $term->name : 'Selected Items';
				
				/* translators: 1: Minimum Quantity, 2: Term Name */
				$message = sprintf( __( 'You must purchase at least %1$s items from "%2$s" to proceed.', 'woocommerce-simple-customizations' ), $min_qty, esc_html( $term_name ) );
			}

			return $message;
		}
        return false;
	}
}

new WSC_Cart_Limit();
