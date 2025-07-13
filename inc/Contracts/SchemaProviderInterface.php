<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Contracts;

use BuiltNorth\Schema\Graph\SchemaPiece;

/**
 * Schema Provider Interface
 * 
 * Clean interface for providing atomic schema pieces.
 * Each provider returns SchemaPiece objects that can reference each other.
 * 
 * @since 3.0.0
 */
interface SchemaProviderInterface
{
    /**
     * Check if this provider can generate schema for the given context
     *
     * @param string $context Current context (home, singular, archive, etc.)
     * @return bool True if provider can provide schema for this context
     */
    public function can_provide(string $context): bool;
    
    /**
     * Generate atomic schema pieces for the context
     * 
     * Returns array of SchemaPiece objects that can reference each other.
     * Each piece is atomic and can be modified independently.
     *
     * @param string $context Current context (home, singular, archive, etc.)
     * @return SchemaPiece[] Array of schema pieces
     */
    public function get_pieces(string $context): array;
    
    /**
     * Get provider priority
     * 
     * Lower numbers = higher priority (runs first).
     *
     * @return int Priority (default: 10)
     */
    public function get_priority(): int;
}