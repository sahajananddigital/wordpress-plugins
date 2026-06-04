<?php
/**
 * Condition Evaluator
 *
 * @package WSC
 */

defined( 'ABSPATH' ) || exit;

class WSC_Condition_Evaluator {

	/**
	 * Evaluate conditions.
	 *
	 * @param array $conditions List of conditions to check.
	 * @return boolean True if all conditions pass.
	 */
	public static function evaluate( $conditions ) {
		if ( empty( $conditions ) || ! is_array( $conditions ) ) {
			return true;
		}

		foreach ( $conditions as $condition ) {
			if ( ! self::evaluate_condition( $condition ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Evaluate single condition.
	 *
	 * @param array $condition Condition data.
	 * @return boolean
	 */
	private static function evaluate_condition( $condition ) {
		$type     = isset( $condition['type'] ) ? $condition['type'] : '';
		$operator = isset( $condition['operator'] ) ? $condition['operator'] : '==';
		$value    = isset( $condition['value'] ) ? $condition['value'] : '';

		switch ( $type ) {
			case 'cart_total':
				return self::compare( WC()->cart->formatted_subtotal, $operator, $value ); // formatted_subtotal? likely get_subtotal()
			case 'cart_subtotal': 
				return self::compare( WC()->cart->get_subtotal(), $operator, $value );
			case 'cart_item_count':
				return self::compare( WC()->cart->get_cart_contents_count(), $operator, $value );
			case 'cart_has_product':
				return self::check_cart_has_product( $value );
			case 'cart_has_category':
				return self::check_cart_has_category( $value );
			case 'user_role':
				return self::check_user_role( $value );
			default:
				return true;
		}
	}

	/**
	 * Compare values.
	 */
	private static function compare( $value1, $operator, $value2 ) {
		switch ( $operator ) {
			case '==':
				return $value1 == $value2;
			case '!=':
				return $value1 != $value2;
			case '>':
				return $value1 > $value2;
			case '<':
				return $value1 < $value2;
			case '>=':
				return $value1 >= $value2;
			case '<=':
				return $value1 <= $value2;
			case 'in':
				return in_array( $value1, (array) $value2 );
			default:
				return false;
		}
	}

	/**
	 * Check if cart has product.
	 */
	private static function check_cart_has_product( $product_id ) {
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( $cart_item['product_id'] == $product_id || $cart_item['variation_id'] == $product_id ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if cart has category.
	 */
	private static function check_cart_has_category( $category_id ) {
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( has_term( $category_id, 'product_cat', $cart_item['product_id'] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check user role.
	 */
	private static function check_user_role( $role ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$user = wp_get_current_user();
		return in_array( $role, (array) $user->roles );
	}
}
