<?php
/**
 * PHPUnit & Unit Test Bootstrap for Sahajanand Static Search.
 *
 * Provides a lightweight, in-memory mock of WordPress core APIs
 * allowing unit tests to execute cleanly without an active MySQL database.
 *
 * @package Sahajanand_Static_Search
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', sys_get_temp_dir() . '/wordpress/' );
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

// In-memory WordPress storage.
global $wp_mock_options, $wp_mock_transients, $wp_mock_filters, $wp_mock_actions, $wp_mock_posts, $wp_mock_terms;
$wp_mock_options    = array();
$wp_mock_transients = array();
$wp_mock_filters    = array();
$wp_mock_actions    = array();
$wp_mock_posts      = array();
$wp_mock_terms      = array();

/**
 * Reset test mock storage between tests.
 */
function wp_mock_reset() {
	global $wp_mock_options, $wp_mock_transients, $wp_mock_filters, $wp_mock_actions, $wp_mock_posts, $wp_mock_terms;
	$wp_mock_options    = array();
	$wp_mock_transients = array();
	$wp_mock_filters    = array();
	$wp_mock_actions    = array();
	$wp_mock_posts      = array();
	$wp_mock_terms      = array();
}

// Mock WordPress functions.
if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
		add_filter( $hook_name, $callback, $priority, $accepted_args );
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( $hook_name, ...$arg ) {
		global $wp_mock_actions;
		if ( ! isset( $wp_mock_actions[ $hook_name ] ) ) {
			$wp_mock_actions[ $hook_name ] = 0;
		}
		$wp_mock_actions[ $hook_name ]++;
		apply_filters( $hook_name, '', ...$arg );
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
		global $wp_mock_filters;
		if ( ! isset( $wp_mock_filters[ $hook_name ] ) ) {
			$wp_mock_filters[ $hook_name ] = array();
		}
		$wp_mock_filters[ $hook_name ][] = array(
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook_name, $value, ...$args ) {
		global $wp_mock_filters;
		if ( empty( $wp_mock_filters[ $hook_name ] ) ) {
			return $value;
		}

		$hooks = $wp_mock_filters[ $hook_name ];
		usort(
			$hooks,
			function ( $a, $b ) {
				return $a['priority'] <=> $b['priority'];
			}
		);

		foreach ( $hooks as $hook ) {
			$params = array_merge( array( $value ), $args );
			$params = array_slice( $params, 0, $hook['accepted_args'] );
			$value  = call_user_func_array( $hook['callback'], $params );
		}

		return $value;
	}
}

if ( ! function_exists( 'register_activation_hook' ) ) {
	function register_activation_hook( $file, $callback ) {}
}

if ( ! function_exists( 'plugin_basename' ) ) {
	function plugin_basename( $file ) {
		return basename( dirname( $file ) ) . '/' . basename( $file );
	}
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return trailingslashit( dirname( $file ) );
	}
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( $file ) {
		return 'http://example.com/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
	}
}

if ( ! function_exists( 'home_url' ) ) {
	function home_url( $path = '', $scheme = null ) {
		global $wp_mock_home_url;
		$base = ! empty( $wp_mock_home_url ) ? $wp_mock_home_url : 'https://example.com';
		if ( ! empty( $path ) ) {
			return rtrim( $base, '/' ) . '/' . ltrim( $path, '/' );
		}
		return $base;
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $string ) {
		return rtrim( (string) $string, '/\\' ) . '/';
	}
}

if ( ! function_exists( 'add_query_arg' ) ) {
	function add_query_arg( ...$args ) {
		if ( count( $args ) === 2 && is_string( $args[0] ) && is_string( $args[1] ) ) {
			// Single key-value pair and URL.
			return $args[1];
		}
		if ( count( $args ) === 3 ) {
			$key = $args[0];
			$val = $args[1];
			$url = $args[2];
			$sep = strpos( $url, '?' ) === false ? '?' : '&';
			return $url . $sep . rawurlencode( $key ) . '=' . rawurlencode( $val );
		}
		return (string) end( $args );
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		global $wp_mock_options;
		return isset( $wp_mock_options[ $option ] ) ? $wp_mock_options[ $option ] : $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value, $autoload = null ) {
		global $wp_mock_options;
		$wp_mock_options[ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( $option ) {
		global $wp_mock_options;
		unset( $wp_mock_options[ $option ] );
		return true;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $transient ) {
		global $wp_mock_transients;
		return isset( $wp_mock_transients[ $transient ] ) ? $wp_mock_transients[ $transient ] : false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $transient, $value, $expiration = 0 ) {
		global $wp_mock_transients;
		$wp_mock_transients[ $transient ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( $transient ) {
		global $wp_mock_transients;
		unset( $wp_mock_transients[ $transient ] );
		return true;
	}
}

if ( ! function_exists( 'wp_upload_dir' ) ) {
	function wp_upload_dir() {
		$tmp = sys_get_temp_dir() . '/wp_uploads_test';
		return array(
			'basedir' => $tmp,
			'baseurl' => 'https://example.com/wp-content/uploads',
		);
	}
}

if ( ! function_exists( 'wp_mkdir_p' ) ) {
	function wp_mkdir_p( $target ) {
		return is_dir( $target ) || mkdir( $target, 0777, true );
	}
}

if ( ! function_exists( 'get_post_types' ) ) {
	function get_post_types( $args = array(), $output = 'names', $operator = 'and' ) {
		global $wp_mock_cpts;
		return ! empty( $wp_mock_cpts ) ? $wp_mock_cpts : array( 'custom_doc' => 'custom_doc' );
	}
}

if ( ! function_exists( 'get_permalink' ) ) {
	function get_permalink( $post = 0 ) {
		if ( is_object( $post ) && isset( $post->permalink ) ) {
			return $post->permalink;
		}
		return 'https://example.com/sample-page/';
	}
}

if ( ! function_exists( 'get_the_title' ) ) {
	function get_the_title( $post = 0 ) {
		if ( is_object( $post ) && isset( $post->post_title ) ) {
			return $post->post_title;
		}
		return 'Sample Title';
	}
}

if ( ! function_exists( 'get_the_terms' ) ) {
	function get_the_terms( $post_id, $taxonomy ) {
		global $wp_mock_terms;
		return isset( $wp_mock_terms[ $post_id ] ) ? $wp_mock_terms[ $post_id ] : false;
	}
}

if ( ! function_exists( 'strip_shortcodes' ) ) {
	function strip_shortcodes( $content ) {
		return preg_replace( '/\[[^\]]+\]/', '', (string) $content );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $string, $remove_breaks = false ) {
		return strip_tags( (string) $string );
	}
}

if ( ! function_exists( 'wp_trim_words' ) ) {
	function wp_trim_words( $text, $num_words = 55, $more = null ) {
		if ( null === $more ) {
			$more = '&hellip;';
		}
		$words = preg_split( '/\s+/', trim( (string) $text ) );
		if ( count( $words ) <= $num_words ) {
			return $text;
		}
		return implode( ' ', array_slice( $words, 0, $num_words ) ) . $more;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

if ( ! function_exists( 'wp_reset_postdata' ) ) {
	function wp_reset_postdata() {}
}

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {
		return false;
	}
}

if ( ! function_exists( 'wp_doing_ajax' ) ) {
	function wp_doing_ajax() {
		return false;
	}
}

if ( ! function_exists( 'wp_enqueue_style' ) ) {
	function wp_enqueue_style() {}
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
	function wp_enqueue_script() {}
}

if ( ! function_exists( 'wp_localize_script' ) ) {
	function wp_localize_script() {}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
		return filter_var( $url, FILTER_SANITIZE_URL );
	}
}

if ( ! function_exists( 'esc_attr_e' ) ) {
	function esc_attr_e( $text ) {
		echo htmlspecialchars( (string) $text, ENT_QUOTES );
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES );
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'get_bloginfo' ) ) {
	function get_bloginfo( $show = '' ) {
		return '6.6';
	}
}

if ( ! function_exists( 'wp_remote_get' ) ) {
	function wp_remote_get( $url, $args = array() ) {
		global $wp_mock_remote_response;
		return ! empty( $wp_mock_remote_response ) ? $wp_mock_remote_response : array(
			'response' => array( 'code' => 200 ),
			'body'     => '{}',
		);
	}
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	function wp_remote_retrieve_response_code( $response ) {
		return isset( $response['response']['code'] ) ? (int) $response['response']['code'] : 200;
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ) {
		return isset( $response['body'] ) ? $response['body'] : '';
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return is_object( $thing ) && is_a( $thing, 'WP_Error' );
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public $message;
		public function __construct( $code = '', $message = '' ) {
			$this->message = $message;
		}
		public function get_error_message() {
			return $this->message;
		}
	}
}

if ( ! class_exists( 'WP_Post' ) ) {
	class WP_Post {
		public $ID = 1;
		public $post_title = '';
		public $post_content = '';
		public $post_excerpt = '';
		public $post_type = 'post';
		public $permalink = '';
	}
}

if ( ! class_exists( 'WP_Query' ) ) {
	class WP_Query {
		public $posts = array();
		public function __construct( $args = array() ) {
			global $wp_mock_posts;
			$this->posts = ! empty( $wp_mock_posts ) ? $wp_mock_posts : array();
		}
	}
}
