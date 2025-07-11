<?php

namespace BuiltNorth\Schema\Defaults;

/**
 * Custom Post Type UI Integration
 * 
 * Provides automatic schema generation for custom post types created with CPTUI.
 */
class CPTUIIntegration extends BaseIntegration
{
    /**
     * Integration name
     *
     * @var string
     */
    protected static $integration_name = 'cptui';

    /**
     * Register WordPress hooks for CPTUI integration
     *
     * @return void
     */
    protected static function register_hooks()
    {
        // Override schema type for custom post types
        add_filter('wp_schema_type_for_post', [self::class, 'override_schema_type'], 10, 3);
        
        // Provide schema data for custom post types
        add_filter('wp_schema_data_for_post', [self::class, 'provide_cpt_data'], 10, 4);
    }

    /**
     * Override schema type for custom post types
     *
     * @param string|null $schema_type Current schema type
     * @param int $post_id Post ID
     * @param array $options Generation options
     * @return string|null Schema type
     */
    public static function override_schema_type($schema_type, $post_id, $options)
    {
        $post_type = get_post_type($post_id);
        
        // Skip core post types
        if (in_array($post_type, ['post', 'page', 'attachment'])) {
            return $schema_type;
        }

        // Map common custom post type names to schema types
        $cpt_schema_map = [
            'product' => 'Product',
            'event' => 'Event',
            'recipe' => 'Recipe',
            'restaurant' => 'Restaurant',
            'business' => 'LocalBusiness',
            'service' => 'Service',
            'review' => 'Review',
            'book' => 'Book',
            'movie' => 'Movie',
            'music' => 'MusicRecording',
            'faq' => 'FAQPage',
            'person' => 'Person',
            'organization' => 'Organization',
            'news' => 'NewsArticle',
            'job' => 'JobPosting',
            'course' => 'Course',
            'software' => 'SoftwareApplication',
            'game' => 'Game'
        ];

        return $cpt_schema_map[$post_type] ?? $schema_type;
    }

    /**
     * Provide schema data for custom post types
     *
     * @param array|null $custom_data Custom data
     * @param int $post_id Post ID
     * @param string $schema_type Schema type
     * @param array $options Generation options
     * @return array|null Schema data
     */
    public static function provide_cpt_data($custom_data, $post_id, $schema_type, $options)
    {
        $post_type = get_post_type($post_id);
        
        // Skip core post types
        if (in_array($post_type, ['post', 'page', 'attachment'])) {
            return $custom_data;
        }

        // CPTUI integration would go here
        // This is a placeholder for future implementation
        
        return $custom_data;
    }

    /**
     * Check if CPTUI is available
     *
     * @return bool
     */
    public static function is_available()
    {
        return class_exists('cptui_admin_ui');
    }

    /**
     * Get integration description
     *
     * @return string
     */
    public static function get_description()
    {
        return 'Schema for custom post types';
    }

    /**
     * Get supported schema types
     *
     * @return array
     */
    public static function get_supported_schema_types()
    {
        return ['Product', 'Event', 'Recipe', 'Restaurant', 'LocalBusiness', 'Service', 'Review', 'Book', 'Movie', 'MusicRecording', 'FAQPage', 'Person', 'Organization', 'NewsArticle', 'JobPosting', 'Course', 'SoftwareApplication', 'Game'];
    }
} 