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
        // Check for navigation posts (WordPress 6.0+ block theme navigation)
        if ($this->has_navigation_posts()) {
            return true;
        }
        
        // Check for navigation blocks with refs
        if ($this->has_navigation_blocks()) {
            return true;
        }
        
        // Fallback to classic menu locations
        $locations = get_nav_menu_locations();
        return !empty($locations);
    }
    
    /**
     * Check if current page has navigation blocks
     */
    private function has_navigation_blocks(): bool
    {
        if (!function_exists('has_blocks') || !function_exists('parse_blocks')) {
            return false;
        }
        
        // Check active theme templates for navigation blocks
        $template_blocks = $this->get_template_navigation_blocks();
        if (!empty($template_blocks)) {
            return true;
        }
        
        // Check current post/page content for navigation blocks
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
        
        // Prioritize navigation posts (modern block theme navigation)
        $nav_post_data = $this->get_navigation_post_data();
        if (!empty($nav_post_data['items'])) {
            return $nav_post_data;
        }
        
        // Try navigation blocks with refs
        $block_data = $this->get_navigation_block_data();
        if (!empty($block_data['items'])) {
            return $block_data;
        }
        
        // Fallback to classic menus
        $menu_data = $this->get_classic_menu_data();
        if (!empty($menu_data['items'])) {
            return $menu_data;
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
        $navigation_blocks = $this->get_template_navigation_blocks();
        
        foreach ($navigation_blocks as $block) {
            $attrs = $block['attrs'] ?? [];
            
            // Check if block has a ref (references a navigation menu)
            if (!empty($attrs['ref'])) {
                $menu_data = $this->get_navigation_menu_by_id($attrs['ref']);
                if (!empty($menu_data['items'])) {
                    return $menu_data;
                }
            }
            
            // Check if block has inline items
            if (!empty($block['innerBlocks'])) {
                $items = $this->parse_navigation_inner_blocks($block['innerBlocks']);
                if (!empty($items)) {
                    return [
                        'name' => 'Site Navigation',
                        'items' => $items
                    ];
                }
            }
        }
        
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
    
    /**
     * Check if site has navigation posts
     */
    private function has_navigation_posts(): bool
    {
        $nav_posts = get_posts([
            'post_type' => 'wp_navigation',
            'post_status' => 'publish',
            'posts_per_page' => 1
        ]);
        
        return !empty($nav_posts);
    }
    
    /**
     * Get navigation data from navigation posts
     */
    private function get_navigation_post_data(): array
    {
        $nav_posts = get_posts([
            'post_type' => 'wp_navigation',
            'post_status' => 'publish',
            'posts_per_page' => -1,  // Get all navigation posts
            'orderby' => 'date',
            'order' => 'ASC'
        ]);
        
        if (empty($nav_posts)) {
            return ['name' => 'Site Navigation', 'items' => []];
        }
        
        // Find the primary/header navigation only
        $primary_nav_post = $this->find_primary_navigation($nav_posts);
        
        if (!$primary_nav_post) {
            return ['name' => 'Site Navigation', 'items' => []];
        }
        
        $blocks = parse_blocks($primary_nav_post->post_content);
        $items = $this->extract_navigation_items($blocks);
        
        return [
            'name' => $primary_nav_post->post_title ?: 'Site Navigation',
            'items' => $items
        ];
    }
    
    /**
     * Get navigation blocks from active theme templates
     */
    private function get_template_navigation_blocks(): array
    {
        // For now, return empty array to avoid complex template parsing
        // This can be expanded later when needed
        // Focus on classic menus which are more reliable
        return [];
    }
    
    /**
     * Recursively find navigation blocks in block tree
     */
    private function find_navigation_blocks(array $blocks): array
    {
        $navigation_blocks = [];
        
        foreach ($blocks as $block) {
            if ($block['blockName'] === 'core/navigation') {
                $navigation_blocks[] = $block;
            }
            
            // Recursively check inner blocks
            if (!empty($block['innerBlocks'])) {
                $navigation_blocks = array_merge($navigation_blocks, $this->find_navigation_blocks($block['innerBlocks']));
            }
        }
        
        return $navigation_blocks;
    }
    
    /**
     * Get navigation menu by WordPress nav menu ID (ref)
     */
    private function get_navigation_menu_by_id(int $menu_id): array
    {
        $menu_object = wp_get_nav_menu_object($menu_id);
        if (!$menu_object) {
            return ['name' => 'Site Navigation', 'items' => []];
        }
        
        $menu_items = wp_get_nav_menu_items($menu_id);
        if (!$menu_items) {
            return ['name' => 'Site Navigation', 'items' => []];
        }
        
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
            'name' => $menu_object->name,
            'items' => $items
        ];
    }
    
    /**
     * Parse navigation inner blocks (inline navigation items)
     */
    private function parse_navigation_inner_blocks(array $inner_blocks): array
    {
        $items = [];
        
        foreach ($inner_blocks as $block) {
            if ($block['blockName'] === 'core/navigation-link') {
                $attrs = $block['attrs'] ?? [];
                $items[] = [
                    '@type' => 'WebPage',
                    'name' => $attrs['label'] ?? 'Navigation Item',
                    'url' => $attrs['url'] ?? '#'
                ];
            }
        }
        
        return $items;
    }
    
    /**
     * Extract navigation items from blocks recursively
     */
    private function extract_navigation_items(array $blocks): array
    {
        $items = [];
        
        foreach ($blocks as $block) {
            if ($block['blockName'] === 'core/navigation-link') {
                $attrs = $block['attrs'] ?? [];
                if (!empty($attrs['label']) && !empty($attrs['url'])) {
                    $items[] = [
                        '@type' => 'WebPage',
                        'name' => $attrs['label'],
                        'url' => $attrs['url']
                    ];
                }
            } elseif ($block['blockName'] === 'core/navigation-submenu') {
                // Handle submenu - extract the main link and skip children for simplicity
                $attrs = $block['attrs'] ?? [];
                if (!empty($attrs['label']) && !empty($attrs['url'])) {
                    $items[] = [
                        '@type' => 'WebPage',
                        'name' => $attrs['label'],
                        'url' => $attrs['url']
                    ];
                }
            }
            
            // Recursively check inner blocks
            if (!empty($block['innerBlocks'])) {
                $inner_items = $this->extract_navigation_items($block['innerBlocks']);
                $items = array_merge($items, $inner_items);
            }
        }
        
        return $items;
    }
    
    /**
     * Find the primary/header navigation from available navigation posts
     */
    private function find_primary_navigation(array $nav_posts): ?object
    {
        $primary_indicators = ['primary', 'main', 'header', 'top', 'site'];
        $exclude_indicators = ['footer', 'sidebar', 'secondary', 'mobile'];
        
        $scored_navs = [];
        
        foreach ($nav_posts as $nav_post) {
            if (!has_blocks($nav_post->post_content)) {
                continue;
            }
            
            $blocks = parse_blocks($nav_post->post_content);
            $items = $this->extract_navigation_items($blocks);
            $item_count = count($items);
            
            // Skip if no navigation items
            if ($item_count === 0) {
                continue;
            }
            
            $title_lower = strtolower($nav_post->post_title);
            $score = 0;
            
            // Positive scoring for primary indicators
            foreach ($primary_indicators as $indicator) {
                if (strpos($title_lower, $indicator) !== false) {
                    $score += 10;
                    break; // Only count once
                }
            }
            
            // Negative scoring for exclude indicators  
            foreach ($exclude_indicators as $indicator) {
                if (strpos($title_lower, $indicator) !== false) {
                    $score -= 20;
                    break; // Only count once
                }
            }
            
            // Bonus points for having more navigation items (likely main nav)
            $score += min($item_count, 10); // Cap at 10 bonus points
            
            // Bonus for being the first/oldest navigation (often the primary)
            if ($nav_post->ID < 100) { // Likely an early/default navigation
                $score += 5;
            }
            
            $scored_navs[] = [
                'post' => $nav_post,
                'score' => $score,
                'items' => $item_count
            ];
        }
        
        if (empty($scored_navs)) {
            return null;
        }
        
        // Sort by score (highest first)
        usort($scored_navs, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return $scored_navs[0]['post'];
    }
}