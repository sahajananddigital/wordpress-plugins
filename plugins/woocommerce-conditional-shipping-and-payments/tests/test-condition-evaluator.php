<?php
/**
 * Test Condition Evaluator.
 *
 * @package WooCommerceConditionalShipping
 */

namespace SahajanandDigital\WooCommerceConditionalShipping\Conditions;

use WP_Mock\Tools\TestCase;

// --- NAMESPACE MOCKS ---
// These functions will be called instead of global/WP functions when called from within the Conditions namespace.

$mock_post = array();
$mock_input = '';
$mock_options = array();
$mock_wc_customer = null;
$mock_is_admin = false;
$mock_wp_doing_ajax = false;

function get_option( $option, $default = false ) {
    global $mock_options;
    return isset( $mock_options[ $option ] ) ? $mock_options[ $option ] : $default;
}

function file_get_contents( $filename ) {
    global $mock_input;
    if ( 'php://input' === $filename ) {
        return $mock_input;
    }
    return \file_get_contents( $filename );
}

function sanitize_text_field( $str ) {
    return trim( $str );
}

function wp_unslash( $val ) {
    return $val;
}

function is_admin() {
    global $mock_is_admin;
    return $mock_is_admin;
}

function wp_doing_ajax() {
    global $mock_wp_doing_ajax;
    return $mock_wp_doing_ajax;
}

function WC() {
    return new Mock_WC();
}

class Mock_WC {
    public $customer;
    public function __construct() {
        global $mock_wc_customer;
        $this->customer = $mock_wc_customer;
    }
}

class Mock_Customer {
    private $country;
    public function __construct( $country ) {
        $this->country = $country;
    }
    public function get_billing_country() {
        return $this->country;
    }
}

// --- TEST CLASS ---

class Test_Condition_Evaluator extends \PHPUnit\Framework\TestCase {

    protected function setUp(): void {
        global $mock_post, $mock_input, $mock_options, $mock_wc_customer, $mock_is_admin, $mock_wp_doing_ajax;
        $mock_post = array();
        $mock_input = '';
        $mock_options = array();
        $mock_wc_customer = null;
        $mock_is_admin = false;
        $mock_wp_doing_ajax = false;
        
        // Mock $_POST global
        $_POST = array();
    }

    public function test_filter_gateways_standard_customer() {
        global $mock_wc_customer, $mock_options;

        // Condition: Disable Stripe if Country is IN
        $mock_options['wc_csp_conditions'] = array(
            array(
                'id' => 1,
                'enabled' => true,
                'action' => 'disable',
                'countries' => array( 'IN' ),
                'payment_methods' => array( 'stripe' ),
            )
        );

        $mock_wc_customer = new Mock_Customer( 'IN' );

        $gateways = array( 'stripe' => 'Stripe', 'paypal' => 'PayPal' );
        $result = Condition_Evaluator::filter_payment_gateways( $gateways );

        $this->assertArrayNotHasKey( 'stripe', $result );
        $this->assertArrayHasKey( 'paypal', $result );
    }

    public function test_filter_gateways_json_standard() {
        global $mock_input, $mock_options;

        // Condition: Allow Only PayPal if Country is US
        // (So if US, keep PayPal, remove others. Wait logic: Enable PayPal IF US. So disable PayPal IF NOT US.
        // Actually Enable PayPal = Keep PayPal, Remove others? 
        // Let's re-read the logic:
        // 'enable' === action: if ( ! match ) { unset( target ) }
        // So: If I say "Enable PayPal for US", and I am US (match=true), nothing happens (PayPal stays).
        // If I am FR (match=false), PayPal is unset.
        
        $mock_options['wc_csp_conditions'] = array(
            array(
                'id' => 2,
                'enabled' => true,
                'action' => 'enable',
                'countries' => array( 'US' ),
                'payment_methods' => array( 'paypal' ),
            )
        );

        $mock_input = json_encode( array(
            'billing_address' => array( 'country' => 'FR' )
        ) );

        $gateways = array( 'stripe' => 'Stripe', 'paypal' => 'PayPal' );
        $result = Condition_Evaluator::filter_payment_gateways( $gateways );

        // I am FR. Condition (US) does NOT match. Action is 'enable'. Logic: if (!match) unset.
        // So PayPal should be removed.
        $this->assertArrayNotHasKey( 'paypal', $result );
        $this->assertArrayHasKey( 'stripe', $result );
    }

    public function test_filter_gateways_json_batch() {
        global $mock_input, $mock_options;

        // Condition: Disable COD if Country is IQ
        $mock_options['wc_csp_conditions'] = array(
            array(
                'id' => 3,
                'enabled' => true,
                'action' => 'disable',
                'countries' => array( 'IQ' ),
                'payment_methods' => array( 'cod' ),
            )
        );

        // Simulate Batch Request structure from blocks
        $mock_input = json_encode( array(
            'requests' => array(
                array( 'path' => '/other/endpoint' ),
                array(
                    'path' => '/wc/store/v1/cart/update-customer',
                    'body' => array(
                        'billing_address' => array( 'country' => 'IQ' )
                    )
                )
            )
        ) );

        $gateways = array( 'cod' => 'Cash on Delivery', 'stripe' => 'Stripe' );
        $result = Condition_Evaluator::filter_payment_gateways( $gateways );

        $this->assertArrayNotHasKey( 'cod', $result );
        $this->assertArrayHasKey( 'stripe', $result );
    }

    public function test_filter_gateways_legacy_post() {
        global $mock_options;

        // Condition: Disable Stripe if Country is CA
        $mock_options['wc_csp_conditions'] = array(
            array(
                'id' => 4,
                'enabled' => true,
                'action' => 'disable',
                'countries' => array( 'CA' ),
                'payment_methods' => array( 'stripe' ),
            )
        );

        $_POST['billing_country'] = 'CA';

        $gateways = array( 'stripe' => 'Stripe', 'paypal' => 'PayPal' );
        $result = Condition_Evaluator::filter_payment_gateways( $gateways );

        $this->assertArrayNotHasKey( 'stripe', $result );
        $this->assertArrayHasKey( 'paypal', $result );
    }
}
