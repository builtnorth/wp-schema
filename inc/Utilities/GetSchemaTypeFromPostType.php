<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Utilities;

/**
 * Get Schema Type From Post Type
 * 
 * Utility to get appropriate schema type for a post using the hook-based system
 * 
 * @package BuiltNorth\Utility
 * @since 1.0.0
 */
class GetSchemaTypeFromPostType
{
    /**
     * Render the schema type for a post
     *
     * @param int $post_id Post ID
     * @return string Schema type
     */
    public static function render($post_id)
    {
        // Use the hook-based system to get schema type
        $schema_type = apply_filters('wp_schema_type_for_post', null, $post_id, []);
        
        if ($schema_type) {
            return $schema_type;
        }

        // Fallback to basic post type mapping
        $post_type = get_post_type($post_id);
        
        $post_type_schema_map = [
            'post' => 'Article',
            'page' => 'WebPage',
            'attachment' => 'ImageObject',
            'product' => 'Product',
            'event' => 'Event',
            'recipe' => 'Recipe',
            'restaurant' => 'Restaurant',
            'business' => 'LocalBusiness',
            'service' => 'Service',
            'review' => 'Review',
            'book' => 'Book',
            'movie' => 'Movie',
            'music' => 'MusicRecording',
            'faq' => 'FAQPage',
            'person' => 'Person',
            'organization' => 'Organization',
            'news' => 'NewsArticle',
            'job' => 'JobPosting',
            'course' => 'Course',
            'software' => 'SoftwareApplication',
            'game' => 'Game'
        ];

        return $post_type_schema_map[$post_type] ?? 'Article';
    }
} 