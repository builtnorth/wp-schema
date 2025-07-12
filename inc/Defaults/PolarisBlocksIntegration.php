<?php

namespace BuiltNorth\Schema\Defaults;

/**
 * Polaris Blocks Integration
 * 
 * Provides schema data generation for Polaris framework blocks.
 * This integration focuses on breadcrumb blocks and other Polaris-specific blocks.
 */
class PolarisBlocksIntegration extends BaseIntegration
{
    /**
     * Integration name
     *
     * @var string
     */
    protected static $integration_name = 'polaris_blocks';

    /**
     * Register WordPress hooks for Polaris Blocks integration
     *
     * @return void
     */
    protected static function register_hooks()
    {
        // Provide breadcrumb schema from Polaris breadcrumb blocks
        add_filter('wp_schema_context_schemas', [self::class, 'provide_breadcrumb_schema'], 10, 3);
    }

    /**
     * Provide breadcrumb schema from Polaris breadcrumb blocks
     *
     * @param array $schemas Existing schemas
     * @param string $context Current context
     * @param array $options Generation options
     * @return array Modified schemas
     */
    public static function provide_breadcrumb_schema($schemas, $context, $options)
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('PolarisBlocksIntegration::provide_breadcrumb_schema() called');
        }
        
        // Find breadcrumb blocks in the current page/post
        $breadcrumb_blocks = self::find_breadcrumb_blocks();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('PolarisBlocksIntegration::provide_breadcrumb_schema() - found breadcrumb blocks: ' . count($breadcrumb_blocks));
        }
        
        if (!empty($breadcrumb_blocks)) {
            foreach ($breadcrumb_blocks as $breadcrumb_block) {
                $breadcrumb_schema = self::build_breadcrumb_schema($breadcrumb_block, $options);
                if (!empty($breadcrumb_schema)) {
                    $schemas[] = $breadcrumb_schema;
                }
            }
        }

        return $schemas;
    }

    /**
     * Find breadcrumb blocks in current content
     *
     * @return array Array of breadcrumb blocks
     */
    private static function find_breadcrumb_blocks()
    {
        $breadcrumb_blocks = [];
        
        // Get current post/page content
        if (is_singular()) {
            $post = get_post();
            if ($post) {
                $blocks = parse_blocks($post->post_content);
                $breadcrumb_blocks = self::extract_breadcrumb_blocks($blocks);
            }
        }
        
        // Also check for breadcrumbs in theme areas
        $theme_blocks = self::get_theme_breadcrumb_blocks();
        $breadcrumb_blocks = array_merge($breadcrumb_blocks, $theme_blocks);
        
        return $breadcrumb_blocks;
    }

    /**
     * Extract breadcrumb blocks from parsed blocks
     *
     * @param array $blocks Parsed blocks
     * @return array Breadcrumb blocks
     */
    private static function extract_breadcrumb_blocks($blocks)
    {
        $breadcrumb_blocks = [];
        
        foreach ($blocks as $block) {
            // Check for Polaris breadcrumb blocks
            if (strpos($block['blockName'] ?? '', 'polaris/breadcrumbs') === 0) {
                $breadcrumb_blocks[] = $block;
            }
            
            // Recursively check inner blocks
            if (!empty($block['innerBlocks'])) {
                $inner_breadcrumb_blocks = self::extract_breadcrumb_blocks($block['innerBlocks']);
                $breadcrumb_blocks = array_merge($breadcrumb_blocks, $inner_breadcrumb_blocks);
            }
        }
        
        return $breadcrumb_blocks;
    }

    /**
     * Get breadcrumb blocks from theme areas
     *
     * @return array Breadcrumb blocks from theme
     */
    private static function get_theme_breadcrumb_blocks()
    {
        $breadcrumb_blocks = [];
        
        // Check for breadcrumbs in header
        if (has_block('polaris/breadcrumbs', 'header')) {
            $header_blocks = parse_blocks(get_block_template_part('header'));
            $breadcrumb_blocks = self::extract_breadcrumb_blocks($header_blocks);
        }
        
        // Check for breadcrumbs in footer
        if (has_block('polaris/breadcrumbs', 'footer')) {
            $footer_blocks = parse_blocks(get_block_template_part('footer'));
            $breadcrumb_blocks = array_merge($breadcrumb_blocks, self::extract_breadcrumb_blocks($footer_blocks));
        }
        
        return $breadcrumb_blocks;
    }

    /**
     * Build breadcrumb schema from block data
     *
     * @param array $breadcrumb_block Breadcrumb block data
     * @param array $options Generation options
     * @return array Breadcrumb schema
     */
    private static function build_breadcrumb_schema($breadcrumb_block, $options)
    {
        $attrs = $breadcrumb_block['attrs'] ?? [];
        $inner_blocks = $breadcrumb_block['innerBlocks'] ?? [];
        
        $breadcrumb_schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => []
        ];
        
        // Extract breadcrumb items
        $breadcrumb_items = self::extract_breadcrumb_items($inner_blocks);
        if (!empty($breadcrumb_items)) {
            $breadcrumb_schema['itemListElement'] = $breadcrumb_items;
        } else {
            // Fallback to generating breadcrumbs from WordPress hierarchy
            $breadcrumb_schema['itemListElement'] = self::generate_breadcrumb_items();
        }
        
        return $breadcrumb_schema;
    }

    /**
     * Extract breadcrumb items from breadcrumb block
     *
     * @param array $blocks Inner blocks of breadcrumb
     * @return array Breadcrumb items
     */
    private static function extract_breadcrumb_items($blocks)
    {
        $breadcrumb_items = [];
        $position = 1;
        
        foreach ($blocks as $block) {
            if (strpos($block['blockName'] ?? '', 'polaris/breadcrumb-item') === 0) {
                $breadcrumb_item = self::build_breadcrumb_item($block, $position);
                if (!empty($breadcrumb_item)) {
                    $breadcrumb_items[] = $breadcrumb_item;
                    $position++;
                }
            }
        }
        
        return $breadcrumb_items;
    }

    /**
     * Build breadcrumb item schema
     *
     * @param array $item_block Breadcrumb item block
     * @param int $position Position in breadcrumb list
     * @return array Breadcrumb item schema
     */
    private static function build_breadcrumb_item($item_block, $position)
    {
        $attrs = $item_block['attrs'] ?? [];
        $inner_content = $item_block['innerContent'] ?? [];
        
        $url = $attrs['url'] ?? '';
        $label = $attrs['label'] ?? '';
        
        // Extract label from inner content if not in attributes
        if (empty($label) && !empty($inner_content)) {
            $label = strip_tags(implode('', $inner_content));
        }
        
        if (empty($label)) {
            return [];
        }
        
        $breadcrumb_item = [
            '@type' => 'ListItem',
            'position' => $position,
            'name' => $label
        ];
        
        if (!empty($url)) {
            $breadcrumb_item['item'] = $url;
        }
        
        return $breadcrumb_item;
    }

    /**
     * Generate breadcrumb items from WordPress hierarchy
     *
     * @return array Breadcrumb items
     */
    private static function generate_breadcrumb_items()
    {
        $breadcrumb_items = [];
        $position = 1;
        
        // Always start with home
        $breadcrumb_items[] = [
            '@type' => 'ListItem',
            'position' => $position,
            'name' => 'Home',
            'item' => home_url('/')
        ];
        $position++;
        
        // Add current page/post
        if (is_singular()) {
            $post = get_post();
            if ($post) {
                $breadcrumb_items[] = [
                    '@type' => 'ListItem',
                    'position' => $position,
                    'name' => get_the_title($post),
                    'item' => get_permalink($post)
                ];
            }
        } elseif (is_tax() || is_category() || is_tag()) {
            $term = get_queried_object();
            if ($term) {
                $breadcrumb_items[] = [
                    '@type' => 'ListItem',
                    'position' => $position,
                    'name' => $term->name,
                    'item' => get_term_link($term)
                ];
            }
        } elseif (is_archive()) {
            $breadcrumb_items[] = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => get_the_archive_title(),
                'item' => get_pagenum_link()
            ];
        }
        
        return $breadcrumb_items;
    }

    /**
     * Check if integration is available
     *
     * @return bool
     */
    public static function is_available()
    {
        // Check if Polaris framework is available
        return class_exists('BuiltNorth\Polaris\App');
    }

    /**
     * Get integration description
     *
     * @return string
     */
    public static function get_description()
    {
        return 'Provides breadcrumb schema from Polaris breadcrumb blocks.';
    }

    /**
     * Get supported schema types
     *
     * @return array
     */
    public static function get_supported_schema_types()
    {
        return ['breadcrumb'];
    }
} 