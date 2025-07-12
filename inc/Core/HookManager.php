<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Core;

use BuiltNorth\Schema\Contracts\HookManagerInterface;
use BuiltNorth\Schema\Contracts\DataProviderInterface;

/**
 * Hook Manager
 * 
 * Manages WordPress hooks for schema generation.
 * Provides clean extension points for plugins.
 * 
 * @since 1.0.0
 */
class HookManager implements HookManagerInterface
{
    /** @var array<string> */
    private array $registeredHooks = [];
    
    public function __construct()
    {
        $this->registerHooks();
    }
    
    /**
     * Register all schema-related hooks
     */
    public function registerHooks(): void
    {
        if (!empty($this->registeredHooks)) {
            return; // Already registered
        }
        
        // Core schema generation hooks
        $this->addHook('wp_schema_before_generation', 'triggerBeforeGeneration', 10, 3);
        $this->addHook('wp_schema_after_generation', 'triggerAfterGeneration', 10, 3);
        
        // Data provider hooks
        $this->addHook('wp_schema_register_providers', 'registerProviders', 10, 1);
        $this->addHook('wp_schema_provider_data', 'filterProviderData', 10, 4);
        
        // Schema type hooks
        $this->addHook('wp_schema_register_types', 'registerSchemaTypes', 10, 1);
        $this->addHook('wp_schema_type_data', 'filterSchemaTypeData', 10, 3);
        
        // Context hooks
        $this->addHook('wp_schema_context_options', 'filterContextOptions', 10, 2);
        // Note: wp_schema_context_schemas filter is handled directly by SchemaGenerator to avoid recursion
        
        // Validation hooks
        $this->addHook('wp_schema_validation_rules', 'addValidationRules', 10, 2);
        $this->addHook('wp_schema_validation_result', 'filterValidationResult', 10, 3);
        
        // Cache hooks
        $this->addHook('wp_schema_cache_key', 'filterCacheKey', 10, 3);
        $this->addHook('wp_schema_cache_ttl', 'filterCacheTtl', 10, 2);
        
        // Output hooks
        $this->addHook('wp_schema_before_output', 'beforeOutput', 10, 2);
        $this->addHook('wp_schema_output_format', 'filterOutputFormat', 10, 2);
        
        // Plugin integration hooks
        $this->addHook('wp_schema_plugin_compatibility', 'handlePluginCompatibility', 10, 2);
        
        // Admin hooks
        $this->addHook('wp_schema_admin_notices', 'showAdminNotices', 10, 1);
        $this->addHook('wp_schema_debug_info', 'addDebugInfo', 10, 2);
        
        do_action('wp_schema_hooks_registered', $this);
    }
    
    /**
     * Apply filters for data provider registration
     */
    public function applyProviderFilters(array $providers): array
    {
        return apply_filters('wp_schema_register_providers', $providers);
    }
    
    /**
     * Apply filters for schema data before generation
     */
    public function applyDataFilters(array $data, string $context, array $options = []): array
    {
        return apply_filters('wp_schema_provider_data', $data, $context, $options, $this);
    }
    
    /**
     * Apply filters for generated schema before output
     */
    public function applySchemaFilters(array $schema, string $schemaType, array $data): array
    {
        return apply_filters('wp_schema_type_data', $schema, $schemaType, $data);
    }
    
    /**
     * Trigger action when schema is generated
     */
    public function triggerSchemaGenerated(array $schema, string $context, array $options = []): void
    {
        do_action('wp_schema_after_generation', $schema, $context, $options);
    }
    
    /**
     * Apply filters for cache key generation
     */
    public function applyCacheKeyFilters(string $cacheKey, string $context, array $options = []): string
    {
        return apply_filters('wp_schema_cache_key', $cacheKey, $context, $options);
    }
    
    /**
     * Filter context options
     */
    public function filterContextOptions(array $options, string $context): array
    {
        return apply_filters('wp_schema_context_options', $options, $context);
    }
    
    /**
     * Filter context schemas - internal method, doesn't call external filters
     */
    public function filterContextSchemas(array $schemas, string $context, array $options): array
    {
        // This method is called BY the wp_schema_context_schemas filter
        // It should NOT call the same filter again to avoid recursion
        // Just return the schemas as-is for now
        return $schemas;
    }
    
    /**
     * Filter cache TTL
     */
    public function filterCacheTtl(int $ttl, string $context): int
    {
        return apply_filters('wp_schema_cache_ttl', $ttl, $context);
    }
    
    /**
     * Trigger before generation action
     */
    public function triggerBeforeGeneration(string $context, array $options, $manager): void
    {
        do_action('wp_schema_before_generation', $context, $options, $manager);
    }
    
    /**
     * Trigger after generation action
     */
    public function triggerAfterGeneration(array $schemas, string $context, array $options): void
    {
        do_action('wp_schema_after_generation', $schemas, $context, $options);
    }
    
    /**
     * Allow plugins to register providers
     */
    public function registerProviders(array $providers): array
    {
        return apply_filters('wp_schema_register_providers', $providers);
    }
    
    /**
     * Filter provider data
     */
    public function filterProviderData(array $data, string $context, array $options, $manager): array
    {
        return apply_filters('wp_schema_provider_data', $data, $context, $options, $manager);
    }
    
    /**
     * Allow plugins to register schema types
     */
    public function registerSchemaTypes($registry)
    {
        do_action('wp_schema_register_types', $registry);
    }
    
    /**
     * Filter schema type data
     */
    public function filterSchemaTypeData(array $schema, string $schemaType, array $data): array
    {
        return apply_filters('wp_schema_type_data', $schema, $schemaType, $data);
    }
    
    /**
     * Add validation rules
     */
    public function addValidationRules(array $rules, string $schemaType): array
    {
        return apply_filters('wp_schema_validation_rules', $rules, $schemaType);
    }
    
    /**
     * Filter validation result
     */
    public function filterValidationResult($result, array $schema, string $schemaType)
    {
        return apply_filters('wp_schema_validation_result', $result, $schema, $schemaType);
    }
    
    /**
     * Before output hook
     */
    public function beforeOutput(array $schemas, array $options): void
    {
        do_action('wp_schema_before_output', $schemas, $options);
    }
    
    /**
     * Filter output format
     */
    public function filterOutputFormat(string $output, array $schemas): string
    {
        return apply_filters('wp_schema_output_format', $output, $schemas);
    }
    
    /**
     * Handle plugin compatibility
     */
    public function handlePluginCompatibility(array $compatibility, array $context): array
    {
        return apply_filters('wp_schema_plugin_compatibility', $compatibility, $context);
    }
    
    /**
     * Show admin notices
     */
    public function showAdminNotices(array $notices): void
    {
        do_action('wp_schema_admin_notices', $notices);
    }
    
    /**
     * Add debug info
     */
    public function addDebugInfo(array $info, string $context): array
    {
        return apply_filters('wp_schema_debug_info', $info, $context);
    }
    
    /**
     * Get all registered hooks
     */
    public function getRegisteredHooks(): array
    {
        return $this->registeredHooks;
    }
    
    /**
     * Check if a hook is registered
     */
    public function hasHook(string $hook): bool
    {
        return in_array($hook, $this->registeredHooks, true);
    }
    
    /**
     * Remove a registered hook
     */
    public function removeHook(string $hook, $callback, int $priority = 10): bool
    {
        $removed = remove_action($hook, $callback, $priority);
        
        if ($removed) {
            $this->registeredHooks = array_filter(
                $this->registeredHooks,
                fn($registeredHook) => $registeredHook !== $hook
            );
        }
        
        return $removed;
    }
    
    /**
     * Get hook documentation for developers
     */
    public function getHookDocumentation(): array
    {
        return [
            'wp_schema_before_generation' => [
                'description' => 'Fired before schema generation starts',
                'parameters' => ['context', 'options', 'manager'],
                'example' => "add_action('wp_schema_before_generation', function(\$context, \$options, \$manager) { /* your code */ });"
            ],
            'wp_schema_after_generation' => [
                'description' => 'Fired after schemas are generated',
                'parameters' => ['schemas', 'context', 'options'],
                'example' => "add_action('wp_schema_after_generation', function(\$schemas, \$context, \$options) { /* your code */ });"
            ],
            'wp_schema_register_providers' => [
                'description' => 'Filter to register new data providers',
                'parameters' => ['providers'],
                'example' => "add_filter('wp_schema_register_providers', function(\$providers) { \$providers[] = new MyProvider(); return \$providers; });"
            ],
            'wp_schema_provider_data' => [
                'description' => 'Filter data from providers before schema generation',
                'parameters' => ['data', 'context', 'options', 'manager'],
                'example' => "add_filter('wp_schema_provider_data', function(\$data, \$context, \$options, \$manager) { return \$data; });"
            ],
            'wp_schema_register_types' => [
                'description' => 'Action to register new schema types',
                'parameters' => ['registry'],
                'example' => "add_action('wp_schema_register_types', function(\$registry) { \$registry->registerSchemaType('MyType', MyGenerator::class); });"
            ],
            'wp_schema_type_data' => [
                'description' => 'Filter generated schema before output',
                'parameters' => ['schema', 'schemaType', 'originalData'],
                'example' => "add_filter('wp_schema_type_data', function(\$schema, \$type, \$data) { return \$schema; });"
            ],
            'wp_schema_context_schemas' => [
                'description' => 'Filter schemas for specific context',
                'parameters' => ['schemas', 'context', 'options'],
                'example' => "add_filter('wp_schema_context_schemas', function(\$schemas, \$context, \$options) { return \$schemas; });"
            ],
            'wp_schema_cache_key' => [
                'description' => 'Filter cache key generation',
                'parameters' => ['cacheKey', 'context', 'options'],
                'example' => "add_filter('wp_schema_cache_key', function(\$key, \$context, \$options) { return \$key; });"
            ],
            'wp_schema_before_output' => [
                'description' => 'Action fired before schema output',
                'parameters' => ['schemas', 'options'],
                'example' => "add_action('wp_schema_before_output', function(\$schemas, \$options) { /* your code */ });"
            ],
        ];
    }
    
    /**
     * Helper method to add and track hooks
     */
    private function addHook(string $hook, $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        if (is_string($callback)) {
            $callback = [$this, $callback];
        }
        
        add_action($hook, $callback, $priority, $acceptedArgs);
        $this->registeredHooks[] = $hook;
    }
}
