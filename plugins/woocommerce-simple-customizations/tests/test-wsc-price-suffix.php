<?php
/**
 * Test Price Suffix Module
 *
 * @package WSC
 */

class Test_WSC_Price_Suffix extends WP_UnitTestCase {

    public function setUp(): void {
        parent::setUp();
        // Reset options
        global $wsc_mock_options;
        $wsc_mock_options = array();
        
        // Reset post meta
        global $wsc_mock_post_meta;
        $wsc_mock_post_meta = array();
    }

    public function test_add_price_suffix_field_callback() {
        $instance = new WSC_Price_Suffix();
        $this->assertTrue( has_action( 'woocommerce_product_options_general_product_data', array( $instance, 'add_price_suffix_field' ) ) );
    }

    public function test_save_price_suffix_field() {
        $instance = new WSC_Price_Suffix();
        $post_id = 123;
        $_POST['_wsc_price_suffix'] = 'per box';
        
        $instance->save_price_suffix_field( $post_id );
        
        $this->assertEquals( 'per box', get_post_meta( $post_id, '_wsc_price_suffix', true ) );
    }

    public function test_filter_price_html_enabled() {
        $instance = new WSC_Price_Suffix();
        $post_id = 123;
        
        // Mock Settings Enabled
        update_option( 'wsc_settings', array( 'price_suffix_enabled' => true ) );
        
        // Mock Post Meta
        update_post_meta( $post_id, '_wsc_price_suffix', 'per unit' );
        
        // Mock Product
        $product = new class {
            public function get_id() { return 123; }
        };

        $price_html = '<span class="amount">$10.00</span>';
        $filtered = $instance->filter_price_html( $price_html, $product );
        
        $this->assertStringContainsString( 'per unit', $filtered );
        $this->assertStringContainsString( 'wsc-price-suffix', $filtered );
    }

    public function test_filter_price_html_disabled() {
        $instance = new WSC_Price_Suffix();
        $post_id = 123;
        
        // Mock Settings Disabled
        update_option( 'wsc_settings', array( 'price_suffix_enabled' => false ) );
        
        // Mock Post Meta
        update_post_meta( $post_id, '_wsc_price_suffix', 'per unit' );
        
        // Mock Product
        $product = new class {
            public function get_id() { return 123; }
        };

        $price_html = '<span class="amount">$10.00</span>';
        $filtered = $instance->filter_price_html( $price_html, $product );
        
        $this->assertEquals( $price_html, $filtered );
    }
}
