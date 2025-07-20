<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Services;

use BuiltNorth\WPSchema\Contracts\SchemaProviderInterface;

/**
 * Provider Registry
 * 
 * Simple registry for schema providers with priority ordering.
 * 
 * @since 3.0.0
 */
class ProviderRegistry
{
    /** @var array<string, SchemaProviderInterface> */
    private array $providers = [];
    
    /**
     * Register a provider
     */
    public function register(string $name, SchemaProviderInterface $provider): void
    {
        $this->providers[$name] = $provider;
    }
    
    /**
     * Get providers that can handle context
     * 
     * @return SchemaProviderInterface[]
     */
    public function get_providers_for_context(string $context): array
    {
        $active = [];
        
        foreach ($this->providers as $provider) {
            if ($provider->can_provide($context)) {
                $active[] = $provider;
            }
        }
        
        // Sort by priority
        usort($active, fn($a, $b) => $a->get_priority() <=> $b->get_priority());
        
        return $active;
    }
    
    /**
     * Get all registered provider names
     */
    public function get_provider_names(): array
    {
        return array_keys($this->providers);
    }
}