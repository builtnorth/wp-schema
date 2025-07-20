<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Providers;

use BuiltNorth\WPSchema\Contracts\SchemaProviderInterface;
use BuiltNorth\WPSchema\Graph\SchemaPiece;

/**
 * WebPage Provider
 * 
 * Provides WebPage schema for pages and posts with WebPage schema type.
 * 
 * @since 3.0.0
 */
class WebPageProvider implements SchemaProviderInterface
{
    public function can_provide(string $context): bool
    {
        // Handle homepage
        if ($context === 'home') {
            $seo_settings = get_option('polaris_seo_settings', []);
            $schema_type = $seo_settings['home']['default_schema_type'] ?? 'WebPage';
            $schema_type = apply_filters('wp_schema_homepage_type', $schema_type);
            return $schema_type === 'WebPage';
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
        $schema_type = apply_filters('wp_schema_post_type_override', $default_type, $post->ID, $post->post_type, $post);
        
        // Handle WebPage and generic page types
        return $schema_type === 'WebPage';
    }
    
    public function get_pieces(string $context): array
    {
        // Handle homepage
        if ($context === 'home') {
            $webpage = new SchemaPiece('homepage', 'WebPage');
            
            $webpage
                ->set('name', get_bloginfo('name'))
                ->set('headline', get_bloginfo('name'))
                ->set('url', home_url())
                ->add_reference('publisher', '#organization')
                ->add_reference('isPartOf', '#website');
            
            // Add description
            $description = get_bloginfo('description');
            if ($description) {
                $webpage->set('description', $description);
            }
            
            // If front page is a static page
            if (get_option('show_on_front') === 'page') {
                $page_id = get_option('page_on_front');
                if ($page_id) {
                    $post = get_post($page_id);
                    if ($post) {
                        $webpage->set('headline', $post->post_title);
                        
                        if ($post->post_excerpt) {
                            $webpage->set('description', wp_strip_all_tags($post->post_excerpt));
                        }
                        
                        $webpage
                            ->set('datePublished', get_the_date('c', $page_id))
                            ->set('dateModified', get_the_modified_date('c', $page_id));
                        
                        if (has_post_thumbnail($page_id)) {
                            $image_url = get_the_post_thumbnail_url($page_id, 'full');
                            if ($image_url) {
                                $webpage->set('image', [
                                    '@type' => 'ImageObject',
                                    'url' => $image_url,
                                ]);
                            }
                        }
                    }
                }
            }
            
            // Add breadcrumb reference
            $webpage->add_reference('breadcrumb', '#breadcrumb');
            
            // Allow filtering of homepage data
            $data = apply_filters('wp_schema_homepage_data', $webpage->to_array());
            $data = apply_filters('wp_schema_webpage_data', $data, 0, null);
            $webpage->from_array($data);
            
            return [$webpage];
        }
        
        // Handle regular pages
        $post = get_queried_object();
        if (!$post) {
            return [];
        }
        // Get schema type
        $default_type = $this->get_default_schema_type($post->post_type);
        $schema_type = apply_filters('wp_schema_post_type_override', $default_type, $post->ID, $post->post_type, $post);
        
        // Create webpage piece with unique ID based on post type and ID
        $piece_id = $post->post_type . '-' . $post->ID;
        $webpage = new SchemaPiece($piece_id, $schema_type);
        
        $webpage
            ->set('headline', $post->post_title)
            ->set('name', $post->post_title)
            ->set('url', get_permalink($post->ID))
            ->set('datePublished', get_the_date('c', $post->ID))
            ->set('dateModified', get_the_modified_date('c', $post->ID))
            ->add_reference('author', '#author')
            ->add_reference('publisher', '#organization')
            ->add_reference('isPartOf', '#website');
        
        // Add description from filter or excerpt
        $description = apply_filters('wp_schema_post_description', '', $post->ID, $post);
        if ($description) {
            $webpage->set('description', $description);
        } elseif ($post->post_excerpt) {
            $webpage->set('description', wp_strip_all_tags($post->post_excerpt));
        }
        
        // Add featured image
        if (has_post_thumbnail($post->ID)) {
            $image_url = get_the_post_thumbnail_url($post->ID, 'full');
            if ($image_url) {
                $webpage->set('image', [
                    '@type' => 'ImageObject',
                    'url' => $image_url,
                ]);
            }
        }
        
        // Add breadcrumb reference
        $webpage->add_reference('breadcrumb', '#breadcrumb');
        
        // Allow filtering of webpage data
        $data = apply_filters('wp_schema_webpage_data', $webpage->to_array(), $post->ID, $post);
        $webpage->from_array($data);
        
        return [$webpage];
    }
    
    public function get_priority(): int
    {
        return 20; // Same priority as ArticleProvider
    }
    
    /**
     * Get default schema type for post type
     */
    private function get_default_schema_type(string $post_type): string
    {
        $mappings = [
            'page' => 'WebPage',
        ];
        
        return apply_filters('wp_schema_post_type_mapping', $mappings[$post_type] ?? '', $post_type);
    }
}