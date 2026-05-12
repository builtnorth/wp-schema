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
        $context = 'unknown';

        if (is_front_page()) {
            $context = 'home';
        } elseif (is_attachment()) {
            $context = 'attachment';
        } elseif (is_singular()) {
            $context = 'singular';
        } elseif (is_search()) {
            $context = 'search';
        } elseif (is_archive() || is_home()) {
            $context = 'archive';
        } elseif (is_404()) {
            $context = '404';
        }

        return apply_filters('wp_schema_framework_context', $context);
    }
    
    /**
     * Check if context should generate schema
     */
    public function should_generate_schema(string $context): bool
    {
        // Allow other plugins to disable schema output
        if (!apply_filters('wp_schema_framework_output_enabled', true)) {
            return false;
        }
        
        // Skip admin, feeds, etc.
        if (is_admin() || is_feed() || is_robots() || is_trackback()) {
            return false;
        }
        
        // Skip 404 and unknown contexts
        return !in_array($context, ['404', 'unknown'], true);
    }
}
