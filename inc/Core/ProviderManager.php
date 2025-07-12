<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Core;

use BuiltNorth\Schema\Contracts\SchemaProviderInterface;

/**
 * Provider Manager
 * 
 * Simple registry for schema providers using registration order.
 * Supports lazy loading and context-based filtering.
 * 
 * @since 3.0.0
 */
class ProviderManager
{
    /** @var array<array{id: string, class: string, instance: ?SchemaProviderInterface}> */
    private array $providers = [];
    
    /**
     * Register a schema provider
     *
     * @param string $id Provider identifier
     * @param string $class_name Fully qualified class name
     * @return void
     */
    public function register(string $id, string $class_name): void
    {
        $this->providers[] = [
            'id' => $id,
            'class' => $class_name,
            'instance' => null // Lazy load
        ];
    }
    
    /**
     * Get all providers that can provide schema for the given context
     *
     * @param string $context Current context (home, singular, archive, etc.)
     * @param array $options Additional options
     * @return array<SchemaProviderInterface> Active providers in registration order
     */
    public function get_for_context(string $context, array $options = []): array
    {
        $active = [];
        
        foreach ($this->providers as &$provider) {
            // Lazy instantiation
            if ($provider['instance'] === null) {
                try {
                    $provider['instance'] = new $provider['class']();
                } catch (\Exception $e) {
                    error_log("Failed to instantiate schema provider {$provider['class']}: " . $e->getMessage());
                    continue;
                }
            }
            
            // Check if provider can handle this context
            if ($provider['instance']->can_provide($context, $options)) {
                $active[] = $provider['instance'];
            }
        }
        
        return $active;
    }
    
    /**
     * Get all registered provider IDs
     *
     * @return array<string> Provider IDs
     */
    public function get_registered_ids(): array
    {
        return array_column($this->providers, 'id');
    }
    
    /**
     * Check if a provider is registered
     *
     * @param string $id Provider ID
     * @return bool True if registered
     */
    public function has_provider(string $id): bool
    {
        return in_array($id, $this->get_registered_ids(), true);
    }
    
    /**
     * Remove a provider by ID
     *
     * @param string $id Provider ID
     * @return bool True if removed, false if not found
     */
    public function unregister(string $id): bool
    {
        foreach ($this->providers as $index => $provider) {
            if ($provider['id'] === $id) {
                unset($this->providers[$index]);
                $this->providers = array_values($this->providers); // Re-index
                return true;
            }
        }
        
        return false;
    }
}