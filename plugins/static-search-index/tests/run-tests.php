<?php
/**
 * Standalone Test Runner for Sahajanand Static Search.
 *
 * Runs all unit tests with zero third-party dependencies.
 *
 * Usage:
 *   php plugins/static-search-index/tests/run-tests.php
 *
 * @package Sahajanand_Static_Search
 */

// Enable assertions and bail on failure.
ini_set( 'zend.assertions', 1 );
ini_set( 'assert.exception', 1 );
error_reporting( E_ALL );

echo "========================================================\n";
echo " Sahajanand Static Search - Test Suite Runner\n";
echo "========================================================\n\n";

$start_time = microtime( true );

// 1. Bootstrap mock WordPress environment.
require_once __DIR__ . '/bootstrap.php';

// 2. Load plugin files.
require_once dirname( __DIR__ ) . '/sahajanand-static-search-index.php';
require_once dirname( __DIR__ ) . '/includes/class-updater.php';

// 3. Load test suites.
require_once __DIR__ . '/test-static-search.php';
require_once __DIR__ . '/test-updater.php';

$suites = array(
	'Test_Sahajanand_Static_Search',
	'Test_Sahajanand_Static_Search_Updater',
);

$total  = 0;
$passed = 0;
$failed = 0;
$errors = array();

foreach ( $suites as $suite_class ) {
	echo "Running {$suite_class}...\n";
	$instance = new $suite_class();
	$reflection = new ReflectionClass( $suite_class );
	$methods = $reflection->getMethods( ReflectionMethod::IS_PUBLIC );

	foreach ( $methods as $method ) {
		if ( strpos( $method->name, 'test_' ) !== 0 ) {
			continue;
		}

		$total++;
		$test_name = $method->name;

		try {
			if ( method_exists( $instance, 'setUp' ) ) {
				$instance->setUp();
			}

			$instance->$test_name();
			$passed++;
			echo "  [PASS] {$test_name}\n";
		} catch ( Throwable $t ) {
			$failed++;
			echo "  [FAIL] {$test_name}: {$t->getMessage()}\n";
			$errors[] = array(
				'suite'   => $suite_class,
				'test'    => $test_name,
				'message' => $t->getMessage(),
				'file'    => $t->getFile(),
				'line'    => $t->getLine(),
			);
		}
	}
	echo "\n";
}

$elapsed = round( ( microtime( true ) - $start_time ) * 1000, 2 );

echo "--------------------------------------------------------\n";
echo "Results: {$passed}/{$total} passed ({$failed} failed) in {$elapsed}ms\n";
echo "--------------------------------------------------------\n";

if ( ! empty( $errors ) ) {
	echo "\nFailures details:\n";
	foreach ( $errors as $err ) {
		echo "- [{$err['suite']}::{$err['test']}] {$err['message']} at {$err['file']}:{$err['line']}\n";
	}
	exit( 1 );
}

echo "\nAll tests passed successfully! ✅\n";
exit( 0 );
