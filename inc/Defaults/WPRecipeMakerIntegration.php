<?php

namespace BuiltNorth\Schema\Defaults;

/**
 * WP Recipe Maker Integration
 * 
 * Provides automatic schema generation for WP Recipe Maker recipes.
 */
class WPRecipeMakerIntegration extends BaseIntegration
{
    /**
     * Integration name
     *
     * @var string
     */
    protected static $integration_name = 'wp_recipe_maker';

    /**
     * Register WordPress hooks for WP Recipe Maker integration
     *
     * @return void
     */
    protected static function register_hooks()
    {
        // Override schema type for recipes
        add_filter('wp_schema_type_for_post', [self::class, 'override_schema_type'], 10, 3);
        
        // Provide schema data for recipes
        add_filter('wp_schema_data_for_post', [self::class, 'provide_recipe_data'], 10, 4);
    }

    /**
     * Override schema type for recipes
     *
     * @param string|null $schema_type Current schema type
     * @param int $post_id Post ID
     * @param array $options Generation options
     * @return string|null Schema type
     */
    public static function override_schema_type($schema_type, $post_id, $options)
    {
        // Check if post has WPRM recipe
        if (class_exists('WPRM_Recipe') && WPRM_Recipe_Manager::get_recipe_ids_from_post($post_id)) {
            return 'Recipe';
        }
        
        return $schema_type;
    }

    /**
     * Provide schema data for recipes
     *
     * @param array|null $custom_data Custom data
     * @param int $post_id Post ID
     * @param string $schema_type Schema type
     * @param array $options Generation options
     * @return array|null Schema data
     */
    public static function provide_recipe_data($custom_data, $post_id, $schema_type, $options)
    {
        if ($schema_type !== 'Recipe') {
            return $custom_data;
        }

        // WP Recipe Maker integration would go here
        // This is a placeholder for future implementation
        
        return $custom_data;
    }

    /**
     * Check if WP Recipe Maker is available
     *
     * @return bool
     */
    public static function is_available()
    {
        return class_exists('WPRM_Recipe');
    }

    /**
     * Get integration description
     *
     * @return string
     */
    public static function get_description()
    {
        return 'Recipe schema for cooking recipes';
    }

    /**
     * Get supported schema types
     *
     * @return array
     */
    public static function get_supported_schema_types()
    {
        return ['Recipe'];
    }
} 