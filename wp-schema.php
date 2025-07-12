<?php
/**
 * WP Schema
 * 
 * Simple, powerful schema markup for WordPress.
 * 
 * @package     BuiltNorth\Schema
 * @since       3.0.0
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_SCHEMA_VERSION', '3.0.0');
define('WP_SCHEMA_PATH', __DIR__);
define('WP_SCHEMA_URL', plugin_dir_url(__FILE__));

// Autoloader for classes (if not using Composer)
spl_autoload_register(function ($class) {
    if (strpos($class, 'BuiltNorth\\Schema\\') !== 0) {
        return;
    }
    
    $relative_class = str_replace('BuiltNorth\\Schema\\', '', $class);
    $file = __DIR__ . '/inc/' . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialize the plugin
function wp_schema_init() {
    \BuiltNorth\Schema\App::instance();
}

// Hook initialization
add_action('init', 'wp_schema_init', 20);

// Provide global access functions
function wp_schema() {
    return \BuiltNorth\Schema\App::instance();
}

function wp_schema_get_service() {
    return wp_schema()->get_schema_service();
}

function wp_schema_render_for_context(?string $context = null, array $options = []) {
    return wp_schema_get_service()->render_for_context($context, $options);
}