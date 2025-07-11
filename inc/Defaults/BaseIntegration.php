<?php

namespace BuiltNorth\Schema\Defaults;

/**
 * Base Integration Class
 * 
 * Abstract base class for all default integrations.
 * Provides common functionality and structure for plugin/block integrations.
 */
abstract class BaseIntegration
{
    /**
     * Integration name
     *
     * @var string
     */
    protected static $integration_name = '';

    /**
     * Initialize the integration
     *
     * @return void
     */
    public static function init()
    {
        // Check if integration is enabled
        if (!DefaultIntegrations::is_integration_enabled(static::$integration_name)) {
            return;
        }

        // Register hooks
        static::register_hooks();
    }

    /**
     * Register WordPress hooks for this integration
     *
     * @return void
     */
    abstract protected static function register_hooks();

    /**
     * Get integration name
     *
     * @return string
     */
    public static function get_integration_name()
    {
        return static::$integration_name;
    }

    /**
     * Check if this integration is available
     *
     * @return bool
     */
    abstract public static function is_available();

    /**
     * Get integration description
     *
     * @return string
     */
    abstract public static function get_description();

    /**
     * Get supported schema types
     *
     * @return array
     */
    abstract public static function get_supported_schema_types();

    /**
     * Enable this integration
     *
     * @return void
     */
    public static function enable()
    {
        DefaultIntegrations::toggle_integration(static::$integration_name, true);
    }

    /**
     * Disable this integration
     *
     * @return void
     */
    public static function disable()
    {
        DefaultIntegrations::toggle_integration(static::$integration_name, false);
    }

    /**
     * Check if integration is enabled
     *
     * @return bool
     */
    public static function is_enabled()
    {
        return DefaultIntegrations::is_integration_enabled(static::$integration_name);
    }
} 