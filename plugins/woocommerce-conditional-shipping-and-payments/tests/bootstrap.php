<?php
/**
 * PHPUnit Bootstrap.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Mock WP functions if not available
if ( ! function_exists( 'add_filter' ) ) {
    function add_filter() {}
}
if ( ! function_exists( 'add_action' ) ) {
    function add_action() {}
}
if ( ! function_exists( 'get_option' ) ) {
    function get_option( $opt, $default ) {
        return $default;
    }
}
