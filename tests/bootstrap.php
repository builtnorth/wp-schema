<?php
/**
 * PHPUnit bootstrap file for WP Schema tests
 *
 * @package BuiltNorth\WPSchema
 */

// Suppress PHP 8.4 deprecation warnings from WP_Mock
error_reporting(E_ALL & ~E_DEPRECATED);

// Require Composer autoloader, falling back to the monorepo root's autoloader
// when this package has no standalone vendor/ install (the normal dev setup —
// see "Autoloader Architecture" in the root CLAUDE.md).
$autoloader = dirname(__DIR__) . '/vendor/autoload.php';
$using_root_autoloader = !file_exists($autoloader);
if ($using_root_autoloader) {
    $autoloader = dirname(__DIR__, 3) . '/vendor/autoload.php';
}
if (!file_exists($autoloader)) {
    die("Please run 'composer install' first.\n");
}
require_once $autoloader;

// The root autoloader only carries this package's own runtime `autoload` PSR-4
// mapping, never a dependency's `autoload-dev` — register the Tests namespace
// by hand when running under the root autoloader.
if ($using_root_autoloader) {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'BuiltNorth\\WPSchema\\Tests\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $relative = substr($class, strlen($prefix));
        $file     = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    });
}

// Minimal WP_Post stub.
//
// WP_Mock stubs functions, not classes, so code that typehints \WP_Post cannot
// be exercised with a plain stdClass. Only the properties this package actually
// reads are declared — it is a fixture, not a reimplementation.
if ( ! class_exists( 'WP_Post' ) ) {
	class WP_Post {
		public int $ID = 0;
		public string $post_type = 'post';
		public string $post_name = '';
		public string $post_title = '';
		public string $post_excerpt = '';
		public string $post_content = '';
		public int $post_author = 0;

		/**
		 * @param array<string, mixed> $props
		 */
		public function __construct( array $props = [] ) {
			foreach ( $props as $key => $value ) {
				$this->$key = $value;
			}
		}
	}
}

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