<?php
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
	 * @param string $class Class name.
	 */
	public static function autoload( $class ) {
		$prefix = 'GitSupport\\Engine\\';
		$len    = strlen( $prefix );

		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, $len );

		// Replace namespace separators with directory separators in the relative class name, append with .php.
		$file = dirname( __FILE__ ) . '/' . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
