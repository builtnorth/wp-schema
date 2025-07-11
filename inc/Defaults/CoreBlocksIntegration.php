<?php

namespace BuiltNorth\Schema\Defaults;

/**
 * Core Blocks Integration
 * 
 * Provides automatic schema data generation for WordPress core Gutenberg blocks.
 * Note: Schema types are determined by post type, not by blocks.
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
     * Register WordPress hooks for core blocks integration
     *
     * @return void
     */
    protected static function register_hooks()
    {
        // Provide schema data for core blocks
        add_filter('wp_schema_data_for_block', [self::class, 'provide_block_data'], 10, 4);
    }

    /**
     * Provide schema data for core blocks
     *
     * @param array|null $custom_data Custom data
     * @param array $block Block data
     * @param string $schema_type Schema type
     * @param array $options Generation options
     * @return array|null Schema data
     */
    public static function provide_block_data($custom_data, $block, $schema_type, $options)
    {
        $block_name = $block['blockName'] ?? '';
        $attrs = $block['attrs'] ?? [];
        $content = $block['innerContent'][0] ?? '';
        
        switch ($block_name) {
            case 'core/paragraph':
                return self::get_paragraph_data($content, $attrs, $schema_type);
            case 'core/heading':
                return self::get_heading_data($content, $attrs, $schema_type);
            case 'core/list':
                return self::get_list_data($content, $attrs, $schema_type);
            case 'core/table':
                return self::get_table_data($content, $attrs, $schema_type);
            case 'core/code':
                return self::get_code_data($content, $attrs, $schema_type);
            case 'core/embed':
                return self::get_embed_data($content, $attrs, $schema_type);
            default:
                return $custom_data;
        }
    }

    /**
     * Get paragraph block data
     *
     * @param string $content Block content
     * @param array $attrs Block attributes
     * @param string $schema_type Schema type
     * @return array|null Paragraph data
     */
    private static function get_paragraph_data($content, $attrs, $schema_type)
    {
        // Only provide text data if the schema type supports it
        if ($schema_type !== 'Article' && $schema_type !== 'WebPage' && $schema_type !== 'CreativeWork') {
            return null;
        }

        $text = wp_strip_all_tags($content);
        if (empty($text)) {
            return null;
        }

        return [
            'text' => $text,
            'name' => $attrs['placeholder'] ?? 'Paragraph'
        ];
    }

    /**
     * Get heading block data
     *
     * @param string $content Block content
     * @param array $attrs Block attributes
     * @param string $schema_type Schema type
     * @return array|null Heading data
     */
    private static function get_heading_data($content, $attrs, $schema_type)
    {
        // Only provide heading data if the schema type supports it
        if ($schema_type !== 'Article' && $schema_type !== 'WebPage' && $schema_type !== 'CreativeWork') {
            return null;
        }

        $level = $attrs['level'] ?? 2;
        $text = wp_strip_all_tags($content);
        
        if (empty($text)) {
            return null;
        }
        
        return [
            'name' => $text,
            'headline' => $text,
            'text' => $text
        ];
    }

    /**
     * Get list block data
     *
     * @param string $content Block content
     * @param array $attrs Block attributes
     * @param string $schema_type Schema type
     * @return array|null List data
     */
    private static function get_list_data($content, $attrs, $schema_type)
    {
        // Only provide list data if the schema type supports it
        if ($schema_type !== 'Article' && $schema_type !== 'WebPage' && $schema_type !== 'CreativeWork') {
            return null;
        }

        // Parse list items from HTML
        preg_match_all('/<li[^>]*>(.*?)<\/li>/s', $content, $matches);
        $list_items = $matches[1] ?? [];

        if (empty($list_items)) {
            return null;
        }

        return [
            'name' => 'List',
            'text' => wp_strip_all_tags($content),
            'listItem' => array_map('wp_strip_all_tags', $list_items)
        ];
    }

    /**
     * Get table block data
     *
     * @param string $content Block content
     * @param array $attrs Block attributes
     * @param string $schema_type Schema type
     * @return array|null Table data
     */
    private static function get_table_data($content, $attrs, $schema_type)
    {
        // Only provide table data if the schema type supports it
        if ($schema_type !== 'Table' && $schema_type !== 'WebPage' && $schema_type !== 'Article') {
            return null;
        }

        $text = wp_strip_all_tags($content);
        if (empty($text)) {
            return null;
        }

        return [
            'name' => $attrs['caption'] ?? 'Table',
            'text' => $text
        ];
    }

    /**
     * Get code block data
     *
     * @param string $content Block content
     * @param array $attrs Block attributes
     * @param string $schema_type Schema type
     * @return array|null Code data
     */
    private static function get_code_data($content, $attrs, $schema_type)
    {
        // Only provide code data if the schema type supports it
        if ($schema_type !== 'SoftwareSourceCode' && $schema_type !== 'CreativeWork' && $schema_type !== 'Article') {
            return null;
        }

        if (empty($content)) {
            return null;
        }

        return [
            'name' => 'Code Block',
            'text' => $content,
            'programmingLanguage' => $attrs['language'] ?? 'text'
        ];
    }

    /**
     * Get embed block data
     *
     * @param string $content Block content
     * @param array $attrs Block attributes
     * @param string $schema_type Schema type
     * @return array|null Embed data
     */
    private static function get_embed_data($content, $attrs, $schema_type)
    {
        // Only provide embed data if the schema type supports it
        if ($schema_type !== 'MediaObject' && $schema_type !== 'VideoObject' && $schema_type !== 'CreativeWork' && $schema_type !== 'WebPage') {
            return null;
        }

        $url = $attrs['url'] ?? '';
        $provider = $attrs['providerNameSlug'] ?? '';

        if (empty($url)) {
            return null;
        }

        $embed_data = [
            'name' => $attrs['title'] ?? 'Embedded Content',
            'url' => $url
        ];

        // Add specific properties based on provider
        switch ($provider) {
            case 'youtube':
                $embed_data['@type'] = 'VideoObject';
                $embed_data['embedUrl'] = $url;
                break;
            case 'vimeo':
                $embed_data['@type'] = 'VideoObject';
                $embed_data['embedUrl'] = $url;
                break;
            case 'twitter':
                $embed_data['@type'] = 'CreativeWork';
                break;
            default:
                $embed_data['@type'] = 'MediaObject';
        }

        return $embed_data;
    }

    /**
     * Check if core blocks are available
     *
     * @return bool
     */
    public static function is_available()
    {
        return true; // Always available in WordPress
    }

    /**
     * Get integration description
     *
     * @return string
     */
    public static function get_description()
    {
        return 'Schema data for WordPress core Gutenberg blocks (paragraph, heading, list, table, code, embed, etc.)';
    }

    /**
     * Get supported schema types
     *
     * @return array
     */
    public static function get_supported_schema_types()
    {
        return ['Article', 'WebPage', 'CreativeWork', 'Table', 'SoftwareSourceCode', 'MediaObject', 'VideoObject'];
    }
} 