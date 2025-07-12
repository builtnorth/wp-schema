<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Integrations;

use BuiltNorth\Schema\Contracts\SchemaProviderInterface;

/**
 * Core Navigation Provider
 * 
 * Provides SiteNavigationElement schema from WordPress core navigation.
 * Handles both classic menus and the newer Navigation block.
 * 
 * @since 3.0.0
 */
class CoreNavigationProvider implements SchemaProviderInterface
{
    public function can_provide(string $context, array $options = []): bool
    {
        // Only provide navigation on public-facing pages
        if (is_admin() || is_404()) {
            return false;
        }
        
        // Check if we have any navigation to provide
        return $this->has_navigation();
    }
    
    public function get_pieces(string $context, array $options = []): array
    {
        $navigation_data = $this->get_navigation_data();
        
        if (empty($navigation_data['items'])) {
            return [];
        }
        
        return [
            [
                '@type' => 'SiteNavigationElement',
                '@id' => home_url('/#navigation'),
                'name' => $navigation_data['name'],
                'hasPart' => $navigation_data['items']
            ]
        ];
    }
    
    public function get_priority(): int
    {
        return 15; // Lower priority - supplementary content
    }
    
    /**
     * Check if site has navigation
     */
    private function has_navigation(): bool
    {
        // Check for classic menu locations
        $locations = get_nav_menu_locations();
        if (!empty($locations)) {
            return true;
        }
        
        // Check for navigation blocks (WordPress 5.9+)
        if ($this->has_navigation_blocks()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if current page has navigation blocks
     */
    private function has_navigation_blocks(): bool
    {
        if (!function_exists('has_blocks') || !function_exists('parse_blocks')) {
            return false;
        }
        
        // Check current post/page content
        if (is_singular()) {
            $post = get_post();
            if ($post && has_blocks($post->post_content)) {
                $blocks = parse_blocks($post->post_content);
                foreach ($blocks as $block) {
                    if ($block['blockName'] === 'core/navigation') {
                        return true;
                    }
                }
            }
        }
        
        // Check theme template parts (if accessible)
        return false;
    }
    
    /**
     * Get navigation data from various sources
     */
    private function get_navigation_data(): array
    {
        $data = [
            'name' => 'Site Navigation',
            'items' => []
        ];
        
        // Try to get from classic menus first
        $menu_data = $this->get_classic_menu_data();
        if (!empty($menu_data['items'])) {
            return $menu_data;
        }
        
        // Fallback: try to get from navigation blocks
        $block_data = $this->get_navigation_block_data();
        if (!empty($block_data['items'])) {
            return $block_data;
        }
        
        return $data;
    }
    
    /**
     * Get navigation data from classic WordPress menus
     */
    private function get_classic_menu_data(): array
    {
        $locations = get_nav_menu_locations();
        $menu_id = null;
        
        // Priority order for menu locations
        $preferred_locations = ['primary', 'main', 'header', 'top'];
        
        foreach ($preferred_locations as $location) {
            if (isset($locations[$location]) && $locations[$location]) {
                $menu_id = $locations[$location];
                break;
            }
        }
        
        // If no preferred location, use first available
        if (!$menu_id && !empty($locations)) {
            $menu_id = array_values($locations)[0];
        }
        
        if (!$menu_id) {
            return ['name' => 'Site Navigation', 'items' => []];
        }
        
        $menu_items = wp_get_nav_menu_items($menu_id);
        if (!$menu_items) {
            return ['name' => 'Site Navigation', 'items' => []];
        }
        
        $menu_object = wp_get_nav_menu_object($menu_id);
        $menu_name = $menu_object ? $menu_object->name : 'Site Navigation';
        
        $items = [];
        foreach ($menu_items as $item) {
            // Only include top-level items for simplicity
            if ($item->menu_item_parent == 0) {
                $items[] = [
                    '@type' => 'WebPage',
                    'name' => $item->title,
                    'url' => $item->url
                ];
            }
        }
        
        return [
            'name' => $menu_name,
            'items' => $items
        ];
    }
    
    /**
     * Get navigation data from navigation blocks
     */
    private function get_navigation_block_data(): array
    {
        // This is more complex as it requires parsing block content
        // For now, return empty - can be expanded in future versions
        return ['name' => 'Site Navigation', 'items' => []];
    }
    
    /**
     * Get page hierarchy for breadcrumb-style navigation
     * Fallback when no menus are available
     */
    private function get_page_hierarchy(): array
    {
        $pages = get_pages([
            'sort_column' => 'menu_order',
            'sort_order' => 'ASC',
            'parent' => 0, // Top-level pages only
            'number' => 10 // Limit to prevent too many items
        ]);
        
        if (empty($pages)) {
            return [];
        }
        
        $items = [];
        foreach ($pages as $page) {
            $items[] = [
                '@type' => 'WebPage',
                'name' => $page->post_title,
                'url' => get_permalink($page->ID)
            ];
        }
        
        return $items;
    }
}