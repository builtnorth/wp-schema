<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Contracts;

/**
 * Cache Interface
 * 
 * Defines caching contract for schema data.
 * Supports different cache backends and invalidation strategies.
 * 
 * @since 1.0.0
 */
interface CacheInterface
{
    /**
     * Get cached value
     * 
     * @param string $key Cache key
     * @return mixed Cached value or null if not found
     */
    public function get(string $key): mixed;
    
    /**
     * Set cached value
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds
     * @return bool True if cached successfully
     */
    public function set(string $key, mixed $value, int $ttl = 3600): bool;
    
    /**
     * Delete cached value
     * 
     * @param string $key Cache key
     * @return bool True if deleted successfully
     */
    public function delete(string $key): bool;
    
    /**
     * Clear all cache
     * 
     * @return bool True if cleared successfully
     */
    public function flush(): bool;
    
    /**
     * Check if key exists in cache
     * 
     * @param string $key Cache key
     * @return bool True if key exists
     */
    public function has(string $key): bool;
    
    /**
     * Get multiple values at once
     * 
     * @param array<string> $keys Cache keys
     * @return array<string, mixed> Key-value pairs
     */
    public function getMultiple(array $keys): array;
    
    /**
     * Set multiple values at once
     * 
     * @param array<string, mixed> $values Key-value pairs
     * @param int $ttl Time to live in seconds
     * @return bool True if all values were cached
     */
    public function setMultiple(array $values, int $ttl = 3600): bool;
    
    /**
     * Delete multiple keys at once
     * 
     * @param array<string> $keys Cache keys
     * @return bool True if all keys were deleted
     */
    public function deleteMultiple(array $keys): bool;
    
    /**
     * Delete by pattern
     * 
     * @param string $pattern Pattern to match (supports wildcards)
     * @return bool True if deleted successfully
     */
    public function deleteByPattern(string $pattern): bool;
}
