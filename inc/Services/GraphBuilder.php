<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Services;

use BuiltNorth\WPSchema\Graph\SchemaGraph;
use BuiltNorth\WPSchema\Graph\SchemaPiece;

/**
 * Graph Builder Service
 * 
 * Builds schema graphs from providers with clean filtering hooks.
 * 
 * @since 3.0.0
 */
class GraphBuilder
{
    private ProviderRegistry $registry;
    
    public function __construct(ProviderRegistry $registry)
    {
        $this->registry = $registry;
    }
    
    /**
     * Build graph for context
     */
    public function build_for_context(string $context): SchemaGraph
    {
        $graph = new SchemaGraph();
        
        // Get pieces from all providers
        $providers = $this->registry->get_providers_for_context($context);
        
        foreach ($providers as $provider) {
            $pieces = $provider->get_pieces($context);
            $graph->add_pieces($pieces);
        }
        
        // Apply filters for extensibility
        $graph->apply_filters($context);

        // Surface dangling references as error_log entries during development
        if (defined('WP_DEBUG') && WP_DEBUG) {
            foreach ($graph->validate_references() as $error) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('[WP Schema] ' . $error);
            }
        }

        return $graph;
    }
}