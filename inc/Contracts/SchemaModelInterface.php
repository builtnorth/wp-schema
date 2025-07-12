<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Contracts;

/**
 * Schema Model Interface
 * 
 * Defines the contract for schema model value objects.
 * All schema models should implement this interface for type safety.
 * 
 * @since 1.0.0
 */
interface SchemaModelInterface
{
    /**
     * Convert model to Schema.org array format
     * 
     * @return array<string, mixed> Schema.org compatible array
     */
    public function toArray(): array;
    
    /**
     * Validate the model data
     * 
     * @return bool True if valid, false otherwise
     */
    public function isValid(): bool;
    
    /**
     * Get validation errors
     * 
     * @return array<string> Array of error messages
     */
    public function getValidationErrors(): array;
}