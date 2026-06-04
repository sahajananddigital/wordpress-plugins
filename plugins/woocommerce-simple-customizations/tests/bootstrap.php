<?php
/**
 * PHPUnit Bootstrap.
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

// If WP Test Suite is not found, we mock the environment for lightweight unit testing
if ( ! $_tests_dir || ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "WP Test Suite not found. Loading Mock Environment..." . PHP_EOL;

	if ( ! class_exists( 'WP_UnitTestCase' ) ) {
		class WP_UnitTestCase extends \PHPUnit\Framework\TestCase {}
	}
	
	if ( ! class_exists( 'WP_REST_Controller' ) ) {
		class WP_REST_Controller {}
	}

	if ( ! class_exists( 'WC_Settings_Page' ) ) {
		class WC_Settings_Page {
			public function __construct() {}
		}
	}
	
	// Mock basic WP functions
    global $wsc_hooks;
    $wsc_hooks = array();

	if ( ! function_exists( 'add_action' ) ) { 
        function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) { 
            global $wsc_hooks;
            $wsc_hooks[ $tag ][] = $callback;
            return true;
        } 
    }
    if ( ! function_exists( 'has_action' ) ) {
        function has_action( $tag, $callback = false ) {
            global $wsc_hooks;
            if ( ! isset( $wsc_hooks[ $tag ] ) ) {
                return false;
            }
            if ( false === $callback ) {
                return true;
            }
            // Simple check for object/array consistency in mocks might be tricky
            // For now, return true if tag exists
            return true;
        }
    }
	if ( ! function_exists( 'do_action' ) ) { function do_action() {} }
	if ( ! function_exists( 'apply_filters' ) ) { function apply_filters( $tag, $value ) { return $value; } }
	if ( ! function_exists( 'add_filter' ) ) { function add_filter() {} }
	if ( ! function_exists( '__' ) ) { function __( $text, $domain = 'default' ) { return $text; } }
	if ( ! function_exists( '_e' ) ) { function _e( $text, $domain = 'default' ) { echo $text; } }
	if ( ! function_exists( 'esc_url_raw' ) ) { function esc_url_raw( $url ) { return $url; } }
	if ( ! function_exists( 'absint' ) ) { function absint( $n ) { return intval( $n ); } }
	if ( ! function_exists( 'plugin_dir_path' ) ) { function plugin_dir_path( $file ) { return dirname( $file ) . '/'; } }
	if ( ! function_exists( 'plugin_dir_url' ) ) { function plugin_dir_url( $file ) { return 'http://example.com/wp-content/plugins/wsc/'; } }
	
	// Mock Options API (Simple Array Store)
	global $wsc_mock_options;
	$wsc_mock_options = array();
	
	if ( ! function_exists( 'get_option' ) ) { 
		function get_option( $key, $default = false ) { 
			global $wsc_mock_options;
			return isset( $wsc_mock_options[ $key ] ) ? $wsc_mock_options[ $key ] : $default;
		} 
	}
	if ( ! function_exists( 'update_option' ) ) { 
		function update_option( $key, $value ) { 
			global $wsc_mock_options;
			$wsc_mock_options[ $key ] = $value;
			return true;
		} 
	}
	
	// Mock Post Meta (Simple Array Store)
    global $wsc_mock_post_meta;
    $wsc_mock_post_meta = array();

    if ( ! function_exists( 'update_post_meta' ) ) {
        function update_post_meta( $post_id, $key, $value ) {
            global $wsc_mock_post_meta;
            if ( ! isset( $wsc_mock_post_meta[ $post_id ] ) ) {
                $wsc_mock_post_meta[ $post_id ] = array();
            }
            $wsc_mock_post_meta[ $post_id ][ $key ] = $value;
            return true;
        }
    }

    if ( ! function_exists( 'get_post_meta' ) ) {
        function get_post_meta( $post_id, $key, $single = false ) {
            global $wsc_mock_post_meta;
            if ( isset( $wsc_mock_post_meta[ $post_id ] ) && isset( $wsc_mock_post_meta[ $post_id ][ $key ] ) ) {
                return $wsc_mock_post_meta[ $post_id ][ $key ];
            }
            return '';
        }
    }
    
    if ( ! function_exists( 'sanitize_text_field' ) ) { function sanitize_text_field( $str ) { return trim( $str ); } }
    if ( ! function_exists( 'esc_html' ) ) { function esc_html( $str ) { return htmlspecialchars( $str ); } }

	// Mock WC
	if ( ! function_exists( 'WC' ) ) {
		function WC() {
			return new class {
				public $cart;
				public function __construct() {
					$this->cart = new class {
						public function get_cart_contents_count() { return 0; }
						public function get_cart_contents() { return array(); }
					};
				}
			};
		}
	}

	// Defined constants
	if ( ! defined( 'ABSPATH' ) ) define( 'ABSPATH', '/tmp/' );

    // Load Plugin
    require_once dirname( dirname( __FILE__ ) ) . '/woocommerce-simple-customizations.php';

} else {
    // Normal WP Test Load
    require_once $_tests_dir . '/includes/functions.php';

    function _manually_load_plugin() {
        require dirname( dirname( __FILE__ ) ) . '/woocommerce-simple-customizations.php';
    }
    tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

    require $_tests_dir . '/includes/bootstrap.php';
}
