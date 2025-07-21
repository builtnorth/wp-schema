<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Providers;

use BuiltNorth\WPSchema\Contracts\SchemaProviderInterface;
use BuiltNorth\WPSchema\Graph\SchemaPiece;

/**
 * Author Provider
 * 
 * Provides Person schema for post authors.
 * 
 * @since 3.0.0
 */
class AuthorProvider implements SchemaProviderInterface
{
    public function can_provide(string $context): bool
    {
        if ($context !== 'singular') {
            return false;
        }
        
        $post = get_queried_object();
        return $post && isset($post->post_author) && $post->post_author > 0;
    }
    
    public function get_pieces(string $context): array
    {
        $post = get_queried_object();
        if (!$post || !isset($post->post_author)) {
            return [];
        }
        
        $author_id = (int) $post->post_author;
        $author = get_userdata($author_id);
        
        if (!$author) {
            return [];
        }
        
        $person = new SchemaPiece('#author', 'Person');
        
        // Basic author data
        $person
            ->set('name', $author->display_name)
            ->set('url', get_author_posts_url($author_id));
        
        // Add description if available
        $description = get_user_meta($author_id, 'description', true);
        if ($description) {
            $person->set('description', wp_strip_all_tags($description));
        }
        
        // Allow filtering of author data
        $data = apply_filters('wp_schema_framework_author_data', $person->to_array(), $author_id, $context);
        $person->from_array($data);
        
        return [$person];
    }
    
    public function get_priority(): int
    {
        return 15; // Medium priority
    }
}