<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Core;

use BuiltNorth\Schema\Contracts\DataProviderInterface;
use BuiltNorth\Schema\Contracts\CacheInterface;

/**
 * Data Provider Manager
 * 
 * Manages all data providers and coordinates their execution.
 * This is where plugins register their data providers.
 * 
 * @since 1.0.0
 */
class DataProviderManager
{
    /** @var array<DataProviderInterface> */
    private array $providers = [];
    
    /** @var array<string, DataProviderInterface[]> */
    private array $providersByContext = [];
    
    /** @var bool */
    private bool $sorted = false;
    
    public function __construct(
        private CacheInterface $cache,
        private bool $cacheEnabled = true
    ) {}
    
    /**
     * Register a data provider
     */
    public function registerProvider(DataProviderInterface $provider): bool
    {
        // Check if provider is available
        if (!$provider->isAvailable()) {
            return false;
        }
        
        // Check for duplicate provider IDs
        $providerId = $provider->getProviderId();
        foreach ($this->providers as $existingProvider) {
            if ($existingProvider->getProviderId() === $providerId) {
                return false;
            }
        }
        
        $this->providers[] = $provider;
        $this->sorted = false;
        $this->providersByContext = []; // Clear context cache
        
        do_action('wp_schema_provider_registered', $provider);
        
        return true;
    }
    
    /**
     * Unregister a data provider
     */
    public function unregisterProvider(string $providerId): bool
    {
        $found = false;
        $this->providers = array_filter($this->providers, function($provider) use ($providerId, &$found) {
            if ($provider->getProviderId() === $providerId) {
                $found = true;
                return false;
            }
            return true;
        });
        
        if ($found) {
            $this->providersByContext = []; // Clear context cache
            do_action('wp_schema_provider_unregistered', $providerId);
        }
        
        return $found;
    }
    
    /**
     * Get all registered providers
     */
    public function getProviders(): array
    {
        $this->sortProviders();
        return $this->providers;
    }
    
    /**
     * Get providers for a specific context
     */
    public function getProvidersForContext(string $context, array $options = []): array
    {
        // Check cache first
        $cacheKey = $context . '_' . md5(serialize($options));
        if (isset($this->providersByContext[$cacheKey])) {
            return $this->providersByContext[$cacheKey];
        }
        
        $this->sortProviders();
        
        $applicableProviders = [];
        foreach ($this->providers as $provider) {
            try {
                if ($provider->canProvide($context, $options)) {
                    $applicableProviders[] = $provider;
                }
            } catch (\Exception $e) {
                error_log("Error checking provider {$provider->getProviderId()}: " . $e->getMessage());
                continue;
            }
        }
        
        // Cache the result
        $this->providersByContext[$cacheKey] = $applicableProviders;
        
        return $applicableProviders;
    }
    
    /**
     * Collect data from all applicable providers
     */
    public function collectData(string $context, array $options = []): array
    {
        $cacheKey = $this->generateCacheKey($context, $options);
        
        // Try cache first
        if ($this->cacheEnabled) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        $providers = $this->getProvidersForContext($context, $options);
        $collectedData = [];
        
        foreach ($providers as $provider) {
            try {
                $data = $provider->provide($context, $options);
                if (!empty($data)) {
                    $collectedData[$provider->getProviderId()] = [
                        'data' => $data,
                        'provider' => $provider->getProviderId(),
                        'priority' => $provider->getPriority(),
                        'schema_types' => $provider->getSupportedSchemaTypes()
                    ];
                }
            } catch (\Exception $e) {
                error_log("Error collecting data from provider {$provider->getProviderId()}: " . $e->getMessage());
                continue;
            }
        }
        
        // Apply filters to allow modification of collected data
        $collectedData = apply_filters('wp_schema_collected_data', $collectedData, $context, $options);
        
        // Cache the result
        if ($this->cacheEnabled && !empty($collectedData)) {
            $this->cache->set($cacheKey, $collectedData, $this->getCacheTtl($context));
        }
        
        return $collectedData;
    }
    
    /**
     * Get provider by ID
     */
    public function getProvider(string $providerId): ?DataProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->getProviderId() === $providerId) {
                return $provider;
            }
        }
        
        return null;
    }
    
    /**
     * Check if a provider is registered
     */
    public function hasProvider(string $providerId): bool
    {
        return $this->getProvider($providerId) !== null;
    }
    
    /**
     * Get providers by schema type
     */
    public function getProvidersBySchemaType(string $schemaType): array
    {
        $providers = [];
        foreach ($this->providers as $provider) {
            if (in_array($schemaType, $provider->getSupportedSchemaTypes(), true)) {
                $providers[] = $provider;
            }
        }
        
        return $providers;
    }
    
    /**
     * Clear cache for specific context
     */
    public function clearCache(string $context, array $options = []): bool
    {
        $cacheKey = $this->generateCacheKey($context, $options);
        return $this->cache->delete($cacheKey);
    }
    
    /**
     * Clear all provider cache
     */
    public function clearAllCache(): bool
    {
        return $this->cache->deleteByPattern('provider_*');
    }
    
    /**
     * Enable or disable caching
     */
    public function setCachingEnabled(bool $enabled): void
    {
        $this->cacheEnabled = $enabled;
    }
    
    /**
     * Check if caching is enabled
     */
    public function isCachingEnabled(): bool
    {
        return $this->cacheEnabled;
    }
    
    /**
     * Sort providers by priority
     */
    private function sortProviders(): void
    {
        if ($this->sorted) {
            return;
        }
        
        usort($this->providers, function(DataProviderInterface $a, DataProviderInterface $b) {
            return $b->getPriority() <=> $a->getPriority();
        });
        
        $this->sorted = true;
    }
    
    /**
     * Generate cache key for context and options
     */
    private function generateCacheKey(string $context, array $options): string
    {
        $keyParts = ['provider', $context];
        
        // Add relevant options to cache key
        $relevantOptions = [
            'post_id' => $options['post_id'] ?? null,
            'term_id' => $options['term_id'] ?? null,
            'user_id' => $options['user_id'] ?? null
        ];
        
        $relevantOptions = array_filter($relevantOptions);
        if (!empty($relevantOptions)) {
            $keyParts[] = md5(serialize($relevantOptions));
        }
        
        return implode('_', $keyParts);
    }
    
    /**
     * Get cache TTL for context
     */
    private function getCacheTtl(string $context): int
    {
        $ttls = [
            'singular' => 3600,    // 1 hour for posts/pages
            'home' => 1800,        // 30 minutes for homepage
            'archive' => 1800,     // 30 minutes for archives
            'taxonomy' => 1800,    // 30 minutes for taxonomy pages
        ];
        
        return $ttls[$context] ?? 900; // Default 15 minutes
    }
}
