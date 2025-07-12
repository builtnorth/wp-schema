<?php

namespace BuiltNorth\Schema\Defaults;

/**
 * Core Blocks Integration
 * 
 * Provides schema data generation for WordPress core blocks.
 * This integration focuses on navigation blocks and other core block types.
 */
class CoreBlocksIntegration extends BaseIntegration
{
    /**
     * Integration name
     *
     * @var string
     */
    protected static $integration_name = 'core_blocks';

    /**
     * Register WordPress hooks for Core Blocks integration
     *
     * @return void
     */
    protected static function register_hooks()
    {
        // Provide navigation schema from core/navigation blocks
        add_filter('wp_schema_context_schemas', [self::class, 'provide_navigation_schema'], 10, 3);
    }

    /**
     * Provide navigation schema from core/navigation blocks
     *
     * @param array $schemas Existing schemas
     * @param string $context Current context
     * @param array $options Generation options
     * @return array Modified schemas
     */
    public static function provide_navigation_schema($schemas, $context, $options)
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CoreBlocksIntegration::provide_navigation_schema() called');
        }
        
        // Find navigation blocks in the current page/post
        $navigation_blocks = self::find_navigation_blocks();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CoreBlocksIntegration::provide_navigation_schema() - found navigation blocks: ' . count($navigation_blocks));
        }
        
        if (!empty($navigation_blocks)) {
            foreach ($navigation_blocks as $nav_block) {
                $nav_schema = self::build_navigation_schema($nav_block, $options);
                if (!empty($nav_schema)) {
                    $schemas[] = $nav_schema;
                }
            }
        }

        return $schemas;
    }

    /**
     * Find navigation blocks in current content
     *
     * @return array Array of navigation blocks
     */
    private static function find_navigation_blocks()
    {
        $navigation_blocks = [];
        
        // Get current post/page content
        if (is_singular()) {
            $post = get_post();
            if ($post) {
                $blocks = parse_blocks($post->post_content);
                $navigation_blocks = self::extract_navigation_blocks($blocks);
            }
        }
        
        // Also check for navigation in theme areas (header, footer, etc.)
        $theme_blocks = self::get_theme_navigation_blocks();
        $navigation_blocks = array_merge($navigation_blocks, $theme_blocks);
        
        return $navigation_blocks;
    }

    /**
     * Extract navigation blocks from parsed blocks
     *
     * @param array $blocks Parsed blocks
     * @return array Navigation blocks
     */
    private static function extract_navigation_blocks($blocks)
    {
        $navigation_blocks = [];
        
        foreach ($blocks as $block) {
            if ($block['blockName'] === 'core/navigation' || $block['blockName'] === 'wp:navigation') {
                $navigation_blocks[] = $block;
            }
            
            // Recursively check inner blocks
            if (!empty($block['innerBlocks'])) {
                $inner_nav_blocks = self::extract_navigation_blocks($block['innerBlocks']);
                $navigation_blocks = array_merge($navigation_blocks, $inner_nav_blocks);
            }
        }
        
        return $navigation_blocks;
    }

    /**
     * Get navigation blocks from theme areas
     *
     * @return array Navigation blocks from theme
     */
    private static function get_theme_navigation_blocks()
    {
        $navigation_blocks = [];
        
        // Check for navigation in header
        if (has_block('core/navigation', 'header')) {
            $header_blocks = parse_blocks(get_block_template_part('header'));
            $nav_blocks = self::extract_navigation_blocks($header_blocks);
            $navigation_blocks = array_merge($navigation_blocks, $nav_blocks);
        }
        
        // Check for navigation in footer
        if (has_block('core/navigation', 'footer')) {
            $footer_blocks = parse_blocks(get_block_template_part('footer'));
            $nav_blocks = self::extract_navigation_blocks($footer_blocks);
            $navigation_blocks = array_merge($navigation_blocks, $nav_blocks);
        }
        
        return $navigation_blocks;
    }

    /**
     * Build navigation schema from block data
     *
     * @param array $nav_block Navigation block data
     * @param array $options Generation options
     * @return array Navigation schema
     */
    private static function build_navigation_schema($nav_block, $options)
    {
        $attrs = $nav_block['attrs'] ?? [];
        $inner_blocks = $nav_block['innerBlocks'] ?? [];
        
        $navigation_schema = [
            '@context' => 'https://schema.org',
            '@type' => 'SiteNavigationElement',
            'name' => $attrs['ariaLabel'] ?? 'Main Navigation',
        ];
        
        // Get navigation items from the referenced navigation menu
        $nav_items = self::get_navigation_items_from_ref($nav_block);
        
        // Fallback to inner blocks if no ref found
        if (empty($nav_items)) {
            $nav_items = self::extract_navigation_items($inner_blocks);
        }
        
        if (!empty($nav_items)) {
            $navigation_schema['hasPart'] = $nav_items;
        }
        
        // Add URL if available
        $url = $options['canonical_url'] ?? home_url('/');
        if ($url) {
            $navigation_schema['url'] = $url;
        }
        
        return $navigation_schema;
    }

    /**
     * Get navigation items from referenced navigation menu
     *
     * @param array $nav_block Navigation block data
     * @return array Navigation items
     */
    private static function get_navigation_items_from_ref($nav_block)
    {
        $nav_items = [];
        $attrs = $nav_block['attrs'] ?? [];
        
        // Try to get ref from attrs
        $ref = null;
        if (isset($attrs['ref']) && is_numeric($attrs['ref'])) {
            $ref = (int)$attrs['ref'];
        } elseif (isset($nav_block['innerHTML'])) {
            // Fallback: regex the ref from the raw block HTML
            if (preg_match('/"ref":\s*(\d+)/', $nav_block['innerHTML'], $matches)) {
                $ref = (int)$matches[1];
            }
        }
        
        if ($ref) {
            $nav_post = get_post($ref);
            if ($nav_post && $nav_post->post_type === 'wp_navigation') {
                $nav_blocks = parse_blocks($nav_post->post_content);
                $nav_items = self::extract_navigation_items($nav_blocks);
            }
        }
        
        return $nav_items;
    }

    /**
     * Extract navigation items from navigation block
     *
     * @param array $blocks Inner blocks of navigation
     * @return array Navigation items
     */
    private static function extract_navigation_items($blocks)
    {
        $nav_items = [];
        
        foreach ($blocks as $block) {
            if ($block['blockName'] === 'core/navigation-link') {
                $nav_item = self::build_navigation_item($block);
                if (!empty($nav_item)) {
                    $nav_items[] = $nav_item;
                }
            }
            
            // Handle navigation submenu
            if ($block['blockName'] === 'core/navigation-submenu') {
                // Extract parent submenu link if present
                if (isset($block['attrs']['label']) && isset($block['attrs']['url'])) {
                    $submenu_item = [
                        '@type' => 'WebPage',
                        'name' => self::sanitize_text($block['attrs']['label']),
                        'url' => esc_url($block['attrs']['url'])
                    ];
                    $nav_items[] = $submenu_item;
                }
                
                // Recursively extract submenu children
                $submenu_items = self::extract_navigation_items($block['innerBlocks'] ?? []);
                if (!empty($submenu_items)) {
                    $nav_items = array_merge($nav_items, $submenu_items);
                }
            }
        }
        
        return $nav_items;
    }

    /**
     * Build navigation item schema
     *
     * @param array $link_block Navigation link block
     * @return array Navigation item schema
     */
    private static function build_navigation_item($link_block)
    {
        $attrs = $link_block['attrs'] ?? [];
        $inner_content = $link_block['innerContent'] ?? [];
        
        $url = $attrs['url'] ?? '';
        $label = $attrs['label'] ?? '';
        
        // Extract label from inner content if not in attributes
        if (empty($label) && !empty($inner_content)) {
            $label = strip_tags(implode('', $inner_content));
        }
        
        if (empty($url) || empty($label)) {
            return [];
        }
        
        return [
            '@type' => 'WebPage',
            'name' => self::sanitize_text($label),
            'url' => esc_url($url)
        ];
    }

    /**
     * Sanitize text for schema output
     *
     * @param string $text Text to sanitize
     * @return string Sanitized text
     */
    private static function sanitize_text($text)
    {
        return wp_strip_all_tags($text);
    }

    /**
     * Check if integration is available
     *
     * @return bool
     */
    public static function is_available()
    {
        return true; // Always available since core blocks are always present
    }

    /**
     * Get integration description
     *
     * @return string
     */
    public static function get_description()
    {
        return 'Provides navigation schema from WordPress core navigation blocks.';
    }

    /**
     * Get supported schema types
     *
     * @return array
     */
    public static function get_supported_schema_types()
    {
        return ['navigation'];
    }
} 