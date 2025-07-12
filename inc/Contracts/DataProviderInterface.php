<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Contracts;

/**
 * Data Provider Interface
 * 
 * Defines the contract for all data providers in the schema system.
 * This is the primary extension point for plugins to add schema data.
 * 
 * @since 1.0.0
 */
interface DataProviderInterface
{
    /**
     * Check if this provider can provide data for the given context
     * 
     * @param string $context The context (e.g., 'singular', 'home', 'archive')
     * @param array<string, mixed> $options Context options
     * @return bool True if this provider can handle the context
     */
    public function canProvide(string $context, array $options = []): bool;
    
    /**
     * Provide schema data for the given context
     * 
     * @param string $context The context being processed
     * @param array<string, mixed> $options Context options
     * @return array<string, mixed> Schema data array
     * @throws \Exception If data provision fails
     */
    public function provide(string $context, array $options = []): array;
    
    /**
     * Get unique cache key for this provider's data
     * 
     * @param string $context The context
     * @param array<string, mixed> $options Context options
     * @return string Cache key
     */
    public function getCacheKey(string $context, array $options = []): string;
    
    /**
     * Get provider priority (higher = runs first)
     * 
     * @return int Priority value (0-100)
     */
    public function getPriority(): int;
    
    /**
     * Get provider identifier
     * 
     * @return string Unique provider identifier
     */
    public function getProviderId(): string;
    
    /**
     * Get schema types this provider can generate
     * 
     * @return array<string> Array of schema type names
     */
    public function getSupportedSchemaTypes(): array;
    
    /**
     * Check if provider is currently available/enabled
     * 
     * @return bool True if provider is available
     */
    public function isAvailable(): bool;
}
