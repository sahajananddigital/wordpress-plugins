<?php
/**
 * PHPUnit tests bootstrap.
 */

// If composer autoload exists, load it.
$composer_autoload = dirname( __DIR__ ) . '/vendor/autoload.php';
if ( file_exists( $composer_autoload ) ) {
    require_once $composer_autoload;
} else {
    // Fail if composer isn't set up.
    echo "Error: Please run 'composer install' to set up unit testing dependencies before running tests.\n";
    exit( 1 );
}

// Initialize WP_Mock.
WP_Mock::setUsePatchwork( true );
WP_Mock::bootstrap();

// Register a dummy ABSPATH to satisfy guard clauses.
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', sys_get_temp_dir() . '/' );
}
