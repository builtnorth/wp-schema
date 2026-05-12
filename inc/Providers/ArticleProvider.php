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
            $schema_type = apply_filters('wp_schema_framework_homepage_type', $schema_type);
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
        $schema_type = apply_filters('wp_schema_framework_post_type_override', $default_type, $post->ID, $post->post_type, $post);
        
        return in_array($schema_type, ['Article', 'BlogPosting', 'NewsArticle'], true);
    }
    
    public function get_pieces(string $context): array
    {
        // Handle homepage
        if ($context === 'home') {
            $seo_settings = get_option('polaris_seo_settings', []);
            $schema_type = $seo_settings['home']['default_schema_type'] ?? 'WebPage';
            $schema_type = apply_filters('wp_schema_framework_homepage_type', $schema_type);
            
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
            $data = apply_filters('wp_schema_framework_homepage_data', $article->to_array());
            $data = apply_filters('wp_schema_framework_article_data', $data, 0, null);
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
        $schema_type = apply_filters('wp_schema_framework_post_type_override', $default_type, $post->ID, $post->post_type, $post);

        $article = new SchemaPiece('#article', $schema_type);

        $article
            ->set('headline', $post->post_title)
            ->set('name', $post->post_title)
            ->set('url', get_permalink($post->ID))
            ->set('datePublished', get_the_date('c', $post->ID))
            ->set('dateModified', get_the_modified_date('c', $post->ID))
            ->set('mainEntityOfPage', ['@type' => 'WebPage', '@id' => get_permalink($post->ID)])
            ->set('inLanguage', get_bloginfo('language'))
            ->add_reference('author', '#author')
            ->add_reference('publisher', '#organization')
            ->add_reference('isPartOf', '#website');

        // Description from filter or excerpt
        $description = apply_filters('wp_schema_framework_post_description', '', $post->ID, $post);
        if ($description) {
            $article->set('description', $description);
        } elseif ($post->post_excerpt) {
            $article->set('description', wp_strip_all_tags($post->post_excerpt));
        }

        // Featured image with dimensions
        $thumb_id = get_post_thumbnail_id($post->ID);
        if ($thumb_id) {
            $image_url = wp_get_attachment_image_url($thumb_id, 'full');
            if ($image_url) {
                $image_data = ['@type' => 'ImageObject', 'url' => $image_url];
                $metadata = wp_get_attachment_metadata($thumb_id);
                if (!empty($metadata['width'])) {
                    $image_data['width'] = $metadata['width'];
                }
                if (!empty($metadata['height'])) {
                    $image_data['height'] = $metadata['height'];
                }
                $article->set('image', $image_data);
            }
        }

        // Keywords from post tags
        $tags = get_the_tags($post->ID);
        if ($tags) {
            $article->set('keywords', implode(', ', array_map(fn($tag) => $tag->name, $tags)));
        }

        // Article section from primary category
        $categories = get_the_category($post->ID);
        if ($categories) {
            $article->set('articleSection', $categories[0]->name);
        }

        // Word count
        $word_count = str_word_count(wp_strip_all_tags($post->post_content));
        if ($word_count > 0) {
            $article->set('wordCount', $word_count);
        }

        // Allow filtering — also gives polaris-seo a chance to add image fallback etc.
        $data = apply_filters('wp_schema_framework_article_data', $article->to_array(), $post->ID, $post);
        $article->from_array($data);

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
        
        return apply_filters('wp_schema_framework_post_type_mapping', $mappings[$post_type] ?? '', $post_type);
    }
}