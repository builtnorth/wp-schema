<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Contracts;

/**
 * Hook Manager Interface
 * 
 * Manages WordPress hooks for schema generation.
 * Provides a clean API for plugins to extend schema functionality.
 * 
 * @since 1.0.0
 */
interface HookManagerInterface
{
    /**
     * Register all schema-related hooks
     * 
     * @return void
     */
    public function registerHooks(): void;
    
    /**
     * Apply filters for data provider registration
     * 
     * @param array<DataProviderInterface> $providers Current providers
     * @return array<DataProviderInterface> Filtered providers
     */
    public function applyProviderFilters(array $providers): array;
    
    /**
     * Apply filters for schema data before generation
     * 
     * @param array<string, mixed> $data Schema data
     * @param string $context Current context
     * @param array<string, mixed> $options Context options
     * @return array<string, mixed> Filtered data
     */
    public function applyDataFilters(array $data, string $context, array $options = []): array;
    
    /**
     * Apply filters for generated schema before output
     * 
     * @param array<string, mixed> $schema Generated schema
     * @param string $schemaType Schema type
     * @param array<string, mixed> $data Original data
     * @return array<string, mixed> Filtered schema
     */
    public function applySchemaFilters(array $schema, string $schemaType, array $data): array;
    
    /**
     * Trigger action when schema is generated
     * 
     * @param array<string, mixed> $schema Generated schema
     * @param string $context Context
     * @param array<string, mixed> $options Options
     * @return void
     */
    public function triggerSchemaGenerated(array $schema, string $context, array $options = []): void;
    
    /**
     * Apply filters for cache key generation
     * 
     * @param string $cacheKey Original cache key
     * @param string $context Context
     * @param array<string, mixed> $options Options
     * @return string Filtered cache key
     */
    public function applyCacheKeyFilters(string $cacheKey, string $context, array $options = []): string;
}
