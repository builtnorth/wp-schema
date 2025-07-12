<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Contracts;

/**
 * Schema Validator Interface
 * 
 * Defines the contract for validating schema data before output.
 * Critical for ensuring valid Schema.org markup.
 * 
 * @since 1.0.0
 */
interface SchemaValidatorInterface
{
    /**
     * Validate schema data
     * 
     * @param array<string, mixed> $schema Schema data to validate
     * @param string $schemaType Expected schema type
     * @return ValidationResult Validation result
     */
    public function validate(array $schema, string $schemaType): ValidationResult;
    
    /**
     * Validate required properties for a schema type
     * 
     * @param array<string, mixed> $schema Schema data
     * @param string $schemaType Schema type
     * @return bool True if all required properties are present
     */
    public function validateRequiredProperties(array $schema, string $schemaType): bool;
    
    /**
     * Validate property types and formats
     * 
     * @param array<string, mixed> $schema Schema data
     * @param string $schemaType Schema type
     * @return bool True if all properties have correct types
     */
    public function validatePropertyTypes(array $schema, string $schemaType): bool;
    
    /**
     * Get required properties for a schema type
     * 
     * @param string $schemaType Schema type
     * @return array<string> Required property names
     */
    public function getRequiredProperties(string $schemaType): array;
    
    /**
     * Get allowed properties for a schema type
     * 
     * @param string $schemaType Schema type
     * @return array<string> Allowed property names
     */
    public function getAllowedProperties(string $schemaType): array;
}

/**
 * Validation Result Value Object
 */
class ValidationResult
{
    public function __construct(
        private bool $isValid,
        private array $errors = [],
        private array $warnings = []
    ) {}
    
    public function isValid(): bool
    {
        return $this->isValid;
    }
    
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    public function getWarnings(): array
    {
        return $this->warnings;
    }
    
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
    
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }
}
