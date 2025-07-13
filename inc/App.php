<?php

declare(strict_types=1);

namespace BuiltNorth\Schema;

use BuiltNorth\Schema\Core\SchemaService;
use BuiltNorth\Schema\Core\OutputService;

/**
 * Main App class for WP Schema package
 * 
 * Updated to use the new simplified architecture.
 * Provides both new architecture and legacy compatibility methods.
 * 
 * @since 3.0.0
 */
class App
{
    /**
     * Singleton instance
     *
     * @var App|null
     */
    private static $instance = null;

    /**
     * Schema service instance
     *
     * @var SchemaService
     */
    private SchemaService $schemaService;

    /**
     * Output service instance
     *
     * @var OutputService
     */
    private OutputService $outputService;

    /**
     * Initialization flag
     *
     * @var bool
     */
    private bool $initialized = false;

    /**
     * Get singleton instance (without auto-initialization)
     *
     * @return App
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initialize the application (must be called explicitly)
     *
     * @return void
     */
    public function init()
    {
        if ($this->initialized) {
            return;
        }

        // Initialize services
        $this->schemaService = new SchemaService();
        $this->outputService = new OutputService($this->schemaService);
        
        // Initialize services
        $this->schemaService->init();
        $this->outputService->init();
        
        // Framework is ready
        do_action('wp_schema_ready');
        
        $this->initialized = true;
    }

    /**
     * Static initialization helper
     *
     * @return App
     */
    public static function initialize(): App
    {
        $instance = self::instance();
        $instance->init();
        return $instance;
    }

    /**
     * Get schema service
     *
     * @return SchemaService
     */
    public function get_schema_service(): SchemaService
    {
        return $this->schemaService;
    }

    /**
     * Get output service
     *
     * @return OutputService
     */
    public function get_output_service(): OutputService
    {
        return $this->outputService;
    }

    /**
     * Generate schema for current page
     *
     * @param array $options Generation options
     * @return array Schema pieces
     */
    public function generate_schema(array $options = []): array
    {
        if (!$this->initialized) {
            trigger_error('App must be initialized before generating schema. Call init() first.', E_USER_WARNING);
            return [];
        }
        
        return $this->schemaService->render_for_context(null, $options);
    }

    /**
     * Check if app is initialized
     *
     * @return bool
     */
    public function is_initialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Generate schema for current context (static helper)
     *
     * @param array $options Generation options
     * @return array Schema pieces
     */
    public static function generate(array $options = []): array
    {
        return self::instance()->generate_schema($options);
    }

    /**
     * Get schema service (static helper)
     *
     * @return SchemaService
     */
    public static function schema_service(): SchemaService
    {
        return self::instance()->get_schema_service();
    }

    /**
     * Get output service (static helper)
     *
     * @return OutputService
     */
    public static function output_service(): OutputService
    {
        return self::instance()->get_output_service();
    }

    /**
     * Simple helper to register schema providers
     * This is the ONLY method plugin authors need to call
     *
     * @param string $name Provider name
     * @param string $class_name Provider class name
     * @return bool Success
     */
    public static function register_provider(string $name, string $class_name): bool
    {
        // Allow registration during initialization (when wp_schema_ready fires)
        $instance = self::instance();
        
        try {
            $provider_manager = $instance->get_schema_service()->get_provider_manager();
            $provider_manager->register($name, $class_name);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get schema type for a post type
     *
     * @param string $post_type WordPress post type
     * @param int|null $post_id Optional post ID for SEO overrides
     * @return string Schema.org type
     */
    public static function get_schema_type_for_post_type(string $post_type, ?int $post_id = null): string
    {
        // Get the post type schema provider
        $instance = self::instance();
        if (!$instance->is_initialized()) {
            $instance->init();
        }
        
        $provider_manager = $instance->get_schema_service()->get_provider_manager();
        
        // Get registered providers and find the post type provider
        $providers = $provider_manager->get_for_context('singular');
        foreach ($providers as $provider) {
            if ($provider instanceof \BuiltNorth\Schema\Integrations\PostTypeSchemaProvider) {
                $mappings = $provider->get_post_type_mappings();
                return $mappings[$post_type] ?? 'Article';
            }
        }
        
        // Fallback mapping
        $basic_mappings = [
            'post' => 'Article',
            'page' => 'WebPage', 
            'product' => 'Product',
            'event' => 'Event',
        ];
        
        return $basic_mappings[$post_type] ?? 'Article';
    }

    /**
     * Get all post type to schema type mappings
     *
     * @return array Mapping of post types to schema types
     */
    public static function get_post_type_mappings(): array
    {
        $instance = self::instance();
        if (!$instance->is_initialized()) {
            $instance->init();
        }
        
        $provider_manager = $instance->get_schema_service()->get_provider_manager();
        
        // Get registered providers and find the post type provider
        $providers = $provider_manager->get_for_context('singular');
        foreach ($providers as $provider) {
            if ($provider instanceof \BuiltNorth\Schema\Integrations\PostTypeSchemaProvider) {
                return $provider->get_post_type_mappings();
            }
        }
        
        return [];
    }
} 