<?php
/**
 * PHPUnit bootstrap file for WP Schema tests
 *
 * @package BuiltNorth\WPSchema
 */

// Suppress PHP 8.4 deprecation warnings from WP_Mock
error_reporting(E_ALL & ~E_DEPRECATED);

// Require Composer autoloader
$autoloader = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($autoloader)) {
    die("Please run 'composer install' first.\n");
}
require_once $autoloader;

// Bootstrap WP_Mock
WP_Mock::bootstrap();

// Define test constants
define('WP_SCHEMA_TEST_MODE', true);

// Define WordPress constants that may be used in the code
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}

if ( ! defined( 'WP_CONTENT_URL' ) ) {
	define( 'WP_CONTENT_URL', 'http://example.com/wp-content' );
}

// Output a message to confirm bootstrap is loaded
echo "WP Schema test bootstrap loaded with WP_Mock.\n";