<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Validation;

use BuiltNorth\Schema\Contracts\SchemaValidatorInterface;
use BuiltNorth\Schema\Contracts\ValidationResult;
use BuiltNorth\Schema\Core\SchemaRegistry;

/**
 * Schema Validator
 * 
 * Validates schema data against Schema.org specifications.
 * Critical for ensuring valid structured data output.
 * 
 * @since 1.0.0
 */
class SchemaValidator implements SchemaValidatorInterface
{
    private SchemaRegistry $registry;
    
    /** @var array<string, array<string, string>> */
    private array $propertyTypes = [];
    
    /** @var array<string, array<string>> */
    private array $enumValues = [];
    
    public function __construct(SchemaRegistry $registry)
    {
        $this->registry = $registry;
        $this->initializePropertyTypes();
        $this->initializeEnumValues();
    }
    
    /**
     * Validate schema data
     */
    public function validate(array $schema, string $schemaType): ValidationResult
    {
        $errors = [];
        $warnings = [];
        
        try {
            // Check basic structure
            if (!$this->validateBasicStructure($schema, $errors)) {
                return new ValidationResult(false, $errors, $warnings);
            }
            
            // Validate schema type
            if (!$this->validateSchemaType($schema, $schemaType, $errors)) {
                return new ValidationResult(false, $errors, $warnings);
            }
            
            // Validate required properties
            $this->validateRequiredPropertiesInternal($schema, $schemaType, $errors);
            
            // Validate property types
            $this->validatePropertyTypesInternal($schema, $schemaType, $errors, $warnings);
            
            // Validate property values
            $this->validatePropertyValues($schema, $schemaType, $errors, $warnings);
            
            // Custom validator if registered
            $customValidator = $this->registry->getValidator($schemaType);
            if ($customValidator) {
                $customResult = $customValidator($schema);
                if ($customResult instanceof ValidationResult) {
                    $errors = array_merge($errors, $customResult->getErrors());
                    $warnings = array_merge($warnings, $customResult->getWarnings());
                } elseif (is_array($customResult)) {
                    $errors = array_merge($errors, $customResult);
                }
            }
            
        } catch (\Exception $e) {
            $errors[] = "Validation error: " . $e->getMessage();
        }
        
        $isValid = empty($errors);
        
        return new ValidationResult($isValid, $errors, $warnings);
    }
    
    /**
     * Validate required properties for a schema type
     */
    public function validateRequiredProperties(array $schema, string $schemaType): bool
    {
        $required = $this->getRequiredProperties($schemaType);
        
        foreach ($required as $property) {
            if (!isset($schema[$property]) || $this->isEmpty($schema[$property])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate property types and formats
     */
    public function validatePropertyTypes(array $schema, string $schemaType): bool
    {
        $allowedProperties = $this->getAllowedProperties($schemaType);
        
        foreach ($schema as $property => $value) {
            // Skip schema.org metadata
            if (in_array($property, ['@context', '@type', '@id'])) {
                continue;
            }
            
            // Check if property is allowed
            if (!in_array($property, $allowedProperties)) {
                continue; // Warning, not error
            }
            
            // Validate property type
            if (!$this->isValidPropertyType($property, $value, $schemaType)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get required properties for a schema type
     */
    public function getRequiredProperties(string $schemaType): array
    {
        return $this->registry->getRequiredProperties($schemaType);
    }
    
    /**
     * Get allowed properties for a schema type
     */
    public function getAllowedProperties(string $schemaType): array
    {
        return $this->registry->getAllowedProperties($schemaType);
    }
    
    /**
     * Validate basic schema structure
     */
    private function validateBasicStructure(array $schema, array &$errors): bool
    {
        // Must have @context
        if (!isset($schema['@context'])) {
            $errors[] = "Missing required '@context' property";
            return false;
        }
        
        // @context must be schema.org
        if ($schema['@context'] !== 'https://schema.org') {
            $errors[] = "Invalid '@context'. Must be 'https://schema.org'";
            return false;
        }
        
        // Must have @type
        if (!isset($schema['@type'])) {
            $errors[] = "Missing required '@type' property";
            return false;
        }
        
        // @type must be string
        if (!is_string($schema['@type'])) {
            $errors[] = "'@type' must be a string";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate schema type matches expected type
     */
    private function validateSchemaType(array $schema, string $expectedType, array &$errors): bool
    {
        $actualType = $schema['@type'];
        
        // Exact match
        if ($actualType === $expectedType) {
            return true;
        }
        
        // Check inheritance (e.g., LocalBusiness extends Organization)
        if ($this->isValidSubtype($actualType, $expectedType)) {
            return true;
        }
        
        $errors[] = "Schema type mismatch. Expected '{$expectedType}', got '{$actualType}'";
        return false;
    }
    
    /**
     * Validate required properties are present
     */
    private function validateRequiredPropertiesInternal(array $schema, string $schemaType, array &$errors): void
    {
        $required = $this->getRequiredProperties($schemaType);
        
        foreach ($required as $property) {
            if (!isset($schema[$property]) || $this->isEmpty($schema[$property])) {
                $errors[] = "Missing required property: '{$property}'";
            }
        }
    }
    
    /**
     * Validate property types
     */
    private function validatePropertyTypesInternal(array $schema, string $schemaType, array &$errors, array &$warnings): void
    {
        foreach ($schema as $property => $value) {
            // Skip schema.org metadata
            if (in_array($property, ['@context', '@type', '@id'])) {
                continue;
            }
            
            if (!$this->isValidPropertyType($property, $value, $schemaType)) {
                $expectedType = $this->getExpectedPropertyType($property, $schemaType);
                $actualType = gettype($value);
                $errors[] = "Invalid type for property '{$property}'. Expected '{$expectedType}', got '{$actualType}'";
            }
        }
    }
    
    /**
     * Validate property values
     */
    private function validatePropertyValues(array $schema, string $schemaType, array &$errors, array &$warnings): void
    {
        foreach ($schema as $property => $value) {
            // Skip complex nested values (arrays/objects)
            if (is_array($value) || is_object($value)) {
                continue;
            }
            
            // Validate URLs (only if value is string)
            if ($this->isUrlProperty($property) && is_string($value) && !$this->isValidUrl($value)) {
                $errors[] = "Invalid URL for property '{$property}': '{$value}'";
            }
            
            // Validate email addresses (only if value is string)
            if ($property === 'email' && is_string($value) && !$this->isValidEmail($value)) {
                $errors[] = "Invalid email address: '{$value}'";
            }
            
            // Validate telephone numbers (only if value is string)
            if ($property === 'telephone' && is_string($value) && !$this->isValidTelephone($value)) {
                $warnings[] = "Telephone number '{$value}' may not be in optimal format";
            }
            
            // Validate date formats (only if value is string)
            if ($this->isDateProperty($property) && is_string($value) && !$this->isValidDate($value)) {
                $errors[] = "Invalid date format for property '{$property}': '{$value}'. Use ISO 8601 format.";
            }
            
            // Validate enum values
            if ($this->hasEnumValues($property) && !$this->isValidEnumValue($property, $value)) {
                $validValues = implode(', ', $this->getEnumValues($property));
                $warnings[] = "Property '{$property}' value '{$value}' is not in recommended values: {$validValues}";
            }
        }
    }
    
    /**
     * Check if a value is empty (but allow 0 and false)
     */
    private function isEmpty($value): bool
    {
        return $value === null || $value === '' || (is_array($value) && empty($value));
    }
    
    /**
     * Check if schema type is valid subtype of expected type
     */
    private function isValidSubtype(string $actualType, string $expectedType): bool
    {
        $inheritance = [
            'LocalBusiness' => ['Organization'],
            'Article' => ['CreativeWork'],
            'BlogPosting' => ['Article', 'CreativeWork'],
            'NewsArticle' => ['Article', 'CreativeWork'],
            'WebPage' => ['CreativeWork'],
            'AboutPage' => ['WebPage', 'CreativeWork'],
            'ContactPage' => ['WebPage', 'CreativeWork'],
        ];
        
        return isset($inheritance[$actualType]) && in_array($expectedType, $inheritance[$actualType]);
    }
    
    /**
     * Check if property type is valid
     */
    private function isValidPropertyType(string $property, $value, string $schemaType): bool
    {
        $expectedType = $this->getExpectedPropertyType($property, $schemaType);
        
        return match($expectedType) {
            'string' => is_string($value),
            'number' => is_numeric($value),
            'integer' => is_int($value) || (is_string($value) && ctype_digit($value)),
            'boolean' => is_bool($value),
            'array' => is_array($value),
            'object' => is_array($value) && isset($value['@type']),
            'url' => is_string($value) && $this->isValidUrl($value),
            'date' => is_string($value) && $this->isValidDate($value),
            'mixed' => true,
            default => true
        };
    }
    
    /**
     * Get expected property type
     */
    private function getExpectedPropertyType(string $property, string $schemaType): string
    {
        return $this->propertyTypes[$schemaType][$property] ?? 
               $this->propertyTypes['*'][$property] ?? 
               'mixed';
    }
    
    /**
     * Check if property should be a URL
     */
    private function isUrlProperty(string $property): bool
    {
        return in_array($property, ['url', 'sameAs', 'image', 'logo', 'mainEntityOfPage']);
    }
    
    /**
     * Check if property is a date property
     */
    private function isDateProperty(string $property): bool
    {
        return in_array($property, ['datePublished', 'dateModified', 'dateCreated', 'startDate', 'endDate']);
    }
    
    /**
     * Validate URL format
     */
    private function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Validate email format
     */
    private function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate telephone format (basic check)
     */
    private function isValidTelephone(string $telephone): bool
    {
        // Very basic check - just ensure it has some digits
        return preg_match('/\d/', $telephone) === 1;
    }
    
    /**
     * Validate date format (ISO 8601)
     */
    private function isValidDate(string $date): bool
    {
        // Check for ISO 8601 format
        return preg_match('/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}(\+\d{2}:\d{2}|Z)?)?$/', $date) === 1;
    }
    
    /**
     * Check if property has enum values
     */
    private function hasEnumValues(string $property): bool
    {
        return isset($this->enumValues[$property]);
    }
    
    /**
     * Check if value is valid enum value
     */
    private function isValidEnumValue(string $property, $value): bool
    {
        return in_array($value, $this->enumValues[$property] ?? []);
    }
    
    /**
     * Get enum values for property
     */
    private function getEnumValues(string $property): array
    {
        return $this->enumValues[$property] ?? [];
    }
    
    /**
     * Initialize property types
     */
    private function initializePropertyTypes(): void
    {
        $this->propertyTypes = [
            '*' => [
                'name' => 'string',
                'description' => 'string',
                'url' => 'url',
                'image' => 'url',
                'telephone' => 'string',
                'email' => 'string',
                'sameAs' => 'array',
                'datePublished' => 'date',
                'dateModified' => 'date',
            ],
            'Organization' => [
                'logo' => 'object',
                'address' => 'object',
                'contactPoint' => 'array',
            ],
            'Article' => [
                'headline' => 'string',
                'author' => 'object',
                'publisher' => 'object',
                'articleBody' => 'string',
                'wordCount' => 'integer',
            ],
            'Product' => [
                'offers' => 'array',
                'brand' => 'object',
                'category' => 'string',
                'sku' => 'string',
                'gtin' => 'string',
            ],
        ];
    }
    
    /**
     * Initialize enum values
     */
    private function initializeEnumValues(): void
    {
        $this->enumValues = [
            'availability' => [
                'https://schema.org/InStock',
                'https://schema.org/OutOfStock',
                'https://schema.org/PreOrder',
                'https://schema.org/BackOrder',
            ],
            'condition' => [
                'https://schema.org/NewCondition',
                'https://schema.org/UsedCondition',
                'https://schema.org/RefurbishedCondition',
                'https://schema.org/DamagedCondition',
            ],
        ];
    }
}
