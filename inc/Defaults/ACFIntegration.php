<?php

namespace BuiltNorth\Schema\Defaults;

/**
 * Advanced Custom Fields Integration
 * 
 * Provides automatic schema generation from ACF custom fields.
 */
class ACFIntegration extends BaseIntegration
{
    /**
     * Integration name
     *
     * @var string
     */
    protected static $integration_name = 'acf';

    /**
     * Register WordPress hooks for ACF integration
     *
     * @return void
     */
    protected static function register_hooks()
    {
        // Provide schema data from ACF fields
        add_filter('wp_schema_data_for_post', [self::class, 'provide_acf_data'], 10, 4);
        
        // Override schema type based on ACF fields
        add_filter('wp_schema_type_for_post', [self::class, 'override_schema_type'], 10, 3);
    }

    /**
     * Override schema type based on ACF fields
     *
     * @param string|null $schema_type Current schema type
     * @param int $post_id Post ID
     * @param array $options Generation options
     * @return string|null Schema type
     */
    public static function override_schema_type($schema_type, $post_id, $options)
    {
        if (!function_exists('get_field')) {
            return $schema_type;
        }

        // Check for common ACF field patterns that indicate schema type
        $schema_type_field = get_field('schema_type', $post_id);
        if ($schema_type_field) {
            return $schema_type_field;
        }

        // Check for other ACF fields that might indicate schema type
        if (get_field('recipe_ingredients', $post_id)) {
            return 'Recipe';
        }

        if (get_field('event_date', $post_id)) {
            return 'Event';
        }

        if (get_field('product_price', $post_id)) {
            return 'Product';
        }

        return $schema_type;
    }

    /**
     * Provide schema data from ACF fields
     *
     * @param array|null $custom_data Custom data
     * @param int $post_id Post ID
     * @param string $schema_type Schema type
     * @param array $options Generation options
     * @return array|null Schema data
     */
    public static function provide_acf_data($custom_data, $post_id, $schema_type, $options)
    {
        if (!function_exists('get_field')) {
            return $custom_data;
        }

        // ACF integration would go here
        // This is a placeholder for future implementation
        
        return $custom_data;
    }

    /**
     * Check if ACF is available
     *
     * @return bool
     */
    public static function is_available()
    {
        return class_exists('ACF');
    }

    /**
     * Get integration description
     *
     * @return string
     */
    public static function get_description()
    {
        return 'Schema from ACF custom fields';
    }

    /**
     * Get supported schema types
     *
     * @return array
     */
    public static function get_supported_schema_types()
    {
        return ['Article', 'Product', 'Event', 'Recipe', 'Person', 'Organization', 'LocalBusiness'];
    }
} 