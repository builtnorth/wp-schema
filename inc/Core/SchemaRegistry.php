<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Core;

use BuiltNorth\Schema\Contracts\SchemaRegistryInterface;

/**
 * Schema Registry
 * 
 * Central registry for all schema types and their generators.
 * This is the core extensibility point for plugins to add new schema types.
 * 
 * @since 1.0.0
 */
class SchemaRegistry implements SchemaRegistryInterface
{
    /** @var array<string, array{generator: string|callable, options: array<string, mixed>}> */
    private array $generators = [];
    
    /** @var array<string, callable> */
    private array $validators = [];
    
    /** @var array<string, array<string>> */
    private array $requiredProperties = [];
    
    /** @var array<string, array<string>> */
    private array $allowedProperties = [];
    
    public function __construct()
    {
        $this->registerCoreSchemaTypes();
    }
    
    /**
     * Register a schema type with its generator
     */
    public function registerSchemaType(string $schemaType, string|callable $generator, array $options = []): bool
    {
        // Validate schema type name
        if (empty($schemaType) || !$this->isValidSchemaType($schemaType)) {
            return false;
        }
        
        // Validate generator
        if (is_string($generator) && !class_exists($generator)) {
            return false;
        }
        
        $this->generators[$schemaType] = [
            'generator' => $generator,
            'options' => $options
        ];
        
        // Set required and allowed properties if provided
        if (!empty($options['required_properties'])) {
            $this->requiredProperties[$schemaType] = $options['required_properties'];
        }
        
        if (!empty($options['allowed_properties'])) {
            $this->allowedProperties[$schemaType] = $options['allowed_properties'];
        }
        
        // Allow plugins to hook into schema type registration
        do_action('wp_schema_type_registered', $schemaType, $generator, $options);
        
        return true;
    }
    
    /**
     * Get generator for a schema type
     */
    public function getGenerator(string $schemaType): string|callable|null
    {
        if (!$this->hasSchemaType($schemaType)) {
            return null;
        }
        
        return $this->generators[$schemaType]['generator'];
    }
    
    /**
     * Check if a schema type is registered
     */
    public function hasSchemaType(string $schemaType): bool
    {
        return isset($this->generators[$schemaType]);
    }
    
    /**
     * Get all registered schema types
     */
    public function getRegisteredTypes(): array
    {
        return array_keys($this->generators);
    }
    
    /**
     * Register a schema validator for a type
     */
    public function registerValidator(string $schemaType, callable $validator): bool
    {
        if (empty($schemaType)) {
            return false;
        }
        
        $this->validators[$schemaType] = $validator;
        
        do_action('wp_schema_validator_registered', $schemaType, $validator);
        
        return true;
    }
    
    /**
     * Get validator for a schema type
     */
    public function getValidator(string $schemaType): ?callable
    {
        return $this->validators[$schemaType] ?? null;
    }
    
    /**
     * Get generator options for a schema type
     */
    public function getGeneratorOptions(string $schemaType): array
    {
        return $this->generators[$schemaType]['options'] ?? [];
    }
    
    /**
     * Get required properties for a schema type
     */
    public function getRequiredProperties(string $schemaType): array
    {
        return $this->requiredProperties[$schemaType] ?? $this->getDefaultRequiredProperties($schemaType);
    }
    
    /**
     * Get allowed properties for a schema type
     */
    public function getAllowedProperties(string $schemaType): array
    {
        return $this->allowedProperties[$schemaType] ?? [];
    }
    
    /**
     * Generate schema using registered generator
     */
    public function generateSchema(string $schemaType, array $data, array $options = []): array
    {
        $generator = $this->getGenerator($schemaType);
        if (!$generator) {
            throw new \InvalidArgumentException("No generator registered for schema type: {$schemaType}");
        }
        
        // Apply pre-generation filters
        $data = apply_filters('wp_schema_pre_generation_data', $data, $schemaType, $options);
        
        // Generate schema
        if (is_callable($generator)) {
            $schema = $generator($data, $options);
        } else {
            // Assume it's a class with static generate method
            $schema = $generator::generate($data, $options);
        }
        
        // Apply post-generation filters
        $schema = apply_filters('wp_schema_post_generation', $schema, $schemaType, $data, $options);
        
        return $schema;
    }
    
    /**
     * Unregister a schema type
     */
    public function unregisterSchemaType(string $schemaType): bool
    {
        if (!$this->hasSchemaType($schemaType)) {
            return false;
        }
        
        unset($this->generators[$schemaType]);
        unset($this->validators[$schemaType]);
        unset($this->requiredProperties[$schemaType]);
        unset($this->allowedProperties[$schemaType]);
        
        do_action('wp_schema_type_unregistered', $schemaType);
        
        return true;
    }
    
    /**
     * Register core WordPress schema types
     */
    private function registerCoreSchemaTypes(): void
    {
        // Core schema types with their generators
        $coreTypes = [
            'Organization' => [
                'generator' => '\\BuiltNorth\\Schema\\Generators\\OrganizationGenerator',
                'required_properties' => ['name'],
                'allowed_properties' => ['name', 'description', 'url', 'logo', 'address', 'telephone', 'email', 'sameAs']
            ],
            'LocalBusiness' => [
                'generator' => '\\BuiltNorth\\Schema\\Generators\\LocalBusinessGenerator',
                'required_properties' => ['name'],
                'allowed_properties' => ['name', 'description', 'url', 'address', 'telephone', 'openingHours', 'geo']
            ],
            'Article' => [
                'generator' => '\\BuiltNorth\\Schema\\Generators\\ArticleGenerator',
                'required_properties' => ['headline', 'author'],
                'allowed_properties' => ['headline', 'author', 'datePublished', 'dateModified', 'description', 'articleBody', 'image', 'publisher']
            ],
            'WebSite' => [
                'generator' => '\\BuiltNorth\\Schema\\Generators\\WebSiteGenerator',
                'required_properties' => ['name', 'url'],
                'allowed_properties' => ['name', 'url', 'description', 'potentialAction', 'publisher']
            ],
            'WebPage' => [
                'generator' => '\\BuiltNorth\\Schema\\Generators\\WebPageGenerator',
                'required_properties' => ['name', 'url'],
                'allowed_properties' => ['name', 'url', 'description', 'breadcrumb', 'isPartOf']
            ],
            'Product' => [
                'generator' => '\\BuiltNorth\\Schema\\Generators\\ProductGenerator',
                'required_properties' => ['name'],
                'allowed_properties' => ['name', 'description', 'image', 'offers', 'brand', 'category', 'sku', 'gtin']
            ],
            'Person' => [
                'generator' => '\\BuiltNorth\\Schema\\Generators\\PersonGenerator',
                'required_properties' => ['name'],
                'allowed_properties' => ['name', 'description', 'image', 'jobTitle', 'worksFor', 'url', 'sameAs']
            ],
            'FAQPage' => [
                'generator' => '\\BuiltNorth\\Schema\\Generators\\FaqGenerator',
                'required_properties' => ['mainEntity'],
                'allowed_properties' => ['name', 'description', 'mainEntity']
            ],
            'BreadcrumbList' => [
                'generator' => '\\BuiltNorth\\Schema\\Generators\\NavigationGenerator',
                'required_properties' => ['itemListElement'],
                'allowed_properties' => ['itemListElement', 'numberOfItems']
            ]
        ];
        
        foreach ($coreTypes as $type => $config) {
            $this->registerSchemaType($type, $config['generator'], [
                'required_properties' => $config['required_properties'],
                'allowed_properties' => $config['allowed_properties']
            ]);
        }
    }
    
    /**
     * Validate schema type name
     */
    private function isValidSchemaType(string $schemaType): bool
    {
        // Schema.org types should be PascalCase
        return preg_match('/^[A-Z][a-zA-Z0-9]*$/', $schemaType) === 1;
    }
    
    /**
     * Get default required properties for common schema types
     */
    private function getDefaultRequiredProperties(string $schemaType): array
    {
        $defaults = [
            'Organization' => ['name'],
            'LocalBusiness' => ['name'],
            'Article' => ['headline', 'author'],
            'WebSite' => ['name', 'url'],
            'WebPage' => ['name', 'url'],
            'Product' => ['name'],
            'Person' => ['name'],
            'FAQPage' => ['mainEntity'],
            'BreadcrumbList' => ['itemListElement']
        ];
        
        return $defaults[$schemaType] ?? ['name'];
    }
}
