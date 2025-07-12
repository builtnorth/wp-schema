<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Core;

use BuiltNorth\Schema\Contracts\SchemaManagerInterface;
use BuiltNorth\Schema\Contracts\CacheInterface;
use BuiltNorth\Schema\Contracts\SchemaValidatorInterface;
use BuiltNorth\Schema\Contracts\HookManagerInterface;
use BuiltNorth\Schema\Cache\SchemaCache;
use BuiltNorth\Schema\Validation\SchemaValidator;

/**
 * Dependency Injection Container
 * 
 * Central container for all schema system dependencies.
 * Provides easy access to core services for plugins.
 * 
 * @since 1.0.0
 */
class Container
{
    /** @var array<string, mixed> */
    private array $services = [];
    
    /** @var array<string, callable> */
    private array $factories = [];
    
    /** @var array<string, bool> */
    private array $singletons = [];
    
    private static ?Container $instance = null;
    
    private function __construct()
    {
        $this->registerCoreServices();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Register a service
     */
    public function register(string $id, callable $factory, bool $singleton = true): void
    {
        $this->factories[$id] = $factory;
        $this->singletons[$id] = $singleton;
        
        // Clear existing instance if re-registering
        unset($this->services[$id]);
    }
    
    /**
     * Register a singleton service
     */
    public function singleton(string $id, callable $factory): void
    {
        $this->register($id, $factory, true);
    }
    
    /**
     * Register an instance directly
     */
    public function instance(string $id, mixed $instance): void
    {
        $this->services[$id] = $instance;
        $this->singletons[$id] = true;
    }
    
    /**
     * Get a service
     */
    public function get(string $id): mixed
    {
        // Return existing instance if singleton
        if (isset($this->services[$id])) {
            return $this->services[$id];
        }
        
        // Create new instance
        if (!isset($this->factories[$id])) {
            throw new \InvalidArgumentException("Service '{$id}' not registered");
        }
        
        $service = $this->factories[$id]($this);
        
        // Store if singleton
        if ($this->singletons[$id] ?? true) {
            $this->services[$id] = $service;
        }
        
        return $service;
    }
    
    /**
     * Check if service is registered
     */
    public function has(string $id): bool
    {
        return isset($this->factories[$id]) || isset($this->services[$id]);
    }
    
    /**
     * Get all registered service IDs
     */
    public function getRegisteredServices(): array
    {
        return array_unique(array_merge(
            array_keys($this->factories),
            array_keys($this->services)
        ));
    }
    
    /**
     * Register core services
     */
    private function registerCoreServices(): void
    {
        // Cache
        $this->singleton(CacheInterface::class, function() {
            return new SchemaCache();
        });
        
        $this->singleton('cache', function(Container $c) {
            return $c->get(CacheInterface::class);
        });
        
        // Schema Registry
        $this->singleton(SchemaRegistry::class, function() {
            return new SchemaRegistry();
        });
        
        $this->singleton('registry', function(Container $c) {
            return $c->get(SchemaRegistry::class);
        });
        
        // Hook Manager
        $this->singleton(HookManagerInterface::class, function() {
            return new HookManager();
        });
        
        $this->singleton('hooks', function(Container $c) {
            return $c->get(HookManagerInterface::class);
        });
        
        // Validator
        $this->singleton(SchemaValidatorInterface::class, function(Container $c) {
            return new SchemaValidator($c->get(SchemaRegistry::class));
        });
        
        $this->singleton('validator', function(Container $c) {
            return $c->get(SchemaValidatorInterface::class);
        });
        
        // Data Provider Manager
        $this->singleton(DataProviderManager::class, function(Container $c) {
            return new DataProviderManager(
                $c->get(CacheInterface::class)
            );
        });
        
        $this->singleton('providers', function(Container $c) {
            return $c->get(DataProviderManager::class);
        });
        
        // Schema Manager (main service)
        $this->singleton(SchemaManagerInterface::class, function(Container $c) {
            return new SchemaManager(
                $c->get(DataProviderManager::class),
                $c->get(SchemaRegistry::class),
                $c->get(HookManagerInterface::class),
                $c->get(SchemaValidatorInterface::class)
            );
        });
        
        $this->singleton('schema', function(Container $c) {
            return $c->get(SchemaManagerInterface::class);
        });
        
        $this->singleton('manager', function(Container $c) {
            return $c->get(SchemaManagerInterface::class);
        });
    }
}

/**
 * Global helper functions for easy access
 */

/**
 * Get the container instance
 */
function wp_schema_container(): Container
{
    return Container::getInstance();
}

/**
 * Get a service from the container
 */
function wp_schema(string $service = 'manager'): mixed
{
    return wp_schema_container()->get($service);
}

/**
 * Get the schema manager
 */
function wp_schema_manager(): SchemaManagerInterface
{
    return wp_schema('manager');
}

/**
 * Get the schema registry
 */
function wp_schema_registry(): SchemaRegistry
{
    return wp_schema('registry');
}

/**
 * Get the cache service
 */
function wp_schema_cache(): CacheInterface
{
    return wp_schema('cache');
}
