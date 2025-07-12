<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Contracts;

/**
 * Schema Manager Interface
 * 
 * Central orchestrator for schema generation.
 * This is the main entry point for generating schemas.
 * 
 * @since 1.0.0
 */
interface SchemaManagerInterface
{
    /**
     * Generate schemas for a given context
     * 
     * @param string $context Context type (e.g., 'singular', 'home')
     * @param array<string, mixed> $options Context options
     * @return array<array<string, mixed>> Array of schema arrays
     */
    public function generateSchemas(string $context, array $options = []): array;
    
    /**
     * Generate a specific schema type
     * 
     * @param string $schemaType Schema type to generate
     * @param array<string, mixed> $data Data for schema generation
     * @param array<string, mixed> $options Generation options
     * @return array<string, mixed> Generated schema
     */
    public function generateSchema(string $schemaType, array $data, array $options = []): array;
    
    /**
     * Register a data provider
     * 
     * @param DataProviderInterface $provider Data provider instance
     * @return bool True if registered successfully
     */
    public function registerProvider(DataProviderInterface $provider): bool;
    
    /**
     * Unregister a data provider
     * 
     * @param string $providerId Provider identifier
     * @return bool True if unregistered successfully
     */
    public function unregisterProvider(string $providerId): bool;
    
    /**
     * Get all registered providers
     * 
     * @return array<DataProviderInterface> Registered providers
     */
    public function getProviders(): array;
    
    /**
     * Get providers for a specific context
     * 
     * @param string $context Context type
     * @param array<string, mixed> $options Context options
     * @return array<DataProviderInterface> Applicable providers
     */
    public function getProvidersForContext(string $context, array $options = []): array;
    
    /**
     * Enable or disable caching
     * 
     * @param bool $enabled Whether caching is enabled
     * @return void
     */
    public function setCachingEnabled(bool $enabled): void;
    
    /**
     * Check if caching is enabled
     * 
     * @return bool True if caching is enabled
     */
    public function isCachingEnabled(): bool;
    
    /**
     * Clear cache for specific context
     * 
     * @param string $context Context to clear
     * @param array<string, mixed> $options Context options
     * @return bool True if cleared successfully
     */
    public function clearCache(string $context, array $options = []): bool;
}
