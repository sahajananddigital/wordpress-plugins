<?php
/**
 * Autoloader for GitSupport Engine.
 *
 * @package GitSupport\Engine
 */

namespace GitSupport\Engine;

/**
 * Autoloader class.
 */
class Autoloader {

	/**
	 * Register the autoloader.
	 */
	public static function register() {
		spl_autoload_register( array( self::class, 'autoload' ) );
	}

	/**
	 * Autoload class files.
	 *
	 * @param string $class_name Class name.
	 */
	public static function autoload( $class_name ) {
		$prefix = 'GitSupport\\Engine\\';
		$len    = strlen( $prefix );

		if ( strncmp( $prefix, $class_name, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class_name, $len );

		// Replace namespace separators with directory separators in the relative class name, append with .php.
		$file = __DIR__ . '/' . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
