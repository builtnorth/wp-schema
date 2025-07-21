<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema;

use BuiltNorth\WPSchema\Services\ProviderRegistry;
use BuiltNorth\WPSchema\Services\GraphBuilder;
use BuiltNorth\WPSchema\Services\OutputService;
use BuiltNorth\WPSchema\Services\ContextDetector;
use BuiltNorth\WPSchema\Services\SchemaTypeRegistry;

/**
 * Main App class for WP Schema package
 * 
 * Clean architecture with atomic schema pieces.
 * 
 * @since 3.0.0
 */
class App
{
    private static ?App $instance = null;
    private bool $initialized = false;
    
    private ProviderRegistry $registry;
    private GraphBuilder $graph_builder;
    private OutputService $output_service;
    private ContextDetector $context_detector;
    private SchemaTypeRegistry $type_registry;

    /**
     * Get singleton instance
     */
    public static function instance(): App
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the application
     */
    public function init(): void
    {
        if ($this->initialized) {
            return;
        }

        // Initialize services
        $this->registry = new ProviderRegistry();
        $this->context_detector = new ContextDetector();
        $this->type_registry = new SchemaTypeRegistry();
        $this->graph_builder = new GraphBuilder($this->registry);
        $this->output_service = new OutputService($this->graph_builder, $this->context_detector);
        
        // Register core providers
        $this->register_core_providers();
        
        // Initialize output hooks
        $this->output_service->init();
        
        // Allow plugins to register providers
        do_action('wp_schema_framework_register_providers', $this);
        
        $this->initialized = true;
        
        // Framework is ready
        do_action('wp_schema_framework_ready', $this);
        
        // Add filter to provide available schema types for UI
        add_filter('wp_schema_framework_available_types', [$this->type_registry, 'get_available_types']);
    }

    /**
     * Static initialization helper
     */
    public static function initialize(): App
    {
        $instance = self::instance();
        $instance->init();
        return $instance;
    }

    /**
     * Register core providers
     */
    private function register_core_providers(): void
    {
        $core_providers = [
            'organization' => 'BuiltNorth\\WPSchema\\Providers\\OrganizationProvider',
            'website' => 'BuiltNorth\\WPSchema\\Providers\\WebsiteProvider',
            'author' => 'BuiltNorth\\WPSchema\\Providers\\AuthorProvider',
            'article' => 'BuiltNorth\\WPSchema\\Providers\\ArticleProvider',
            'webpage' => 'BuiltNorth\\WPSchema\\Providers\\WebPageProvider',
            'archive' => 'BuiltNorth\\WPSchema\\Providers\\ArchiveProvider',
            'search' => 'BuiltNorth\\WPSchema\\Providers\\SearchResultsProvider',
            'media' => 'BuiltNorth\\WPSchema\\Providers\\MediaProvider',
            'page_type' => 'BuiltNorth\\WPSchema\\Providers\\PageTypeProvider',
            'comment' => 'BuiltNorth\\WPSchema\\Providers\\CommentProvider',
            'navigation' => 'BuiltNorth\\WPSchema\\Providers\\NavigationProvider',
            'logo' => 'BuiltNorth\\WPSchema\\Providers\\LogoProvider',
            'site_icon' => 'BuiltNorth\\WPSchema\\Providers\\SiteIconProvider',
            'generic' => 'BuiltNorth\\WPSchema\\Providers\\GenericSchemaProvider',
        ];
        
        foreach ($core_providers as $name => $class) {
            if (class_exists($class)) {
                $this->registry->register($name, new $class());
            }
        }
    }

    /**
     * Register a provider (simple 2-line method for plugins)
     */
    public static function register_provider(string $name, string $class_name): bool
    {
        $instance = self::instance();
        
        if (!class_exists($class_name)) {
            return false;
        }
        
        try {
            $provider = new $class_name();
            $instance->registry->register($name, $provider);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get registry
     */
    public function get_registry(): ProviderRegistry
    {
        return $this->registry;
    }

    /**
     * Get graph builder
     */
    public function get_graph_builder(): GraphBuilder
    {
        return $this->graph_builder;
    }

    /**
     * Check if initialized
     */
    public function is_initialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Get type registry
     */
    public function get_type_registry(): SchemaTypeRegistry
    {
        return $this->type_registry;
    }
}