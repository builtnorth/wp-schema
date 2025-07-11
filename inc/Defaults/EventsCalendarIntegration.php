<?php

namespace BuiltNorth\Schema\Defaults;

/**
 * The Events Calendar Integration
 * 
 * Provides automatic schema generation for The Events Calendar events.
 */
class EventsCalendarIntegration extends BaseIntegration
{
    /**
     * Integration name
     *
     * @var string
     */
    protected static $integration_name = 'events_calendar';

    /**
     * Register WordPress hooks for Events Calendar integration
     *
     * @return void
     */
    protected static function register_hooks()
    {
        // Override schema type for events
        add_filter('wp_schema_type_for_post', [self::class, 'override_schema_type'], 10, 3);
        
        // Provide schema data for events
        add_filter('wp_schema_data_for_post', [self::class, 'provide_event_data'], 10, 4);
    }

    /**
     * Override schema type for events
     *
     * @param string|null $schema_type Current schema type
     * @param int $post_id Post ID
     * @param array $options Generation options
     * @return string|null Schema type
     */
    public static function override_schema_type($schema_type, $post_id, $options)
    {
        $post_type = get_post_type($post_id);
        
        if ($post_type === 'tribe_events') {
            return 'Event';
        }
        
        return $schema_type;
    }

    /**
     * Provide schema data for events
     *
     * @param array|null $custom_data Custom data
     * @param int $post_id Post ID
     * @param string $schema_type Schema type
     * @param array $options Generation options
     * @return array|null Schema data
     */
    public static function provide_event_data($custom_data, $post_id, $schema_type, $options)
    {
        if ($schema_type !== 'Event' || get_post_type($post_id) !== 'tribe_events') {
            return $custom_data;
        }

        // Events Calendar integration would go here
        // This is a placeholder for future implementation
        
        return $custom_data;
    }

    /**
     * Check if Events Calendar is available
     *
     * @return bool
     */
    public static function is_available()
    {
        return class_exists('Tribe__Events__Main');
    }

    /**
     * Get integration description
     *
     * @return string
     */
    public static function get_description()
    {
        return 'Event schema for calendar events';
    }

    /**
     * Get supported schema types
     *
     * @return array
     */
    public static function get_supported_schema_types()
    {
        return ['Event'];
    }
} 