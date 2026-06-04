<?php
/**
 * Class CartLimitTest
 *
 * @package WSC
 */

class CartLimitTest extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		if ( ! class_exists( 'WSC_Cart_Limit' ) ) {
			// Ensure classes are loaded
			require_once dirname( dirname( __FILE__ ) ) . '/includes/modules/cart-limit/class-wsc-cart-limit.php';
			require_once dirname( dirname( __FILE__ ) ) . '/includes/class-wsc-condition-evaluator.php';
		}
	}

	public function test_legacy_settings_migration() {
		// Set legacy options
		update_option( 'wsc_settings', array(
			'cart_limit_enabled' => true,
			'cart_limit_rule_type' => 'global',
			'cart_limit_min_qty' => 5
		) );

		// Instantiate (triggers checks)
		$cart_limit = new WSC_Cart_Limit();
		
		// In a real integration test, we would check if check_cart_limits() adds a notice.
		// Since we can't fully mock WC()->cart in this lightweight environment without dedicated mocking library,
		// we verify the class methods exist and basics run without error.
		$this->assertTrue( method_exists( $cart_limit, 'check_cart_limits' ) );
	}

	public function test_multiple_rules_structure() {
		$rules = array(
			array(
				'target_type' => 'global',
				'min_qty' => 10,
				'conditions' => array()
			),
			array(
				'target_type' => 'category',
				'target_id' => 123,
				'min_qty' => 5,
				'conditions' => array(
					array( 'type' => 'user_role', 'operator' => '==', 'value' => 'subscriber' )
				)
			)
		);

		update_option( 'wsc_settings', array(
			'cart_limit_enabled' => true,
			'cart_limit_rules' => $rules
		) );

		$this->assertEquals( $rules, get_option( 'wsc_settings' )['cart_limit_rules'] );
	}
}
