<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Contracts;

/**
 * Schema Provider Interface
 * 
 * Simplified interface for providing schema pieces using Yoast-style approach.
 * Providers generate individual schema pieces that can reference each other.
 * 
 * @since 3.0.0
 */
interface SchemaProviderInterface
{
    /**
     * Check if this provider can generate schema for the given context
     *
     * @param string $context Current context (home, singular, archive, etc.)
     * @param array $options Additional options
     * @return bool True if provider can provide schema for this context
     */
    public function can_provide(string $context, array $options = []): bool;
    
    /**
     * Generate schema pieces for the context
     * 
     * Returns array of schema pieces with @id for Yoast-style references.
     * Each piece should be a complete schema object with @type and @id.
     *
     * @param string $context Current context (home, singular, archive, etc.)
     * @param array $options Additional options
     * @return array Array of schema pieces
     */
    public function get_pieces(string $context, array $options = []): array;
    
    /**
     * Get provider priority
     * 
     * Lower numbers = higher priority (runs first).
     * Used for registration order when providers are registered.
     *
     * @return int Priority (default: 10)
     */
    public function get_priority(): int;
}