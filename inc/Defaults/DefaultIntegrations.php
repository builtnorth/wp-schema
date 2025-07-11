<?php

namespace BuiltNorth\Schema\Defaults;

/**
 * Default Integrations Manager
 * 
 * Manages pre-built schema integrations for popular WordPress plugins and blocks.
 * This provides automatic schema generation for common use cases while maintaining
 * the flexibility of the hook system.
 */
class DefaultIntegrations
{
    /**
     * Initialize all default integrations
     *
     * @return void
     */
    public static function init()
    {
        // WooCommerce integration
        if (class_exists('WooCommerce')) {
            WooCommerceIntegration::init();
        }

        // Easy Digital Downloads integration
        if (class_exists('Easy_Digital_Downloads')) {
            EasyDigitalDownloadsIntegration::init();
        }

        // The Events Calendar integration
        if (class_exists('Tribe__Events__Main')) {
            EventsCalendarIntegration::init();
        }

        // WP Recipe Maker integration
        if (class_exists('WPRM_Recipe')) {
            WPRecipeMakerIntegration::init();
        }

        // Advanced Custom Fields integration
        if (class_exists('ACF')) {
            ACFIntegration::init();
        }

        // Custom Post Type UI integration
        if (class_exists('cptui_admin_ui')) {
            CPTUIIntegration::init();
        }

        // Gutenberg core blocks integration
        CoreBlocksIntegration::init();



        // WordPress core post types integration
        WordPressCoreIntegration::init();

        // Polaris Organization integration (global business data)
        if (class_exists('Polaris\Schema\PolarisOrganizationIntegration')) {
            \Polaris\Schema\PolarisOrganizationIntegration::init();
        }

        // Polaris Blocks integration
        if (class_exists('PolarisBlocks\App')) {
            PolarisBlocksIntegration::init();
        }
    }

    /**
     * Get list of available integrations
     *
     * @return array List of integration names and their status
     */
    public static function get_available_integrations()
    {
        $integrations = [
            'woocommerce' => [
                'name' => 'WooCommerce',
                'available' => class_exists('WooCommerce'),
                'description' => 'Product, Review, and Organization schema for WooCommerce'
            ],
            'edd' => [
                'name' => 'Easy Digital Downloads',
                'available' => class_exists('Easy_Digital_Downloads'),
                'description' => 'Product schema for digital downloads'
            ],
            'events_calendar' => [
                'name' => 'The Events Calendar',
                'available' => class_exists('Tribe__Events__Main'),
                'description' => 'Event schema for calendar events'
            ],
            'wp_recipe_maker' => [
                'name' => 'WP Recipe Maker',
                'available' => class_exists('WPRM_Recipe'),
                'description' => 'Recipe schema for cooking recipes'
            ],
            'acf' => [
                'name' => 'Advanced Custom Fields',
                'available' => class_exists('ACF'),
                'description' => 'Schema from ACF custom fields'
            ],
            'cptui' => [
                'name' => 'Custom Post Type UI',
                'available' => class_exists('cptui_admin_ui'),
                'description' => 'Schema for custom post types'
            ],
            'core_blocks' => [
                'name' => 'Gutenberg Core Blocks',
                'available' => true,
                'description' => 'Schema for WordPress core blocks'
            ],

            'wordpress_core' => [
                'name' => 'WordPress Core',
                'available' => true,
                'description' => 'Schema for WordPress core post types'
            ],
            'polaris_organization' => [
                'name' => 'Polaris Organization',
                'available' => class_exists('Polaris\Schema\PolarisOrganizationIntegration'),
                'description' => 'Organization and business schema data from Polaris organization settings (contact info, address, hours, social media, business type)'
            ],
            'polaris_blocks' => [
                'name' => 'Polaris Blocks',
                'available' => class_exists('PolarisBlocks\App'),
                'description' => 'Schema for Polaris Blocks plugin blocks (accordion, map, contact info, social media, etc.)'
            ]
        ];

        return apply_filters('wp_schema_available_integrations', $integrations);
    }

    /**
     * Enable/disable specific integrations
     *
     * @param string $integration Integration name
     * @param bool $enabled Whether to enable or disable
     * @return void
     */
    public static function toggle_integration($integration, $enabled = true)
    {
        $option_name = 'wp_schema_integration_' . $integration;
        
        if ($enabled) {
            update_option($option_name, true);
        } else {
            delete_option($option_name);
        }
    }

    /**
     * Check if an integration is enabled
     *
     * @param string $integration Integration name
     * @return bool Whether integration is enabled
     */
    public static function is_integration_enabled($integration)
    {
        $option_name = 'wp_schema_integration_' . $integration;
        return get_option($option_name, true); // Default to enabled
    }

    /**
     * Get integration class name
     *
     * @param string $integration Integration name
     * @return string|null Class name or null if not found
     */
    public static function get_integration_class($integration)
    {
        $classes = [
            'woocommerce' => 'WooCommerceIntegration',
            'edd' => 'EasyDigitalDownloadsIntegration',
            'events_calendar' => 'EventsCalendarIntegration',
            'wp_recipe_maker' => 'WPRecipeMakerIntegration',
            'acf' => 'ACFIntegration',
            'cptui' => 'CPTUIIntegration',
            'core_blocks' => 'CoreBlocksIntegration',

            'wordpress_core' => 'WordPressCoreIntegration',
            'polaris_organization' => 'PolarisOrganizationIntegration',
            'polaris_blocks' => 'PolarisBlocksIntegration'
        ];

        $class_name = $classes[$integration] ?? null;
        
        if ($class_name) {
            $full_class = 'BuiltNorth\Schema\Defaults\\' . $class_name;
            return class_exists($full_class) ? $full_class : null;
        }

        return null;
    }
} 