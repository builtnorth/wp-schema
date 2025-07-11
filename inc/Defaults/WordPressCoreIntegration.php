<?php

namespace BuiltNorth\Schema\Defaults;

/**
 * WordPress Core Integration
 * 
 * Provides automatic schema data generation for WordPress core post types and content.
 * Note: Schema types are determined by post type, not by blocks.
 */
class WordPressCoreIntegration extends BaseIntegration
{
    /**
     * Integration name
     *
     * @var string
     */
    protected static $integration_name = 'wordpress_core';

    /**
     * Register WordPress hooks for core integration
     *
     * @return void
     */
    protected static function register_hooks()
    {
        // Override schema type for core post types
        add_filter('wp_schema_type_for_post', [self::class, 'override_schema_type'], 10, 3);
        
        // Provide schema data for core post types
        add_filter('wp_schema_data_for_post', [self::class, 'provide_post_data'], 10, 4);
        
        // Provide schema data for core blocks
        add_filter('wp_schema_data_for_block', [self::class, 'provide_block_data'], 10, 4);
        
        // Add common schema properties
        add_filter('wp_schema_final_schema', [self::class, 'add_common_properties'], 10, 4);
    }

    /**
     * Override schema type for core post types
     *
     * @param string|null $schema_type Current schema type
     * @param int $post_id Post ID
     * @param array $options Generation options
     * @return string|null Schema type
     */
    public static function override_schema_type($schema_type, $post_id, $options)
    {
        $post_type = get_post_type($post_id);
        
        switch ($post_type) {
            case 'post':
                return 'Article';
            case 'page':
                return 'WebPage';
            case 'attachment':
                return 'ImageObject';
            default:
                return $schema_type;
        }
    }

    /**
     * Provide schema data for core post types
     *
     * @param array|null $custom_data Custom data
     * @param int $post_id Post ID
     * @param string $schema_type Schema type
     * @param array $options Generation options
     * @return array|null Schema data
     */
    public static function provide_post_data($custom_data, $post_id, $schema_type, $options)
    {
        $post = get_post($post_id);
        if (!$post) {
            return $custom_data;
        }

        $post_type = get_post_type($post_id);
        
        // Handle different post types
        switch ($post_type) {
            case 'post':
                return self::get_article_data($post, $schema_type);
            case 'page':
                return self::get_webpage_data($post, $schema_type);
            case 'attachment':
                return self::get_image_data($post, $schema_type);
            default:
                return $custom_data;
        }
    }

    /**
     * Get article data for blog posts
     *
     * @param WP_Post $post Post object
     * @param string $schema_type Schema type
     * @return array|null Article data
     */
    private static function get_article_data($post, $schema_type)
    {
        if ($schema_type !== 'Article') {
            return null;
        }

        $author = get_userdata($post->post_author);
        
        $article_data = [
            'name' => get_the_title($post->ID),
            'headline' => get_the_title($post->ID),
            'description' => get_the_excerpt($post->ID),
            'url' => get_permalink($post->ID),
            'datePublished' => get_the_date('c', $post->ID),
            'dateModified' => get_the_modified_date('c', $post->ID),
            'author' => [
                '@type' => 'Person',
                'name' => $author ? $author->display_name : 'Unknown Author'
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => get_site_icon_url()
                ]
            ]
        ];

        // Add featured image
        $image_id = get_post_thumbnail_id($post->ID);
        if ($image_id) {
            $article_data['image'] = wp_get_attachment_image_url($image_id, 'full');
        }

        // Add categories
        $categories = get_the_category($post->ID);
        if (!empty($categories)) {
            $article_data['articleSection'] = $categories[0]->name;
        }

        // Add tags
        $tags = get_the_tags($post->ID);
        if (!empty($tags)) {
            $article_data['keywords'] = implode(', ', wp_list_pluck($tags, 'name'));
        }

        return $article_data;
    }

    /**
     * Get webpage data for pages
     *
     * @param WP_Post $post Post object
     * @param string $schema_type Schema type
     * @return array|null Webpage data
     */
    private static function get_webpage_data($post, $schema_type)
    {
        if ($schema_type !== 'WebPage') {
            return null;
        }

        return [
            'name' => get_the_title($post->ID),
            'description' => get_the_excerpt($post->ID),
            'url' => get_permalink($post->ID),
            'datePublished' => get_the_date('c', $post->ID),
            'dateModified' => get_the_modified_date('c', $post->ID)
        ];
    }

    /**
     * Get image data for attachments
     *
     * @param WP_Post $post Post object
     * @param string $schema_type Schema type
     * @return array|null Image data
     */
    private static function get_image_data($post, $schema_type)
    {
        if ($schema_type !== 'ImageObject') {
            return null;
        }

        $image_url = wp_get_attachment_image_url($post->ID, 'full');
        $image_meta = wp_get_attachment_metadata($post->ID);

        $image_data = [
            'name' => get_the_title($post->ID),
            'url' => $image_url,
            'contentUrl' => $image_url
        ];

        // Add image dimensions if available
        if ($image_meta && isset($image_meta['width']) && isset($image_meta['height'])) {
            $image_data['width'] = $image_meta['width'];
            $image_data['height'] = $image_meta['height'];
        }

        // Add caption if available
        $caption = wp_get_attachment_caption($post->ID);
        if ($caption) {
            $image_data['caption'] = $caption;
        }

        return $image_data;
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
        
        switch ($block_name) {
            case 'core/image':
                return self::get_image_block_data($attrs, $schema_type);
            case 'core/gallery':
                return self::get_gallery_block_data($attrs, $schema_type);
            case 'core/quote':
                return self::get_quote_block_data($attrs, $schema_type);
            case 'core/video':
                return self::get_video_block_data($attrs, $schema_type);
            case 'core/audio':
                return self::get_audio_block_data($attrs, $schema_type);
            default:
                return $custom_data;
        }
    }

    /**
     * Get image block data
     *
     * @param array $attrs Block attributes
     * @param string $schema_type Schema type
     * @return array|null Image data
     */
    private static function get_image_block_data($attrs, $schema_type)
    {
        // Only provide image data if the schema type supports it
        if ($schema_type !== 'ImageObject' && $schema_type !== 'WebPage' && $schema_type !== 'Article' && $schema_type !== 'CreativeWork') {
            return null;
        }

        $image_id = $attrs['id'] ?? 0;
        if (!$image_id) {
            return null;
        }

        $image_url = wp_get_attachment_image_url($image_id, 'full');
        $image_meta = wp_get_attachment_metadata($image_id);

        $image_data = [
            '@type' => 'ImageObject',
            'url' => $image_url,
            'contentUrl' => $image_url
        ];

        // Add image dimensions if available
        if ($image_meta && isset($image_meta['width']) && isset($image_meta['height'])) {
            $image_data['width'] = $image_meta['width'];
            $image_data['height'] = $image_meta['height'];
        }

        // Add caption if available
        $caption = wp_get_attachment_caption($image_id);
        if ($caption) {
            $image_data['caption'] = $caption;
        }

        // Add alt text if available
        if ($attrs['alt'] ?? false) {
            $image_data['name'] = $attrs['alt'];
        }

        return $image_data;
    }

    /**
     * Get gallery block data
     *
     * @param array $attrs Block attributes
     * @param string $schema_type Schema type
     * @return array|null Gallery data
     */
    private static function get_gallery_block_data($attrs, $schema_type)
    {
        // Only provide gallery data if the schema type supports it
        if ($schema_type !== 'ImageGallery' && $schema_type !== 'WebPage' && $schema_type !== 'Article' && $schema_type !== 'CreativeWork') {
            return null;
        }

        $image_ids = $attrs['ids'] ?? [];
        if (empty($image_ids)) {
            return null;
        }

        $images = [];
        foreach ($image_ids as $image_id) {
            $image_url = wp_get_attachment_image_url($image_id, 'full');
            if ($image_url) {
                $images[] = [
                    '@type' => 'ImageObject',
                    'url' => $image_url
                ];
            }
        }

        if (empty($images)) {
            return null;
        }

        return [
            '@type' => 'ImageGallery',
            'image' => $images
        ];
    }

    /**
     * Get quote block data
     *
     * @param array $attrs Block attributes
     * @param string $schema_type Schema type
     * @return array|null Quote data
     */
    private static function get_quote_block_data($attrs, $schema_type)
    {
        // Only provide quote data if the schema type supports it
        if ($schema_type !== 'Quotation' && $schema_type !== 'Article' && $schema_type !== 'CreativeWork' && $schema_type !== 'WebPage') {
            return null;
        }

        $quote_text = $attrs['value'] ?? '';
        $citation = $attrs['citation'] ?? '';

        if (empty($quote_text)) {
            return null;
        }

        $quote_data = [
            '@type' => 'Quotation',
            'text' => $quote_text
        ];

        if ($citation) {
            $quote_data['citation'] = $citation;
        }

        return $quote_data;
    }

    /**
     * Get video block data
     *
     * @param array $attrs Block attributes
     * @param string $schema_type Schema type
     * @return array|null Video data
     */
    private static function get_video_block_data($attrs, $schema_type)
    {
        // Only provide video data if the schema type supports it
        if ($schema_type !== 'VideoObject' && $schema_type !== 'MediaObject' && $schema_type !== 'WebPage' && $schema_type !== 'Article') {
            return null;
        }

        $video_url = $attrs['src'] ?? '';
        if (empty($video_url)) {
            return null;
        }

        return [
            '@type' => 'VideoObject',
            'url' => $video_url,
            'contentUrl' => $video_url
        ];
    }

    /**
     * Get audio block data
     *
     * @param array $attrs Block attributes
     * @param string $schema_type Schema type
     * @return array|null Audio data
     */
    private static function get_audio_block_data($attrs, $schema_type)
    {
        // Only provide audio data if the schema type supports it
        if ($schema_type !== 'AudioObject' && $schema_type !== 'MediaObject' && $schema_type !== 'WebPage' && $schema_type !== 'Article') {
            return null;
        }

        $audio_url = $attrs['src'] ?? '';
        if (empty($audio_url)) {
            return null;
        }

        return [
            '@type' => 'AudioObject',
            'url' => $audio_url,
            'contentUrl' => $audio_url
        ];
    }

    /**
     * Add common schema properties
     *
     * @param array $schema Schema data
     * @param string $content Content
     * @param string $type Schema type
     * @param array $options Generation options
     * @return array Modified schema
     */
    public static function add_common_properties($schema, $content, $type, $options)
    {
        // Add site information
        $schema['publisher'] = [
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'url' => get_home_url()
        ];

        // Add site logo if available
        $logo_url = get_site_icon_url();
        if ($logo_url) {
            $schema['publisher']['logo'] = [
                '@type' => 'ImageObject',
                'url' => $logo_url
            ];
        }

        return $schema;
    }

    /**
     * Check if WordPress core is available
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
        return 'Schema data for WordPress core post types and blocks (posts, pages, images, galleries, quotes, videos, audio, etc.)';
    }

    /**
     * Get supported schema types
     *
     * @return array
     */
    public static function get_supported_schema_types()
    {
        return ['Article', 'WebPage', 'ImageObject', 'ImageGallery', 'Quotation', 'VideoObject', 'AudioObject', 'CreativeWork'];
    }
} 