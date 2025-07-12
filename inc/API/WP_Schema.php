<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\API;

use BuiltNorth\Schema\Contracts\DataProviderInterface;

/**
 * WP Schema Static API
 * 
 * Convenient static facade for the schema system.
 * Provides easy access to the plugin API without container dependencies.
 * 
 * @since 1.0.0
 */
class WP_Schema
{
    private static ?PluginAPI $api = null;
    
    /**
     * Get the plugin API instance
     */
    private static function getAPI(): PluginAPI
    {
        if (self::$api === null) {
            self::$api = new PluginAPI();
        }
        
        return self::$api;
    }
    
    /**
     * Register a new data provider
     */
    public static function registerProvider(DataProviderInterface $provider): bool
    {
        return self::getAPI()->registerProvider($provider);
    }
    
    /**
     * Register a new schema type
     */
    public static function registerSchemaType(string $type, string|callable $generator, array $options = []): bool
    {
        return self::getAPI()->registerSchemaType($type, $generator, $options);
    }
    
    /**
     * Register a custom validator for a schema type
     */
    public static function registerValidator(string $schemaType, callable $validator): bool
    {
        return self::getAPI()->registerValidator($schemaType, $validator);
    }
    
    /**
     * Generate schemas for current context
     */
    public static function generateSchemas(string $context, array $options = []): array
    {
        return self::getAPI()->generateSchemas($context, $options);
    }
    
    /**
     * Generate schema for specific data
     */
    public static function generateSchema(string $type, array $data, array $options = []): array
    {
        return self::getAPI()->generateSchema($type, $data, $options);
    }
    
    /**
     * Clear cache for specific context
     */
    public static function clearCache(string $context, array $options = []): bool
    {
        return self::getAPI()->clearCache($context, $options);
    }
    
    /**
     * Output schema as JSON-LD script tag
     */
    public static function outputSchema(array $schema): void
    {
        self::getAPI()->outputSchema($schema);
    }
    
    /**
     * Get system status
     */
    public static function getStatus(): array
    {
        return self::getAPI()->getStatus();
    }
    
    /**
     * Validate a schema
     */
    public static function validateSchema(array $schema, string $type): array
    {
        return self::getAPI()->validateSchema($schema, $type);
    }
    
    /**
     * Add filter for schema modification
     */
    public static function addFilter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        self::getAPI()->addFilter($hook, $callback, $priority, $acceptedArgs);
    }
    
    /**
     * Add action for schema events
     */
    public static function addAction(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        self::getAPI()->addAction($hook, $callback, $priority, $acceptedArgs);
    }
}