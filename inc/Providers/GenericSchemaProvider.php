<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Providers;

use BuiltNorth\WPSchema\Contracts\SchemaProviderInterface;
use BuiltNorth\WPSchema\Graph\SchemaPiece;

/**
 * Generic Schema Provider
 * 
 * Provides fallback schema for any schema type that doesn't have a specific provider.
 * This ensures that even custom schema types like VideoObject, Product, etc. get
 * basic schema output with essential properties.
 * 
 * @since 3.0.0
 */
class GenericSchemaProvider implements SchemaProviderInterface
{
    /**
     * Schema types that are handled by other specific providers
     */
    private array $handled_types = [
        'Article', 
        'BlogPosting', 
        'NewsArticle',
        'WebPage',
        'AboutPage',
        'ContactPage',
        'Organization',
        'Person',
        'WebSite',
        // Add other types that have specific providers
    ];

    public function can_provide(string $context): bool
    {
        // Handle homepage
        if ($context === 'home') {
            $seo_settings = get_option('polaris_seo_settings', []);
            $schema_type = $seo_settings['home']['default_schema_type'] ?? 'WebPage';
            $schema_type = apply_filters('wp_schema_framework_homepage_type', $schema_type);
            // Only handle types that don't have specific providers
            return !empty($schema_type) && !in_array($schema_type, $this->handled_types, true);
        }
        
        if ($context !== 'singular') {
            return false;
        }
        
        $post = get_queried_object();
        if (!$post || !isset($post->ID)) {
            return false;
        }
        
        // Get schema type with filters
        $default_type = $this->get_default_schema_type($post->post_type);
        $schema_type = apply_filters('wp_schema_framework_post_type_override', $default_type, $post->ID, $post->post_type, $post);
        
        // Only handle types that don't have specific providers
        return !empty($schema_type) && !in_array($schema_type, $this->handled_types, true);
    }
    
    public function get_pieces(string $context): array
    {
        // Handle homepage
        if ($context === 'home') {
            $seo_settings = get_option('polaris_seo_settings', []);
            $schema_type = $seo_settings['home']['default_schema_type'] ?? 'WebPage';
            $schema_type = apply_filters('wp_schema_framework_homepage_type', $schema_type);
            
            // Create generic schema piece for homepage
            $generic = new SchemaPiece('homepage', $schema_type);
            
            // Add common properties
            $generic
                ->set('name', get_bloginfo('name'))
                ->set('url', home_url());
            
            // Add description
            $description = get_bloginfo('description');
            if ($description) {
                $generic->set('description', $description);
            }
            
            // If front page is a static page, use its content
            if (get_option('show_on_front') === 'page') {
                $page_id = get_option('page_on_front');
                if ($page_id) {
                    $post = get_post($page_id);
                    if ($post) {
                        $generic->set('name', $post->post_title);
                        
                        if ($post->post_excerpt) {
                            $generic->set('description', wp_strip_all_tags($post->post_excerpt));
                        }
                        
                        // Add dates for types that support them
                        if ($this->supports_dates($schema_type)) {
                            $generic
                                ->set('datePublished', get_the_date('c', $page_id))
                                ->set('dateModified', get_the_modified_date('c', $page_id));
                        }
                        
                        // Add featured image
                        if (has_post_thumbnail($page_id)) {
                            $image_url = get_the_post_thumbnail_url($page_id, 'full');
                            if ($image_url) {
                                $generic->set('image', [
                                    '@type' => 'ImageObject',
                                    'url' => $image_url,
                                ]);
                            }
                        }
                    }
                }
            }
            
            // Add author for types that support it
            if ($this->supports_author($schema_type)) {
                $generic->add_reference('author', '#author');
            }
            
            // Add publisher for types that support it
            if ($this->supports_publisher($schema_type)) {
                $generic->add_reference('publisher', '#organization');
            }
            
            // Add website reference
            $generic->add_reference('isPartOf', '#website');
            
            // Allow filtering of homepage data
            $data = apply_filters('wp_schema_framework_homepage_data', $generic->to_array());
            $data = apply_filters('wp_schema_framework_generic_data', $data, 0, null);
            $data = apply_filters('wp_schema_framework_' . strtolower($schema_type) . '_data', $data, 0, null);
            $generic->from_array($data);
            
            return [$generic];
        }
        
        // Handle regular posts
        $post = get_queried_object();
        if (!$post) {
            return [];
        }
        
        // Get schema type
        $default_type = $this->get_default_schema_type($post->post_type);
        $schema_type = apply_filters('wp_schema_framework_post_type_override', $default_type, $post->ID, $post->post_type, $post);
        
        // Create generic schema piece with unique ID
        $piece_id = $post->post_type . '-' . $post->ID;
        $generic = new SchemaPiece($piece_id, $schema_type);
        
        // Add common properties that most schema types support
        $generic
            ->set('name', $post->post_title)
            ->set('url', get_permalink($post->ID));
        
        // Add description if available
        if ($post->post_excerpt) {
            $generic->set('description', wp_strip_all_tags($post->post_excerpt));
        }
        
        // Add dates for types that support them
        if ($this->supports_dates($schema_type)) {
            $generic
                ->set('datePublished', get_the_date('c', $post->ID))
                ->set('dateModified', get_the_modified_date('c', $post->ID));
        }
        
        // Add image if available
        if (has_post_thumbnail($post->ID)) {
            $image_url = get_the_post_thumbnail_url($post->ID, 'full');
            if ($image_url) {
                $generic->set('image', [
                    '@type' => 'ImageObject',
                    'url' => $image_url,
                ]);
            }
        }
        
        // Add author for types that support it
        if ($this->supports_author($schema_type)) {
            $generic->add_reference('author', '#author');
        }
        
        // Add publisher for types that support it
        if ($this->supports_publisher($schema_type)) {
            $generic->add_reference('publisher', '#organization');
        }
        
        // Add website reference
        $generic->add_reference('isPartOf', '#website');
        
        // Add breadcrumb reference
        $generic->add_reference('breadcrumb', '#breadcrumb');
        
        // Allow filtering of generic schema data
        $data = apply_filters('wp_schema_framework_generic_data', $generic->to_array(), $post->ID, $post);
        $data = apply_filters('wp_schema_framework_' . strtolower($schema_type) . '_data', $data, $post->ID, $post);
        $generic->from_array($data);
        
        return [$generic];
    }
    
    public function get_priority(): int
    {
        return 100; // Low priority so specific providers run first
    }
    
    /**
     * Get default schema type for post type
     */
    private function get_default_schema_type(string $post_type): string
    {
        // This provider doesn't have defaults - it only handles what's explicitly set
        return apply_filters('wp_schema_framework_post_type_mapping', '', $post_type);
    }
    
    /**
     * Check if schema type supports date properties
     */
    private function supports_dates(string $schema_type): bool
    {
        $date_types = [
            'VideoObject', 'ImageObject', 'AudioObject', 'MediaObject',
            'CreativeWork', 'Book', 'Course', 'Episode', 'Event',
            'Movie', 'MusicRecording', 'Photograph', 'Recipe',
            'Review', 'SoftwareApplication', 'TVEpisode', 'TVSeries'
        ];
        
        return in_array($schema_type, $date_types, true);
    }
    
    /**
     * Check if schema type supports author property
     */
    private function supports_author(string $schema_type): bool
    {
        $author_types = [
            'CreativeWork', 'Article', 'BlogPosting', 'Book',
            'Course', 'HowTo', 'NewsArticle', 'Report',
            'Review', 'ScholarlyArticle', 'TechArticle'
        ];
        
        return in_array($schema_type, $author_types, true);
    }
    
    /**
     * Check if schema type supports publisher property
     */
    private function supports_publisher(string $schema_type): bool
    {
        return $this->supports_author($schema_type);
    }
}