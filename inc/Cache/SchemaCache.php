<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Cache;

use BuiltNorth\Schema\Contracts\CacheInterface;

/**
 * Schema Cache Implementation
 * 
 * WordPress transient-based caching with intelligent invalidation.
 * Designed for high-performance schema generation.
 * 
 * @since 1.0.0
 */
class SchemaCache implements CacheInterface
{
    private const PREFIX = 'wp_schema_';
    private const DEFAULT_TTL = 3600;
    private const MAX_KEY_LENGTH = 172; // WordPress transient key limit is 172 chars
    
    /** @var array<string, mixed> In-memory cache for single request */
    private array $memoryCache = [];
    
    /** @var bool Whether to use in-memory caching */
    private bool $useMemoryCache;
    
    public function __construct(bool $useMemoryCache = true)
    {
        $this->useMemoryCache = $useMemoryCache;
        $this->registerInvalidationHooks();
    }
    
    /**
     * Get cached value
     */
    public function get(string $key): mixed
    {
        $cacheKey = $this->buildCacheKey($key);
        
        // Check memory cache first
        if ($this->useMemoryCache && isset($this->memoryCache[$cacheKey])) {
            return $this->memoryCache[$cacheKey];
        }
        
        // Get from WordPress transients
        $value = get_transient($cacheKey);
        
        // Store in memory cache for this request
        if ($this->useMemoryCache && $value !== false) {
            $this->memoryCache[$cacheKey] = $value;
        }
        
        return $value !== false ? $value : null;
    }
    
    /**
     * Set cached value
     */
    public function set(string $key, mixed $value, int $ttl = self::DEFAULT_TTL): bool
    {
        $cacheKey = $this->buildCacheKey($key);
        
        // Store in memory cache
        if ($this->useMemoryCache) {
            $this->memoryCache[$cacheKey] = $value;
        }
        
        // Store in WordPress transients
        $result = set_transient($cacheKey, $value, $ttl);
        
        // Store cache metadata for invalidation
        $this->storeCacheMetadata($key, $cacheKey, $ttl);
        
        return $result;
    }
    
    /**
     * Delete cached value
     */
    public function delete(string $key): bool
    {
        $cacheKey = $this->buildCacheKey($key);
        
        // Remove from memory cache
        if ($this->useMemoryCache) {
            unset($this->memoryCache[$cacheKey]);
        }
        
        // Remove from WordPress transients
        $result = delete_transient($cacheKey);
        
        // Remove metadata
        $this->removeCacheMetadata($key);
        
        return $result;
    }
    
    /**
     * Clear all cache
     */
    public function flush(): bool
    {
        global $wpdb;
        
        // Clear memory cache
        if ($this->useMemoryCache) {
            $this->memoryCache = [];
        }
        
        // Clear WordPress transients
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . self::PREFIX . '%',
                '_transient_timeout_' . self::PREFIX . '%'
            )
        );
        
        // Clear metadata
        delete_option('wp_schema_cache_metadata');
        
        do_action('wp_schema_cache_flushed');
        
        return true;
    }
    
    /**
     * Check if key exists in cache
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }
    
    /**
     * Get multiple values at once
     */
    public function getMultiple(array $keys): array
    {
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = $this->get($key);
        }
        return $results;
    }
    
    /**
     * Set multiple values at once
     */
    public function setMultiple(array $values, int $ttl = self::DEFAULT_TTL): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }
        return $success;
    }
    
    /**
     * Delete multiple keys at once
     */
    public function deleteMultiple(array $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }
        return $success;
    }
    
    /**
     * Delete by pattern
     */
    public function deleteByPattern(string $pattern): bool
    {
        global $wpdb;
        
        $likePattern = str_replace('*', '%', self::PREFIX . $pattern);
        
        // Clear from memory cache
        if ($this->useMemoryCache) {
            $regex = '/^' . str_replace('*', '.*', preg_quote(self::PREFIX . $pattern, '/')) . '$/';
            $this->memoryCache = array_filter(
                $this->memoryCache,
                fn($key) => !preg_match($regex, $key),
                ARRAY_FILTER_USE_KEY
            );
        }
        
        // Clear from WordPress transients
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . $likePattern,
                '_transient_timeout_' . $likePattern
            )
        );
        
        return true;
    }
    
    /**
     * Invalidate cache for specific post
     */
    public function invalidatePost(int $postId): void
    {
        $patterns = [
            "provider_singular_*{$postId}*",
            "provider_home_*",
            "schema_post_{$postId}_*",
            "context_singular_*{$postId}*"
        ];
        
        foreach ($patterns as $pattern) {
            $this->deleteByPattern($pattern);
        }
        
        do_action('wp_schema_post_cache_invalidated', $postId);
    }
    
    /**
     * Invalidate cache for specific term
     */
    public function invalidateTerm(int $termId): void
    {
        $patterns = [
            "provider_taxonomy_*{$termId}*",
            "provider_archive_*{$termId}*",
            "schema_term_{$termId}_*",
            "context_taxonomy_*{$termId}*"
        ];
        
        foreach ($patterns as $pattern) {
            $this->deleteByPattern($pattern);
        }
        
        do_action('wp_schema_term_cache_invalidated', $termId);
    }
    
    /**
     * Invalidate homepage cache
     */
    public function invalidateHome(): void
    {
        $patterns = [
            'provider_home_*',
            'schema_home_*',
            'context_home_*'
        ];
        
        foreach ($patterns as $pattern) {
            $this->deleteByPattern($pattern);
        }
        
        do_action('wp_schema_home_cache_invalidated');
    }
    
    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        global $wpdb;
        
        $transientCount = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . self::PREFIX . '%'
            )
        );
        
        return [
            'transient_count' => (int) $transientCount,
            'memory_cache_count' => count($this->memoryCache),
            'memory_cache_enabled' => $this->useMemoryCache
        ];
    }
    
    /**
     * Build cache key with prefix and length validation
     */
    private function buildCacheKey(string $key): string
    {
        $cacheKey = self::PREFIX . $key;
        
        // WordPress transient keys have a 172 character limit
        if (strlen($cacheKey) > self::MAX_KEY_LENGTH) {
            // Use hash for long keys but keep prefix for pattern matching
            $hash = md5($key);
            $cacheKey = self::PREFIX . substr($key, 0, 20) . '_' . $hash;
        }
        
        return $cacheKey;
    }
    
    /**
     * Store cache metadata for intelligent invalidation
     */
    private function storeCacheMetadata(string $originalKey, string $cacheKey, int $ttl): void
    {
        $metadata = get_option('wp_schema_cache_metadata', []);
        
        $metadata[$cacheKey] = [
            'original_key' => $originalKey,
            'created' => time(),
            'ttl' => $ttl,
            'expires' => time() + $ttl
        ];
        
        // Clean up expired metadata
        $now = time();
        $metadata = array_filter($metadata, fn($meta) => $meta['expires'] > $now);
        
        update_option('wp_schema_cache_metadata', $metadata, false);
    }
    
    /**
     * Remove cache metadata
     */
    private function removeCacheMetadata(string $key): void
    {
        $cacheKey = $this->buildCacheKey($key);
        $metadata = get_option('wp_schema_cache_metadata', []);
        
        unset($metadata[$cacheKey]);
        
        update_option('wp_schema_cache_metadata', $metadata, false);
    }
    
    /**
     * Register WordPress hooks for cache invalidation
     */
    private function registerInvalidationHooks(): void
    {
        // Post invalidation
        add_action('save_post', [$this, 'invalidatePost']);
        add_action('delete_post', [$this, 'invalidatePost']);
        add_action('wp_trash_post', [$this, 'invalidatePost']);
        add_action('untrash_post', [$this, 'invalidatePost']);
        
        // Term invalidation
        add_action('edit_term', [$this, 'invalidateTerm']);
        add_action('delete_term', [$this, 'invalidateTerm']);
        add_action('create_term', [$this, 'invalidateTerm']);
        
        // Theme/customizer changes
        add_action('customize_save_after', [$this, 'invalidateHome']);
        add_action('switch_theme', [$this, 'flush']);
        
        // Plugin activation/deactivation
        add_action('activated_plugin', [$this, 'flush']);
        add_action('deactivated_plugin', [$this, 'flush']);
        
        // Menu changes
        add_action('wp_update_nav_menu', [$this, 'flush']);
        
        // Option changes that might affect schema
        add_action('update_option_blogname', [$this, 'invalidateHome']);
        add_action('update_option_blogdescription', [$this, 'invalidateHome']);
        add_action('update_option_polaris_organization', [$this, 'flush']);
        
        // Clear cache on plugin updates
        add_action('upgrader_process_complete', function($upgrader, $options) {
            if (isset($options['plugins']) && is_array($options['plugins'])) {
                foreach ($options['plugins'] as $plugin) {
                    if (strpos($plugin, 'wp-schema') !== false) {
                        $this->flush();
                        break;
                    }
                }
            }
        }, 10, 2);
    }
}
