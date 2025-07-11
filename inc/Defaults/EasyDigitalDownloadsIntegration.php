<?php

namespace BuiltNorth\Schema\Defaults;

/**
 * Easy Digital Downloads Integration
 * 
 * Provides automatic schema generation for Easy Digital Downloads products.
 */
class EasyDigitalDownloadsIntegration extends BaseIntegration
{
    /**
     * Integration name
     *
     * @var string
     */
    protected static $integration_name = 'edd';

    /**
     * Register WordPress hooks for EDD integration
     *
     * @return void
     */
    protected static function register_hooks()
    {
        // Override schema type for EDD products
        add_filter('wp_schema_type_for_post', [self::class, 'override_schema_type'], 10, 3);
        
        // Provide schema data for EDD products
        add_filter('wp_schema_data_for_post', [self::class, 'provide_product_data'], 10, 4);
    }

    /**
     * Override schema type for EDD products
     *
     * @param string|null $schema_type Current schema type
     * @param int $post_id Post ID
     * @param array $options Generation options
     * @return string|null Schema type
     */
    public static function override_schema_type($schema_type, $post_id, $options)
    {
        $post_type = get_post_type($post_id);
        
        if ($post_type === 'download') {
            return 'Product';
        }
        
        return $schema_type;
    }

    /**
     * Provide schema data for EDD products
     *
     * @param array|null $custom_data Custom data
     * @param int $post_id Post ID
     * @param string $schema_type Schema type
     * @param array $options Generation options
     * @return array|null Schema data
     */
    public static function provide_product_data($custom_data, $post_id, $schema_type, $options)
    {
        if ($schema_type !== 'Product' || get_post_type($post_id) !== 'download') {
            return $custom_data;
        }

        // EDD integration would go here
        // This is a placeholder for future implementation
        
        return $custom_data;
    }

    /**
     * Check if EDD is available
     *
     * @return bool
     */
    public static function is_available()
    {
        return class_exists('Easy_Digital_Downloads');
    }

    /**
     * Get integration description
     *
     * @return string
     */
    public static function get_description()
    {
        return 'Product schema for digital downloads';
    }

    /**
     * Get supported schema types
     *
     * @return array
     */
    public static function get_supported_schema_types()
    {
        return ['Product'];
    }
} 