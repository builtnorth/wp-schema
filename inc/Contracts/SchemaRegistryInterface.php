<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Contracts;

/**
 * Schema Registry Interface
 * 
 * Central registry for all schema types and their generators.
 * This allows plugins to register new schema types dynamically.
 * 
 * @since 1.0.0
 */
interface SchemaRegistryInterface
{
    /**
     * Register a schema type with its generator
     * 
     * @param string $schemaType Schema.org type (e.g., 'Organization', 'LocalBusiness')
     * @param string|callable $generator Generator class name or callable
     * @param array<string, mixed> $options Generator options
     * @return bool True if registered successfully
     */
    public function registerSchemaType(string $schemaType, string|callable $generator, array $options = []): bool;
    
    /**
     * Get generator for a schema type
     * 
     * @param string $schemaType Schema type to get generator for
     * @return string|callable|null Generator or null if not found
     */
    public function getGenerator(string $schemaType): string|callable|null;
    
    /**
     * Check if a schema type is registered
     * 
     * @param string $schemaType Schema type to check
     * @return bool True if registered
     */
    public function hasSchemaType(string $schemaType): bool;
    
    /**
     * Get all registered schema types
     * 
     * @return array<string> Array of registered schema types
     */
    public function getRegisteredTypes(): array;
    
    /**
     * Register a schema validator for a type
     * 
     * @param string $schemaType Schema type
     * @param callable $validator Validator function
     * @return bool True if registered successfully
     */
    public function registerValidator(string $schemaType, callable $validator): bool;
    
    /**
     * Get validator for a schema type
     * 
     * @param string $schemaType Schema type
     * @return callable|null Validator or null if not found
     */
    public function getValidator(string $schemaType): ?callable;
}
