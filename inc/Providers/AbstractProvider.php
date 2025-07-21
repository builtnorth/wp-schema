<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Providers;

use BuiltNorth\WPSchema\Contracts\SchemaProviderInterface;

/**
 * Abstract Provider
 * 
 * Base class for schema providers with common functionality.
 * 
 * @since 3.0.0
 */
abstract class AbstractProvider implements SchemaProviderInterface
{
    /**
     * Apply filters to piece data for extensibility
     */
    protected function apply_piece_filters(array $data, string $filter_name, ...$args): array
    {
        return apply_filters($filter_name, $data, ...$args);
    }
    
    /**
     * Get current queried object safely
     */
    protected function get_queried_object()
    {
        global $wp_query;
        return $wp_query->get_queried_object();
    }
    
    /**
     * Check if we're in admin or other contexts where schema shouldn't run
     */
    protected function should_skip_context(): bool
    {
        return is_admin() || is_feed() || is_robots() || is_trackback();
    }
}