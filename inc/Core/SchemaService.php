<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Core;

use BuiltNorth\Schema\Contracts\SchemaProviderInterface;

/**
 * Schema Service
 * 
 * Main orchestrator for schema generation using simplified Polaris-style approach.
 * Supports both provider classes and simple filter fallback for maximum flexibility.
 * 
 * @since 3.0.0
 */
class SchemaService
{
    private ProviderManager $providerManager;
    private PieceAssembler $assembler;
    private bool $initialized = false;
    
    public function __construct()
    {
        $this->providerManager = new ProviderManager();
        $this->assembler = new PieceAssembler();
    }
    
    /**
     * Initialize the service and register providers
     */
    public function init(): void
    {
        if ($this->initialized) {
            return;
        }
        
        // Register built-in providers first
        $this->register_core_providers();
        
        // Let plugins register their providers via hook
        do_action('wp_schema_register_providers', $this->providerManager);
        
        $this->initialized = true;
    }
    
    /**
     * Register core WordPress providers
     */
    private function register_core_providers(): void
    {
        // Polaris organization data (highest priority)
        $this->providerManager->register(
            'polaris_organization',
            'BuiltNorth\\Schema\\Integrations\\PolarisOrganizationProvider'
        );
        
        // WordPress core - WebSite and WebPage schema
        $this->providerManager->register(
            'wordpress_core',
            'BuiltNorth\\Schema\\Integrations\\WordPressCoreProvider'
        );
        
        // WordPress core navigation
        $this->providerManager->register(
            'core_navigation',
            'BuiltNorth\\Schema\\Integrations\\CoreNavigationProvider'
        );
    }
    
    /**
     * Generate schema for current context
     *
     * @param string|null $context Override context detection
     * @param array $options Additional options
     * @return array Complete schema graph
     */
    public function render_for_context(?string $context = null, array $options = []): array
    {
        $this->init();
        
        // Detect context if not provided
        if ($context === null) {
            $context = $this->get_current_context();
        }
        
        $pieces = [];
        
        // Collect pieces from provider classes
        try {
            foreach ($this->providerManager->get_for_context($context, $options) as $provider) {
                $provider_pieces = $provider->get_pieces($context, $options);
                if (is_array($provider_pieces)) {
                    $pieces = array_merge($pieces, $provider_pieces);
                }
            }
        } catch (\Exception $e) {
            error_log('Schema provider error: ' . $e->getMessage());
        }
        
        // Also collect pieces from simple filter (plugin fallback)
        $filter_pieces = apply_filters('wp_schema_pieces', [], $context, $options);
        if (is_array($filter_pieces)) {
            $pieces = array_merge($pieces, $filter_pieces);
        }
        
        // Assemble into Yoast-style referenced graph
        return $this->assembler->assemble($pieces);
    }
    
    /**
     * Generate schema for a specific post
     *
     * @param int $post_id Post ID
     * @param array $options Additional options
     * @return array Schema pieces for the post
     */
    public function render_for_post(int $post_id, array $options = []): array
    {
        // Set up post context
        $original_post = $GLOBALS['post'] ?? null;
        $GLOBALS['post'] = get_post($post_id);
        
        $context = 'singular';
        $options['post_id'] = $post_id;
        
        $schema = $this->render_for_context($context, $options);
        
        // Restore original post
        $GLOBALS['post'] = $original_post;
        
        return $schema;
    }
    
    /**
     * Generate schema for a block
     *
     * @param array $block Block data
     * @param array $options Additional options
     * @return array Schema pieces for the block
     */
    public function render_for_block(array $block, array $options = []): array
    {
        $context = 'block';
        $options['block'] = $block;
        
        return $this->render_for_context($context, $options);
    }
    
    /**
     * Get current WordPress context
     *
     * @return string Context identifier
     */
    private function get_current_context(): string
    {
        if (is_front_page()) {
            return 'home';
        }
        
        if (is_singular()) {
            return 'singular';
        }
        
        if (is_archive() || is_home()) {
            return 'archive';
        }
        
        if (is_search()) {
            return 'search';
        }
        
        if (is_404()) {
            return '404';
        }
        
        return 'unknown';
    }
    
    /**
     * Register a provider programmatically
     *
     * @param string $id Provider ID
     * @param string $class_name Provider class name
     * @return void
     */
    public function register_provider(string $id, string $class_name): void
    {
        $this->providerManager->register($id, $class_name);
    }
    
    /**
     * Get provider manager (for advanced usage)
     *
     * @return ProviderManager
     */
    public function get_provider_manager(): ProviderManager
    {
        return $this->providerManager;
    }
    
    /**
     * Get piece assembler (for advanced usage)
     *
     * @return PieceAssembler
     */
    public function get_assembler(): PieceAssembler
    {
        return $this->assembler;
    }
    
    /**
     * Validate schema graph references
     *
     * @param array $schema Schema graph to validate
     * @return array Validation results
     */
    public function validate_schema(array $schema): array
    {
        return $this->assembler->validate_references($schema);
    }
}