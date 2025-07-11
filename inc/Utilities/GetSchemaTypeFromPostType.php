<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Utilities;

use BuiltNorth\Schema\Detectors\PostTypeDetector;

/**
 * Get Schema Type From Post Type
 * 
 * Utility to get appropriate schema type for a post with plugin override capability
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
        return PostTypeDetector::render($post_id);
    }
} 