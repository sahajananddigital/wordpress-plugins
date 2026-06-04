<?php
/**
 * Plugin Name: WSC E2E Setup
 * Description: Seeds data for E2E tests.
 */
 
register_activation_hook( __FILE__, 'wsc_e2e_seed' );

function wsc_e2e_seed() {
    // 1. Set Options
    update_option('wsc_settings', array(
        'cart_limit_enabled' => '1',
        'price_suffix_enabled' => '1',
        'cart_limit_rules' => array(
            array(
                'target_type' => 'global',
                'min_qty' => 5,
                'auto_adjust_qty' => '0'
            )
        )
    ));

    // 2. Create Product
    // We hook to init to ensure WC is loaded if activation happens too early, 
    // but usually activation is inside WP init sequence.
    // However, WC classes might not be available during this plugin's activation hook if WC is activated in the same request?
    // Safer to simple run immediate if classes exist, or hook to 'init'.
    
    if ( class_exists( 'WC_Product_Simple' ) ) {
        wsc_create_product();
    } else {
        add_action( 'init', 'wsc_create_product' );
    }
}

function wsc_create_product() {
    // Check if exists
    $existing = get_page_by_title( 'Demo Product', OBJECT, 'product' );
    if ( $existing ) return;

    $p = new WC_Product_Simple();
    $p->set_name('Demo Product');
    $p->set_regular_price(25);
    $p->set_status('publish');
    $p->update_meta_data('_wsc_price_suffix', 'per box');
    $p->save();
}
