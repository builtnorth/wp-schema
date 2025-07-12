<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\API;

use BuiltNorth\Schema\Contracts\DataProviderInterface;
use BuiltNorth\Schema\Core\Container;

/**
 * Plugin API
 * 
 * Easy-to-use API for plugins to extend the schema system.
 * This is the main integration point for third-party plugins.
 * 
 * @since 1.0.0
 */
class PluginAPI
{
    private Container $container;
    
    public function __construct()
    {
        $this->container = Container::getInstance();
    }
    
    /**
     * Register a new data provider
     * 
     * Example:
     * WP_Schema::registerProvider(new MyCustomProvider());
     */
    public function registerProvider(DataProviderInterface $provider): bool
    {
        return $this->container->get('manager')->registerProvider($provider);
    }
    
    /**
     * Register a new schema type
     * 
     * Example:
     * WP_Schema::registerSchemaType('Recipe', MyRecipeGenerator::class, [
     *     'required_properties' => ['name', 'author'],
     *     'allowed_properties' => ['name', 'author', 'ingredients', 'instructions']
     * ]);
     */
    public function registerSchemaType(string $type, string|callable $generator, array $options = []): bool
    {
        return $this->container->get('registry')->registerSchemaType($type, $generator, $options);
    }
    
    /**
     * Register a schema validator
     * 
     * Example:
     * WP_Schema::registerValidator('Recipe', function($schema) {
     *     return new ValidationResult(true);
     * });
     */
    public function registerValidator(string $type, callable $validator): bool
    {
        return $this->container->get('registry')->registerValidator($type, $validator);
    }
    
    /**
     * Generate schemas for current context
     * 
     * Example:
     * $schemas = WP_Schema::generateSchemas('singular', ['post_id' => 123]);
     */
    public function generateSchemas(string $context, array $options = []): array
    {
        return $this->container->get('manager')->generateSchemas($context, $options);
    }
    
    /**
     * Generate a specific schema type
     * 
     * Example:
     * $schema = WP_Schema::generateSchema('Organization', [
     *     'name' => 'My Company',
     *     'url' => 'https://example.com'
     * ]);
     */
    public function generateSchema(string $type, array $data, array $options = []): array
    {
        return $this->container->get('manager')->generateSchema($type, $data, $options);
    }
    
    /**
     * Output schema as JSON-LD script tag
     * 
     * Example:
     * WP_Schema::outputSchema($schema);
     */
    public function outputSchema(array $schema): void
    {
        if (empty($schema)) {
            return;
        }
        
        $json = wp_json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        if ($json) {
            echo '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n";
        }
    }
    
    /**
     * Get system status for debugging
     * 
     * Example:
     * $status = WP_Schema::getStatus();
     */
    public function getStatus(): array
    {
        return $this->container->get('manager')->getSystemStatus();
    }
    
    /**
     * Clear cache for specific context
     * 
     * Example:
     * WP_Schema::clearCache('singular', ['post_id' => 123]);
     */
    public function clearCache(string $context, array $options = []): bool
    {
        return $this->container->get('manager')->clearCache($context, $options);
    }
    
    /**
     * Add hook for schema modification
     * 
     * Example:
     * WP_Schema::addFilter('wp_schema_type_data', function($schema, $type, $data) {
     *     if ($type === 'Organization') {
     *         $schema['customField'] = 'customValue';
     *     }
     *     return $schema;
     * });
     */
    public function addFilter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        add_filter($hook, $callback, $priority, $acceptedArgs);
    }
    
    /**
     * Add action for schema events
     * 
     * Example:
     * WP_Schema::addAction('wp_schema_after_generation', function($schemas, $context, $options) {
     *     // Do something after schemas are generated
     * });
     */
    public function addAction(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        add_action($hook, $callback, $priority, $acceptedArgs);
    }
    
    /**
     * Get available hooks for developers
     */
    public function getAvailableHooks(): array
    {
        return $this->container->get('hooks')->getHookDocumentation();
    }
    
    /**
     * Enable/disable caching
     */
    public function setCachingEnabled(bool $enabled): void
    {
        $this->container->get('manager')->setCachingEnabled($enabled);
    }
    
    /**
     * Enable/disable validation
     */
    public function setValidationEnabled(bool $enabled): void
    {
        $this->container->get('manager')->setValidationEnabled($enabled);
    }
    
    /**
     * Validate a schema
     */
    public function validateSchema(array $schema, string $type): array
    {
        $result = $this->container->get('validator')->validate($schema, $type);
        
        return [
            'valid' => $result->isValid(),
            'errors' => $result->getErrors(),
            'warnings' => $result->getWarnings()
        ];
    }
    
    /**
     * Get performance report
     */
    public function getPerformanceReport(string $context, array $options = []): array
    {
        return $this->container->get('manager')->getPerformanceReport($context, $options);
    }
}

