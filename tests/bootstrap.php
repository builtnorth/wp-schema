<?php
/**
 * PHPUnit bootstrap file for WP Schema tests
 */

// Require Composer autoloader
$autoloader = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($autoloader)) {
    die("Please run 'composer install' first.\n");
}
require_once $autoloader;

// Define test constants
define('WP_SCHEMA_TEST_MODE', true);

// Mock WordPress functions that the package might use
if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        // Mock implementation for testing
        global $wp_filter;
        if (!isset($wp_filter)) {
            $wp_filter = [];
        }
        $wp_filter[$hook][] = [
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $accepted_args
        ];
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
        return add_action($hook, $callback, $priority, $accepted_args);
    }
}

if (!function_exists('do_action')) {
    function do_action($hook, ...$args) {
        global $wp_filter;
        if (!isset($wp_filter[$hook])) {
            return;
        }
        foreach ($wp_filter[$hook] as $filter) {
            call_user_func_array($filter['callback'], array_slice($args, 0, $filter['accepted_args']));
        }
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook, $value, ...$args) {
        global $wp_filter;
        if (!isset($wp_filter[$hook])) {
            return $value;
        }
        foreach ($wp_filter[$hook] as $filter) {
            $value = call_user_func_array($filter['callback'], array_merge([$value], array_slice($args, 0, $filter['accepted_args'] - 1)));
        }
        return $value;
    }
}

if (!function_exists('home_url')) {
    function home_url($path = '') {
        return 'https://example.com' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('get_bloginfo')) {
    function get_bloginfo($show = '') {
        $info = [
            'name' => 'Test Site',
            'description' => 'Just another WordPress site',
            'url' => 'https://example.com',
            'wpurl' => 'https://example.com',
        ];
        return $info[$show] ?? 'Test Site';
    }
}

if (!function_exists('is_singular')) {
    function is_singular($post_types = '') {
        return false;
    }
}

if (!function_exists('is_home')) {
    function is_home() {
        return false;
    }
}

if (!function_exists('is_front_page')) {
    function is_front_page() {
        return false;
    }
}

if (!function_exists('is_archive')) {
    function is_archive() {
        return false;
    }
}

if (!function_exists('is_search')) {
    function is_search() {
        return false;
    }
}

if (!function_exists('is_404')) {
    function is_404() {
        return false;
    }
}

if (!function_exists('is_attachment')) {
    function is_attachment() {
        return false;
    }
}

if (!function_exists('get_post')) {
    function get_post($post = null) {
        return null;
    }
}

if (!function_exists('get_post_type')) {
    function get_post_type($post = null) {
        return 'post';
    }
}

if (!function_exists('get_the_ID')) {
    function get_the_ID() {
        return 1;
    }
}

if (!function_exists('get_the_title')) {
    function get_the_title($post = 0) {
        return 'Test Post Title';
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($post = 0) {
        return 'https://example.com/test-post/';
    }
}

if (!function_exists('get_the_excerpt')) {
    function get_the_excerpt($post = null) {
        return 'This is a test excerpt.';
    }
}

if (!function_exists('get_the_content')) {
    function get_the_content($more_link_text = null, $strip_teaser = false, $post = null) {
        return 'This is test content.';
    }
}

if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags($string, $remove_breaks = false) {
        $string = strip_tags($string);
        if ($remove_breaks) {
            $string = preg_replace('/[\r\n\t ]+/', ' ', $string);
        }
        return trim($string);
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        $options = [
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',
        ];
        return $options[$option] ?? $default;
    }
}

if (!function_exists('get_query_var')) {
    function get_query_var($var, $default = '') {
        return $default;
    }
}

if (!function_exists('get_search_query')) {
    function get_search_query($escaped = true) {
        return 'test search';
    }
}

if (!function_exists('current_theme_supports')) {
    function current_theme_supports($feature) {
        return false;
    }
}

if (!function_exists('get_theme_support')) {
    function get_theme_support($feature) {
        return false;
    }
}

if (!function_exists('has_custom_logo')) {
    function has_custom_logo() {
        return false;
    }
}

if (!function_exists('get_custom_logo')) {
    function get_custom_logo() {
        return '';
    }
}

if (!function_exists('get_theme_mod')) {
    function get_theme_mod($name, $default = false) {
        return $default;
    }
}

if (!function_exists('get_site_icon_url')) {
    function get_site_icon_url($size = 512, $url = '', $blog_id = 0) {
        return '';
    }
}

if (!function_exists('get_user_by')) {
    function get_user_by($field, $value) {
        return false;
    }
}

if (!function_exists('get_the_author_meta')) {
    function get_the_author_meta($field = '', $user_id = false) {
        return '';
    }
}

if (!function_exists('get_author_posts_url')) {
    function get_author_posts_url($author_id, $author_nicename = '') {
        return 'https://example.com/author/test/';
    }
}

if (!function_exists('get_avatar_url')) {
    function get_avatar_url($id_or_email, $args = null) {
        return 'https://example.com/avatar.jpg';
    }
}

// Output a message to confirm bootstrap is loaded
echo "WP Schema test bootstrap loaded.\n";