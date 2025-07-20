<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Providers;

use BuiltNorth\WPSchema\Contracts\SchemaProviderInterface;
use BuiltNorth\WPSchema\Graph\SchemaPiece;

/**
 * Article Provider
 * 
 * Provides Article schema for posts and custom post types.
 * 
 * @since 3.0.0
 */
class ArticleProvider implements SchemaProviderInterface
{
    public function can_provide(string $context): bool
    {
        // Handle homepage
        if ($context === 'home') {
            $seo_settings = get_option('polaris_seo_settings', []);
            $schema_type = $seo_settings['home']['default_schema_type'] ?? 'WebPage';
            $schema_type = apply_filters('wp_schema_homepage_type', $schema_type);
            return in_array($schema_type, ['Article', 'BlogPosting', 'NewsArticle'], true);
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
        
        return in_array($schema_type, ['Article', 'BlogPosting', 'NewsArticle'], true);
    }
    
    public function get_pieces(string $context): array
    {
        // Handle homepage
        if ($context === 'home') {
            $seo_settings = get_option('polaris_seo_settings', []);
            $schema_type = $seo_settings['home']['default_schema_type'] ?? 'WebPage';
            $schema_type = apply_filters('wp_schema_homepage_type', $schema_type);
            
            // Create article piece for homepage
            $article = new SchemaPiece('homepage', $schema_type);
            
            $article
                ->set('headline', get_bloginfo('name'))
                ->set('name', get_bloginfo('name'))
                ->set('url', home_url())
                ->add_reference('author', '#author')
                ->add_reference('publisher', '#organization');
            
            // Add description
            $description = get_bloginfo('description');
            if ($description) {
                $article->set('description', $description);
            }
            
            // If front page is a static page, use its content
            if (get_option('show_on_front') === 'page') {
                $page_id = get_option('page_on_front');
                if ($page_id) {
                    $post = get_post($page_id);
                    if ($post) {
                        $article->set('headline', $post->post_title);
                        $article->set('name', $post->post_title);
                        
                        if ($post->post_excerpt) {
                            $article->set('description', wp_strip_all_tags($post->post_excerpt));
                        }
                        
                        $article
                            ->set('datePublished', get_the_date('c', $page_id))
                            ->set('dateModified', get_the_modified_date('c', $page_id));
                        
                        if (has_post_thumbnail($page_id)) {
                            $image_url = get_the_post_thumbnail_url($page_id, 'full');
                            if ($image_url) {
                                $article->set('image', [
                                    '@type' => 'ImageObject',
                                    'url' => $image_url,
                                ]);
                            }
                        }
                    }
                }
            } else {
                // For blog homepage, use current date as modified date
                $article->set('datePublished', get_the_date('c'))
                     ->set('dateModified', current_time('c'));
            }
            
            // Allow filtering of homepage data
            $data = apply_filters('wp_schema_homepage_data', $article->to_array());
            $data = apply_filters('wp_schema_article_data', $data, 0, null);
            $article->from_array($data);
            
            return [$article];
        }
        
        // Handle regular posts
        $post = get_queried_object();
        if (!$post) {
            return [];
        }
        
        // Get schema type
        $default_type = $this->get_default_schema_type($post->post_type);
        $schema_type = apply_filters('wp_schema_post_type_override', $default_type, $post->ID, $post->post_type, $post);
        
        // Create article piece
        $article = new SchemaPiece('#article', $schema_type);
        
        $article
            ->set('headline', $post->post_title)
            ->set('name', $post->post_title)
            ->set('url', get_permalink($post->ID))
            ->set('datePublished', get_the_date('c', $post->ID))
            ->set('dateModified', get_the_modified_date('c', $post->ID))
            ->add_reference('author', '#author')
            ->add_reference('publisher', '#organization');
        
        // Add description from filter or excerpt
        $description = apply_filters('wp_schema_post_description', '', $post->ID, $post);
        if ($description) {
            $article->set('description', $description);
        } elseif ($post->post_excerpt) {
            $article->set('description', wp_strip_all_tags($post->post_excerpt));
        }
        
        // Add featured image
        if (has_post_thumbnail($post->ID)) {
            $image_url = get_the_post_thumbnail_url($post->ID, 'full');
            if ($image_url) {
                $article->set('image', [
                    '@type' => 'ImageObject',
                    'url' => $image_url,
                ]);
            }
        }
        
        return [$article];
    }
    
    public function get_priority(): int
    {
        return 20;
    }
    
    /**
     * Get default schema type for post type
     */
    private function get_default_schema_type(string $post_type): string
    {
        $mappings = [
            'post' => 'Article',
            'news' => 'NewsArticle',
            'blog_post' => 'BlogPosting',
        ];
        
        return apply_filters('wp_schema_post_type_mapping', $mappings[$post_type] ?? '', $post_type);
    }
}