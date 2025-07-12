<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Defaults;

use BuiltNorth\Schema\Contracts\DataProviderInterface;

/**
 * Core Blocks Navigation Provider
 * 
 * Provides navigation schema data from WordPress core navigation blocks.
 * Safe implementation that avoids template parsing during schema generation.
 * 
 * @since 2.0.0
 */
class CoreBlocksNavigationProvider implements DataProviderInterface
{
    private string $providerId = 'core_blocks_navigation';
    private int $priority = 12;
    
    /**
     * Get provider ID
     */
    public function getProviderId(): string
    {
        return $this->providerId;
    }
    
    /**
     * Get provider priority
     */
    public function getPriority(): int
    {
        return $this->priority;
    }
    
    /**
     * Check if provider is available
     */
    public function isAvailable(): bool
    {
        return true; // Always available since core blocks are always present
    }
    
    /**
     * Check if we can provide data for this context
     */
    public function canProvide(string $context, array $options = []): bool
    {
        // Provide navigation data for all contexts where navigation might be present
        return in_array($context, ['home', 'singular', 'taxonomy', 'archive']);
    }
    
    /**
     * Provide navigation schema data
     */
    public function provide(string $context, array $options = []): array
    {
        $navigation_data = $this->getNavigationData();
        
        if (empty($navigation_data)) {
            return [];
        }
        
        return [
            '@type' => 'SiteNavigationElement',
            'name' => $navigation_data['name'] ?? 'Site Navigation',
            'hasPart' => $navigation_data['items'] ?? [],
            'url' => $options['canonical_url'] ?? home_url('/')
        ];
    }
    
    /**
     * Get cache key for this provider
     */
    public function getCacheKey(string $context, array $options = []): string
    {
        return "core_blocks_nav_{$context}";
    }
    
    /**
     * Get supported schema types
     */
    public function getSupportedSchemaTypes(): array
    {
        return ['SiteNavigationElement'];
    }
    
    /**
     * Get navigation data from WordPress menus (safe approach)
     */
    private function getNavigationData(): array
    {
        // Get registered navigation menus - this is safe and doesn't cause recursion
        $nav_menus = wp_get_nav_menus();
        
        if (empty($nav_menus)) {
            return [];
        }
        
        // Use the first available menu (primary navigation)
        $primary_menu = $nav_menus[0];
        $menu_items = wp_get_nav_menu_items($primary_menu->term_id);
        
        if (empty($menu_items)) {
            return [];
        }
        
        $navigation_items = [];
        foreach ($menu_items as $item) {
            // Only include top-level items to avoid complexity
            if ($item->menu_item_parent == 0) {
                $navigation_items[] = [
                    '@type' => 'WebPage',
                    'name' => wp_strip_all_tags($item->title),
                    'url' => esc_url($item->url)
                ];
            }
        }
        
        return [
            'name' => $primary_menu->name,
            'items' => $navigation_items
        ];
    }
}