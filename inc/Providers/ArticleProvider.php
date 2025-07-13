<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Providers;

use BuiltNorth\Schema\Contracts\SchemaProviderInterface;
use BuiltNorth\Schema\Graph\SchemaPiece;

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
        
        return apply_filters('wp_schema_post_type_mapping', $mappings[$post_type] ?? 'Article', $post_type);
    }
}