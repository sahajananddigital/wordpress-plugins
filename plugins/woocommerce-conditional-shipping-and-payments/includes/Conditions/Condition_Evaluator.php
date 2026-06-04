<?php
/**
 * Condition Evaluator Class.
 *
 * @package WooCommerceConditionalShipping
 */

namespace SahajanandDigital\WooCommerceConditionalShipping\Conditions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Condition Evaluator class.
 */
class Condition_Evaluator {

	/**
	 * Init.
	 */
	public static function init() {
		add_filter( 'woocommerce_available_payment_gateways', array( __CLASS__, 'filter_payment_gateways' ) );
        // Future: add_filter( 'woocommerce_package_rates', array( __CLASS__, 'filter_shipping_methods' ), 10, 2 );
	}

	/**
	 * Filter payment gateways based on conditions.
	 *
	 * @param array $available_gateways Available payment gateways.
	 * @return array Filtered payment gateways.
	 */
	public static function filter_payment_gateways( $available_gateways ) {
        // Do not disable in admin (settings page) but allow ajax for checkout
        if ( is_admin() && ! wp_doing_ajax() ) {
            return $available_gateways;
        }

		$conditions = self::get_conditions();

        if ( empty( $conditions ) ) {
            return $available_gateways;
        }

        // Get customer country safely
        $customer_country = self::get_customer_country();

        foreach ( $conditions as $condition ) {
            if ( ! isset( $condition['enabled'] ) || ! $condition['enabled'] ) {
                continue;
            }

            // 1. Evaluate Condition (Is it true?)
            $condition_matches = false;
            if ( isset( $condition['countries'] ) && is_array( $condition['countries'] ) ) {
                if ( in_array( $customer_country, $condition['countries'], true ) ) {
                    $condition_matches = true;
                }
            }

            // 2. Evaluate Logic
            $action = isset( $condition['action'] ) ? $condition['action'] : 'disable';
            $target_methods = isset( $condition['payment_methods'] ) && is_array( $condition['payment_methods'] ) ? $condition['payment_methods'] : array();

            if ( 'disable' === $action ) {
                // Logic: Disable selected methods IF condition matches.
                if ( $condition_matches ) {
                    foreach ( $target_methods as $method_id ) {
                        unset( $available_gateways[ $method_id ] );
                    }
                }
            } elseif ( 'enable' === $action ) {
                // Logic: Enable selected methods ONLY IF condition matches.
                // Which means: Disable selected methods IF condition DOES NOT match.
                if ( ! $condition_matches ) {
                    foreach ( $target_methods as $method_id ) {
                        unset( $available_gateways[ $method_id ] );
                    }
                }
            }
        }

		return $available_gateways;
	}

    /**
     * Get customer country with fallback for Blocks/AJAX.
     *
     * @return string|null Country code.
     */
    private static function get_customer_country() {
        // Debug Logger
        $log_file = WC_CSP_PLUGIN_DIR . 'debug_csp.log';
        $log = function($msg) use ($log_file) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
        };

        // 1. Raw JSON (Blocks/Store API)
        $raw_data = file_get_contents( 'php://input' );
        if ( ! empty( $raw_data ) ) {
            $data = json_decode( $raw_data, true );
            if ( json_last_error() === JSON_ERROR_NONE ) {
                $log("JSON Keys: " . implode(', ', array_keys($data))); // DEBUG: See what keys exist
                
                // Case A: Standard Checkout/Cart Update (Root billing_address)
                if ( isset( $data['billing_address']['country'] ) ) {
                    $c = sanitize_text_field( $data['billing_address']['country'] );
                    $log("JSON [Standard] found: $c");
                    return $c;
                }

                // Case B: Batch Request (Common in Blocks)
                if ( isset( $data['requests'] ) ) {
                     $log("JSON ['requests'] key exists."); 
                     if ( is_array( $data['requests'] ) ) {
                        $log("JSON [Batch] detected. Requests: " . count($data['requests']));
                        foreach ( $data['requests'] as $request ) {
                            // Log the path to see what we are dealing with
                            $path = isset($request['path']) ? $request['path'] : 'unknown';
                            $log(" - Request Path: $path");
    
                            if ( isset( $request['body']['billing_address']['country'] ) ) {
                                $c = sanitize_text_field( $request['body']['billing_address']['country'] );
                                $log("   -> Found in body: $c");
                                return $c;
                            }
                            if ( isset( $request['data']['billing_address']['country'] ) ) {
                                $c = sanitize_text_field( $request['data']['billing_address']['country'] );
                                $log("   -> Found in data: $c");
                                return $c;
                            }
                        }
                     } else {
                         $log("JSON ['requests'] is not an array.");
                     }
                } else {
                    $log("JSON ['requests'] key MISSING.");
                }

                // Case C: Direct Country Field

                // Case C: Direct Country Field
                if ( isset( $data['country'] ) ) {
                    $c = sanitize_text_field( $data['country'] );
                    $log("JSON [Direct] found: $c");
                    return $c;
                }
            } else {
                 $log("JSON Decode Error: " . json_last_error_msg());
            }
        }

        // 2. Legacy AJAX
        if ( isset( $_POST['country'] ) ) {
             $c = sanitize_text_field( wp_unslash( $_POST['country'] ) );
             $log("POST['country'] found: $c");
             return $c;
        }
        if ( isset( $_POST['billing_country'] ) ) {
             $c = sanitize_text_field( wp_unslash( $_POST['billing_country'] ) );
             $log("POST['billing_country'] found: $c");
             return $c;
        }

        // 3. Standard WC Customer
        if ( WC()->customer ) {
            $country = WC()->customer->get_billing_country();
            if ( ! empty( $country ) ) {
                $log("WC()->customer found: $country");
                return $country;
            }
        }

        $log("No country found.");
        return null;
    }

    /**
     * Get conditions.
     * 
     * @return array Conditions.
     */
    private static function get_conditions() {
        // Retrieve from DB (Option for now, later custom post type or table)
        return get_option( 'wc_csp_conditions', array() );
    }
}
