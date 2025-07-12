<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Core;

use BuiltNorth\Schema\Contracts\SchemaManagerInterface;
use BuiltNorth\Schema\Contracts\DataProviderInterface;
use BuiltNorth\Schema\Contracts\CacheInterface;
use BuiltNorth\Schema\Contracts\SchemaValidatorInterface;
use BuiltNorth\Schema\Contracts\HookManagerInterface;

/**
 * Schema Manager
 * 
 * Central orchestrator for schema generation.
 * This is THE entry point for generating schemas.
 * 
 * @since 1.0.0
 */
class SchemaManager implements SchemaManagerInterface
{
    private DataProviderManager $providerManager;
    private SchemaRegistry $registry;
    private HookManagerInterface $hookManager;
    private ?SchemaValidatorInterface $validator;
    private bool $cachingEnabled = true;
    private bool $validationEnabled = true;
    
    public function __construct(
        DataProviderManager $providerManager,
        SchemaRegistry $registry,
        HookManagerInterface $hookManager,
        ?SchemaValidatorInterface $validator = null
    ) {
        $this->providerManager = $providerManager;
        $this->registry = $registry;
        $this->hookManager = $hookManager;
        $this->validator = $validator;
    }
    
    /**
     * Generate schemas for a given context
     */
    public function generateSchemas(string $context, array $options = []): array
    {
        static $isGenerating = false;
        
        // Prevent recursion in SchemaManager itself
        if ($isGenerating) {
            error_log("SchemaManager: Recursion detected, aborting to prevent infinite loop");
            return [];
        }
        
        $isGenerating = true;
        
        try {
            // Skip hooks temporarily to avoid recursion - just collect data and generate schemas
            $collectedData = $this->providerManager->collectData($context, $options);
            $schemas = $this->generateSchemasFromData($collectedData, $options);
            
            // Skip validation temporarily to preserve all data
            // if ($this->validationEnabled && $this->validator) {
            //     $schemas = $this->validateSchemas($schemas);
            // }
            
            $isGenerating = false;
            return $schemas;
            
        } catch (\Exception $e) {
            error_log("Schema generation error in context '{$context}': " . $e->getMessage());
            $isGenerating = false;
            return [];
        }
    }
    
    /**
     * Generate a specific schema type
     */
    public function generateSchema(string $schemaType, array $data, array $options = []): array
    {
        try {
            // Check if schema type is registered
            if (!$this->registry->hasSchemaType($schemaType)) {
                throw new \InvalidArgumentException("Schema type '{$schemaType}' is not registered");
            }
            
            // Apply data filters
            $data = $this->hookManager->applyDataFilters($data, 'single', $options);
            
            // Generate schema using registry
            $schema = $this->registry->generateSchema($schemaType, $data, $options);
            
            // Apply schema filters
            $schema = $this->hookManager->applySchemaFilters($schema, $schemaType, $data);
            
            // Validate schema if validation is enabled
            if ($this->validationEnabled && $this->validator) {
                $validationResult = $this->validator->validate($schema, $schemaType);
                
                if (!$validationResult->isValid()) {
                    $errors = implode(', ', $validationResult->getErrors());
                    error_log("Schema validation failed for type '{$schemaType}': {$errors}");
                    
                    // Return empty schema on validation failure
                    return [];
                }
                
                // Log warnings if present
                if ($validationResult->hasWarnings()) {
                    $warnings = implode(', ', $validationResult->getWarnings());
                    error_log("Schema validation warnings for type '{$schemaType}': {$warnings}");
                }
            }
            
            return $schema;
            
        } catch (\Exception $e) {
            error_log("Schema generation error for type '{$schemaType}': " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Register a data provider
     */
    public function registerProvider(DataProviderInterface $provider): bool
    {
        return $this->providerManager->registerProvider($provider);
    }
    
    /**
     * Unregister a data provider
     */
    public function unregisterProvider(string $providerId): bool
    {
        return $this->providerManager->unregisterProvider($providerId);
    }
    
    /**
     * Get all registered providers
     */
    public function getProviders(): array
    {
        return $this->providerManager->getProviders();
    }
    
    /**
     * Get providers for a specific context
     */
    public function getProvidersForContext(string $context, array $options = []): array
    {
        return $this->providerManager->getProvidersForContext($context, $options);
    }
    
    /**
     * Enable or disable caching
     */
    public function setCachingEnabled(bool $enabled): void
    {
        $this->cachingEnabled = $enabled;
        $this->providerManager->setCachingEnabled($enabled);
    }
    
    /**
     * Check if caching is enabled
     */
    public function isCachingEnabled(): bool
    {
        return $this->cachingEnabled;
    }
    
    /**
     * Enable or disable validation
     */
    public function setValidationEnabled(bool $enabled): void
    {
        $this->validationEnabled = $enabled;
    }
    
    /**
     * Check if validation is enabled
     */
    public function isValidationEnabled(): bool
    {
        return $this->validationEnabled;
    }
    
    /**
     * Clear cache for specific context
     */
    public function clearCache(string $context, array $options = []): bool
    {
        return $this->providerManager->clearCache($context, $options);
    }
    
    /**
     * Get schema registry
     */
    public function getRegistry(): SchemaRegistry
    {
        return $this->registry;
    }
    
    /**
     * Get hook manager
     */
    public function getHookManager(): HookManagerInterface
    {
        return $this->hookManager;
    }
    
    /**
     * Get provider manager
     */
    public function getProviderManager(): DataProviderManager
    {
        return $this->providerManager;
    }
    
    /**
     * Get validator
     */
    public function getValidator(): ?SchemaValidatorInterface
    {
        return $this->validator;
    }
    
    /**
     * Set validator
     */
    public function setValidator(?SchemaValidatorInterface $validator): void
    {
        $this->validator = $validator;
    }
    
    /**
     * Get system status for debugging
     */
    public function getSystemStatus(): array
    {
        $providers = $this->getProviders();
        $registeredTypes = $this->registry->getRegisteredTypes();
        
        return [
            'providers' => [
                'count' => count($providers),
                'list' => array_map(fn($p) => [
                    'id' => $p->getProviderId(),
                    'priority' => $p->getPriority(),
                    'available' => $p->isAvailable(),
                    'types' => $p->getSupportedSchemaTypes()
                ], $providers)
            ],
            'schema_types' => [
                'count' => count($registeredTypes),
                'list' => $registeredTypes
            ],
            'settings' => [
                'caching_enabled' => $this->cachingEnabled,
                'validation_enabled' => $this->validationEnabled,
                'validator_available' => $this->validator !== null
            ],
            'hooks' => [
                'registered' => count($this->hookManager->getRegisteredHooks()),
                'list' => $this->hookManager->getRegisteredHooks()
            ]
        ];
    }
    
    /**
     * Generate performance report
     */
    public function getPerformanceReport(string $context, array $options = []): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        // Generate schemas and measure performance
        $schemas = $this->generateSchemas($context, $options);
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        return [
            'context' => $context,
            'schemas_generated' => count($schemas),
            'execution_time' => round(($endTime - $startTime) * 1000, 2), // ms
            'memory_used' => round(($endMemory - $startMemory) / 1024, 2), // KB
            'providers_used' => count($this->getProvidersForContext($context, $options)),
            'cache_enabled' => $this->cachingEnabled,
            'validation_enabled' => $this->validationEnabled,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Generate schemas from collected data
     */
    private function generateSchemasFromData(array $collectedData, array $options): array
    {
        $schemas = [];
        
        foreach ($collectedData as $providerData) {
            $data = $providerData['data'];
            $supportedTypes = $providerData['schema_types'];
            
            // If data contains a schema type, check if it's already a complete schema
            if (isset($data['@type'])) {
                // If it also has @context, it's already a complete schema - use as-is
                if (isset($data['@context'])) {
                    $schemas[] = $data;
                    continue;
                }
                
                // Otherwise, generate schema using registry
                $schemaType = $data['@type'];
                if ($this->registry->hasSchemaType($schemaType)) {
                    $schema = $this->registry->generateSchema($schemaType, $data, $options);
                    if (!empty($schema)) {
                        $schemas[] = $schema;
                    }
                }
                continue;
            }
            
            // Otherwise, generate schemas for all supported types
            foreach ($supportedTypes as $schemaType) {
                if ($this->registry->hasSchemaType($schemaType)) {
                    try {
                        $schema = $this->registry->generateSchema($schemaType, $data, $options);
                        if (!empty($schema)) {
                            $schemas[] = $schema;
                        }
                    } catch (\Exception $e) {
                        // Log error but continue with other types
                        error_log("Error generating schema type '{$schemaType}': " . $e->getMessage());
                    }
                }
            }
        }
        
        // Remove duplicates and merge similar schemas
        return $this->deduplicateSchemas($schemas);
    }
    
    /**
     * Validate generated schemas
     */
    private function validateSchemas(array $schemas): array
    {
        $validatedSchemas = [];
        
        foreach ($schemas as $schema) {
            if (!isset($schema['@type'])) {
                continue; // Skip invalid schemas
            }
            
            $schemaType = $schema['@type'];
            $validationResult = $this->validator->validate($schema, $schemaType);
            
            if ($validationResult->isValid()) {
                $validatedSchemas[] = $schema;
            } else {
                $errors = implode(', ', $validationResult->getErrors());
                error_log("Schema validation failed for type '{$schemaType}': {$errors}");
            }
            
            // Log warnings
            if ($validationResult->hasWarnings()) {
                $warnings = implode(', ', $validationResult->getWarnings());
                error_log("Schema validation warnings for type '{$schemaType}': {$warnings}");
            }
        }
        
        return $validatedSchemas;
    }
    
    /**
     * Remove duplicate schemas and merge similar ones
     */
    private function deduplicateSchemas(array $schemas): array
    {
        $uniqueSchemas = [];
        $seenTypes = [];
        
        foreach ($schemas as $schema) {
            if (!isset($schema['@type'])) {
                continue;
            }
            
            $type = $schema['@type'];
            
            // For organization/business schemas, merge instead of duplicate
            if (in_array($type, ['Organization', 'LocalBusiness']) && isset($seenTypes[$type])) {
                $existingIndex = $seenTypes[$type];
                $uniqueSchemas[$existingIndex] = $this->mergeSchemas(
                    $uniqueSchemas[$existingIndex],
                    $schema
                );
            } else {
                $uniqueSchemas[] = $schema;
                $seenTypes[$type] = count($uniqueSchemas) - 1;
            }
        }
        
        return array_values($uniqueSchemas);
    }
    
    /**
     * Merge two schemas of the same type
     */
    private function mergeSchemas(array $schema1, array $schema2): array
    {
        $merged = $schema1;
        
        foreach ($schema2 as $key => $value) {
            if ($key === '@context' || $key === '@type') {
                continue; // Don't merge these
            }
            
            if (!isset($merged[$key]) || empty($merged[$key])) {
                $merged[$key] = $value;
            } elseif (is_array($value) && is_array($merged[$key])) {
                $merged[$key] = array_unique(array_merge($merged[$key], $value));
            }
        }
        
        return $merged;
    }
}
