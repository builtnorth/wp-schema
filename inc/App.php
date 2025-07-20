<?php

declare(strict_types=1);

namespace BuiltNorth\Schema;

use BuiltNorth\Schema\Services\ProviderRegistry;
use BuiltNorth\Schema\Services\GraphBuilder;
use BuiltNorth\Schema\Services\OutputService;
use BuiltNorth\Schema\Services\ContextDetector;
use BuiltNorth\Schema\Services\SchemaTypeRegistry;

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
        do_action('wp_schema_register_providers', $this);
        
        $this->initialized = true;
        
        // Framework is ready
        do_action('wp_schema_ready', $this);
        
        // Add filter to provide available schema types for UI
        add_filter('wp_schema_available_types', [$this->type_registry, 'get_available_types']);
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
            'organization' => 'BuiltNorth\\Schema\\Providers\\OrganizationProvider',
            'website' => 'BuiltNorth\\Schema\\Providers\\WebsiteProvider',
            'author' => 'BuiltNorth\\Schema\\Providers\\AuthorProvider',
            'article' => 'BuiltNorth\\Schema\\Providers\\ArticleProvider',
            'webpage' => 'BuiltNorth\\Schema\\Providers\\WebPageProvider',
            'archive' => 'BuiltNorth\\Schema\\Providers\\ArchiveProvider',
            'search' => 'BuiltNorth\\Schema\\Providers\\SearchResultsProvider',
            'media' => 'BuiltNorth\\Schema\\Providers\\MediaProvider',
            'page_type' => 'BuiltNorth\\Schema\\Providers\\PageTypeProvider',
            'comment' => 'BuiltNorth\\Schema\\Providers\\CommentProvider',
            'navigation' => 'BuiltNorth\\Schema\\Providers\\NavigationProvider',
            'logo' => 'BuiltNorth\\Schema\\Providers\\LogoProvider',
            'site_icon' => 'BuiltNorth\\Schema\\Providers\\SiteIconProvider',
            'generic' => 'BuiltNorth\\Schema\\Providers\\GenericSchemaProvider',
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