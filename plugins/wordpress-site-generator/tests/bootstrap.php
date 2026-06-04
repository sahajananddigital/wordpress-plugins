<?php
/**
 * Mock WordPress functions for unit testing.
 */

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( $str ) {
		$str = strtolower( $str );
		$str = preg_replace( '/[^a-z0-9]/', '-', $str );
		return trim( $str, '-' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $str ) {
		return htmlspecialchars( $str, ENT_QUOTES );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $str ) {
		return htmlspecialchars( $str, ENT_QUOTES );
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value ) {
		$GLOBALS['wp_options'][$option] = $value;
		return true;
	}
}

if ( ! function_exists( 'get_page_by_path' ) ) {
	function get_page_by_path( $path ) {
		return isset( $GLOBALS['wp_posts_by_path'][$path] ) ? (object) $GLOBALS['wp_posts_by_path'][$path] : null;
	}
}

if ( ! function_exists( 'wp_update_post' ) ) {
	function wp_update_post( $args ) {
		$id = $args['ID'];
		$GLOBALS['wp_posts'][$id] = array_merge( $GLOBALS['wp_posts'][$id], $args );
		return $id;
	}
}

if ( ! function_exists( 'wp_insert_post' ) ) {
	function wp_insert_post( $args ) {
		static $id_gen = 100;
		$id = ++$id_gen;
		$args['ID'] = $id;
		$GLOBALS['wp_posts'][$id] = $args;
		if ( isset( $args['post_name'] ) ) {
			$GLOBALS['wp_posts_by_path'][$args['post_name']] = $args;
		}
		return $id;
	}
}

if ( ! function_exists( 'get_permalink' ) ) {
	function get_permalink( $id ) {
		return "http://example.com/?p=" . $id;
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

class WP_Error {
	private $code;
	private $message;
	public function __construct( $code, $message ) {
		$this->code = $code;
		$this->message = $message;
	}
	public function get_error_message() {
		return $this->message;
	}
}
