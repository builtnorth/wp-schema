<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Providers;

use BuiltNorth\Schema\Contracts\SchemaProviderInterface;
use BuiltNorth\Schema\Graph\SchemaPiece;

/**
 * Navigation Provider
 * 
 * Provides SiteNavigationElement schema for navigation menus.
 * Works with both FSE themes (core/navigation blocks) and classic themes.
 * 
 * @since 3.0.0
 */
class NavigationProvider implements SchemaProviderInterface
{
    public function can_provide(string $context): bool
    {
        // Navigation can appear on any page
        return true;
    }
    
    public function get_pieces(string $context): array
    {
        $pieces = [];
        
        // Check if block theme
        if (function_exists('wp_is_block_theme') && wp_is_block_theme()) {
            // For FSE themes, get wp_navigation posts
            $navigations = get_posts([
                'post_type' => 'wp_navigation',
                'post_status' => 'publish',
                'numberposts' => -1,
            ]);
            
            foreach ($navigations as $navigation) {
                $piece = $this->create_navigation_from_post($navigation);
                if ($piece) {
                    $pieces[] = $piece;
                }
            }
        }
        
        // Also check classic menus (FSE themes can have these too)
        $menus = wp_get_nav_menus();
        
        foreach ($menus as $menu) {
            $menu_items = wp_get_nav_menu_items($menu->term_id);
            
            if (empty($menu_items)) {
                continue;
            }
            
            $menu_slug = sanitize_title($menu->name);
            $piece = new SchemaPiece("#navigation-{$menu_slug}", 'SiteNavigationElement');
            $piece->set('name', $menu->name);
            
            // Build menu items - include ALL top-level items (even those with children)
            $schema_items = [];
            
            // Collect top-level items
            foreach ($menu_items as $item) {
                if ($item->menu_item_parent == 0 || $item->menu_item_parent == '0') { // Only top-level items
                    // Skip items without titles
                    $title = trim($item->title);
                    if (empty($title)) {
                        continue;
                    }
                    
                    // Get the URL based on menu item type
                    $url = '';
                    
                    switch ($item->type) {
                        case 'custom':
                            // Custom links have URL stored directly
                            $url = $item->url;
                            break;
                        case 'post_type':
                            // Regular post/page
                            if ($item->object_id) {
                                $url = get_permalink($item->object_id);
                            }
                            break;
                        case 'post_type_archive':
                            // Archive page (like Blog)
                            if ($item->object) {
                                $url = get_post_type_archive_link($item->object);
                            }
                            break;
                        case 'taxonomy':
                            // Category/tag/taxonomy term
                            if ($item->object_id && $item->object) {
                                $url = get_term_link((int) $item->object_id, $item->object);
                            }
                            break;
                        default:
                            // Fallback to stored URL
                            $url = $item->url;
                            break;
                    }
                    
                    // Ensure URL is valid and not empty
                    if (!is_wp_error($url) && !empty($url)) {
                        $schema_items[] = [
                            '@type' => 'SiteNavigationElement',
                            'name' => $title,
                            'url' => $url,
                        ];
                    }
                }
            }
            
            if (!empty($schema_items)) {
                $piece->set('hasPart', $schema_items);
                $pieces[] = $piece;
            }
        }
        
        return $pieces;
    }
    
    public function get_priority(): int
    {
        return 15; // Lower priority than content
    }
    
    /**
     * Create navigation schema from wp_navigation post
     */
    private function create_navigation_from_post(\WP_Post $navigation): ?SchemaPiece
    {
        // Parse the navigation blocks
        $blocks = parse_blocks($navigation->post_content);
        
        if (empty($blocks)) {
            return null;
        }
        
        $menu_slug = sanitize_title($navigation->post_title ?: 'navigation-' . $navigation->ID);
        $piece = new SchemaPiece("#navigation-{$menu_slug}", 'SiteNavigationElement');
        $piece->set('name', $navigation->post_title ?: 'Navigation');
        
        // Extract menu items from blocks
        $schema_items = $this->extract_navigation_items($blocks);
        
        if (!empty($schema_items)) {
            $piece->set('hasPart', $schema_items);
            return $piece;
        }
        
        return null;
    }
    
    /**
     * Extract navigation items from blocks
     */
    private function extract_navigation_items(array $blocks): array
    {
        $items = [];
        
        foreach ($blocks as $block) {
            // Handle various navigation block types
            switch ($block['blockName']) {
                case 'core/navigation-link':
                    // Standard navigation link
                    $attrs = $block['attrs'] ?? [];
                    $label = $attrs['label'] ?? '';
                    $url = $attrs['url'] ?? '';
                    
                    if ($label && $url) {
                        $items[] = [
                            '@type' => 'SiteNavigationElement',
                            'name' => $label,
                            'url' => $url,
                        ];
                    }
                    break;
                    
                case 'core/navigation-submenu':
                    // Submenu block - extract the parent item
                    $attrs = $block['attrs'] ?? [];
                    $label = $attrs['label'] ?? '';
                    $url = $attrs['url'] ?? '';
                    $type = $attrs['type'] ?? 'custom';
                    $id = $attrs['id'] ?? null;
                    
                    // Get URL based on type
                    if (empty($url) && $id) {
                        switch ($type) {
                            case 'post':
                            case 'page':
                                $url = get_permalink($id);
                                break;
                            case 'category':
                            case 'tag':
                            case 'taxonomy':
                                $url = get_term_link($id);
                                break;
                        }
                    }
                    
                    if ($label && $url && !is_wp_error($url)) {
                        $items[] = [
                            '@type' => 'SiteNavigationElement',
                            'name' => $label,
                            'url' => $url,
                        ];
                    }
                    
                    // Don't process innerBlocks for submenus - we only want top-level items
                    break;
                    
                case 'core/home-link':
                    // Home link
                    $items[] = [
                        '@type' => 'SiteNavigationElement',
                        'name' => 'Home',
                        'url' => home_url('/'),
                    ];
                    break;
                    
                case 'core/page-list':
                    // Automatic page list
                    $pages = get_pages([
                        'sort_column' => 'menu_order,post_title',
                        'parent' => 0, // Only top-level pages
                    ]);
                    
                    foreach ($pages as $page) {
                        $title = trim($page->post_title);
                        if (empty($title)) {
                            continue;
                        }
                        
                        $items[] = [
                            '@type' => 'SiteNavigationElement',
                            'name' => $title,
                            'url' => get_permalink($page),
                        ];
                    }
                    break;
                    
                default:
                    // For other blocks, check inner blocks
                    if (!empty($block['innerBlocks'])) {
                        $items = array_merge($items, $this->extract_navigation_items($block['innerBlocks']));
                    }
                    break;
            }
        }
        
        return $items;
    }
    
    /**
     * Get navigation pieces from FSE theme blocks
     */
    private function get_fse_navigation_pieces(): array
    {
        $pieces = [];
        
        // Check if we have any navigation blocks in the current template
        if (function_exists('get_the_block_template_html')) {
            $template_content = '';
            
            // Get template content based on context
            if (is_front_page() || is_home()) {
                $template = get_block_template(get_stylesheet() . '//home');
                if (!$template) {
                    $template = get_block_template(get_stylesheet() . '//front-page');
                }
                if (!$template) {
                    $template = get_block_template(get_stylesheet() . '//index');
                }
            } elseif (is_single()) {
                $template = get_block_template(get_stylesheet() . '//single');
            } elseif (is_page()) {
                $template = get_block_template(get_stylesheet() . '//page');
            } elseif (is_archive()) {
                $template = get_block_template(get_stylesheet() . '//archive');
            }
            
            if ($template && !empty($template->content)) {
                $template_content = $template->content;
            }
            
            // Parse blocks to find navigation
            if ($template_content) {
                $blocks = parse_blocks($template_content);
                $nav_blocks = $this->find_navigation_blocks($blocks);
                
                foreach ($nav_blocks as $index => $nav_block) {
                    $nav_piece = $this->create_navigation_piece_from_block($nav_block, $index);
                    if ($nav_piece) {
                        $pieces[] = $nav_piece;
                    }
                }
            }
        }
        
        return $pieces;
    }
    
    /**
     * Recursively find navigation blocks
     */
    private function find_navigation_blocks(array $blocks): array
    {
        $nav_blocks = [];
        
        foreach ($blocks as $block) {
            if ($block['blockName'] === 'core/navigation') {
                $nav_blocks[] = $block;
            }
            
            // Check inner blocks
            if (!empty($block['innerBlocks'])) {
                $nav_blocks = array_merge($nav_blocks, $this->find_navigation_blocks($block['innerBlocks']));
            }
        }
        
        return $nav_blocks;
    }
    
    /**
     * Create navigation piece from block
     */
    private function create_navigation_piece_from_block(array $block, int $index): ?SchemaPiece
    {
        // Extract menu ref if available
        $menu_ref = $block['attrs']['ref'] ?? null;
        $menu_name = 'primary-navigation';
        
        if ($menu_ref) {
            $menu = wp_get_nav_menu_object($menu_ref);
            if ($menu) {
                $menu_name = sanitize_title($menu->name);
            }
        }
        
        $piece = new SchemaPiece("#navigation-{$menu_name}", 'SiteNavigationElement');
        $piece->set('name', ucfirst(str_replace('-', ' ', $menu_name)));
        
        // Get menu items
        $menu_items = [];
        if ($menu_ref) {
            $items = wp_get_nav_menu_items($menu_ref);
            if ($items) {
                foreach ($items as $item) {
                    if ($item->menu_item_parent == 0) { // Only top-level items
                        $menu_items[] = [
                            '@type' => 'SiteNavigationElement',
                            'name' => $item->title,
                            'url' => $item->url,
                        ];
                    }
                }
            }
        }
        
        if (!empty($menu_items)) {
            $piece->set('hasPart', $menu_items);
        }
        
        return $piece;
    }
    
    /**
     * Get navigation pieces from classic themes
     */
    private function get_classic_navigation_pieces(): array
    {
        $pieces = [];
        
        // Skip if block theme
        if (function_exists('wp_is_block_theme') && wp_is_block_theme()) {
            return $pieces;
        }
        
        // Get all registered nav menu locations
        $locations = get_nav_menu_locations();
        $registered_menus = get_registered_nav_menus();
        
        foreach ($locations as $location => $menu_id) {
            if ($menu_id) {
                $menu = wp_get_nav_menu_object($menu_id);
                if (!$menu) {
                    continue;
                }
                
                $menu_items = wp_get_nav_menu_items($menu_id);
                if (!$menu_items) {
                    continue;
                }
                
                // Create navigation piece
                $piece = new SchemaPiece("#navigation-{$location}", 'SiteNavigationElement');
                
                // Set name from registered menu name or location
                $name = $registered_menus[$location] ?? ucfirst(str_replace('_', ' ', $location));
                $piece->set('name', $name);
                
                // Build menu structure
                $schema_items = [];
                foreach ($menu_items as $item) {
                    if ($item->menu_item_parent == 0) { // Only top-level items
                        $schema_items[] = [
                            '@type' => 'SiteNavigationElement',
                            'name' => $item->title,
                            'url' => $item->url,
                        ];
                    }
                }
                
                if (!empty($schema_items)) {
                    $piece->set('hasPart', $schema_items);
                    $pieces[] = $piece;
                }
            }
        }
        
        return $pieces;
    }
    
    /**
     * Get navigation from rendered blocks (simplified approach)
     */
    private function get_rendered_navigation_pieces(): array
    {
        $pieces = [];
        
        // Check for navigation blocks using has_block
        if (function_exists('has_block') && has_block('core/navigation')) {
            // Get all registered menus as fallback
            $menus = wp_get_nav_menus();
            
            foreach ($menus as $menu) {
                $menu_items = wp_get_nav_menu_items($menu->term_id);
                
                if (empty($menu_items)) {
                    continue;
                }
                
                $menu_slug = sanitize_title($menu->name);
                $piece = new SchemaPiece("#navigation-{$menu_slug}", 'SiteNavigationElement');
                $piece->set('name', $menu->name);
                
                // Build menu items
                $schema_items = [];
                foreach ($menu_items as $item) {
                    if ($item->menu_item_parent == 0) { // Only top-level items
                        $schema_items[] = [
                            '@type' => 'SiteNavigationElement',
                            'name' => $item->title,
                            'url' => $item->url,
                        ];
                    }
                }
                
                if (!empty($schema_items)) {
                    $piece->set('hasPart', $schema_items);
                    $pieces[] = $piece;
                }
            }
        }
        
        return $pieces;
    }
}