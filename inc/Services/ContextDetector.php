<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Services;

/**
 * Context Detector
 * 
 * Detects the current WordPress context for schema generation.
 * 
 * @since 3.0.0
 */
class ContextDetector
{
    /**
     * Get current context
     */
    public function get_current_context(): string
    {
        if (is_front_page()) {
            return 'home';
        }
        
        if (is_singular()) {
            return 'singular';
        }
        
        if (is_archive() || is_home()) {
            return 'archive';
        }
        
        if (is_search()) {
            return 'search';
        }
        
        if (is_404()) {
            return '404';
        }
        
        return 'unknown';
    }
    
    /**
     * Check if context should generate schema
     */
    public function should_generate_schema(string $context): bool
    {
        // Skip admin, feeds, etc.
        if (is_admin() || is_feed() || is_robots() || is_trackback()) {
            return false;
        }
        
        // Skip 404 and unknown contexts
        return !in_array($context, ['404', 'unknown'], true);
    }
}