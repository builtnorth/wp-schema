<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Services;

use BuiltNorth\WPSchema\Graph\SchemaGraph;

/**
 * Output Service
 * 
 * Handles JSON-LD output to page head.
 * 
 * @since 3.0.0
 */
class OutputService
{
    private GraphBuilder $graph_builder;
    private ContextDetector $context_detector;
    
    public function __construct(GraphBuilder $graph_builder, ContextDetector $context_detector)
    {
        $this->graph_builder = $graph_builder;
        $this->context_detector = $context_detector;
    }
    
    /**
     * Initialize output hooks
     */
    public function init(): void
    {
        add_action('wp_head', [$this, 'output_schema'], 3);
    }
    
    /**
     * Output schema to page head
     */
    public function output_schema(): void
    {
        $context = $this->context_detector->get_current_context();
        
        if (!$this->context_detector->should_generate_schema($context)) {
            return;
        }
        
        // Fire action before output
        do_action('wp_schema_framework_before_output', $context);
        
        $graph = $this->graph_builder->build_for_context($context);
        
        if ($graph->is_empty()) {
            return;
        }
        
        $this->output_graph($graph);
        
        // Fire action after output
        do_action('wp_schema_framework_after_output', $context, $graph);
    }
    
    /**
     * The `@context` + `@graph` array exactly as it is printed to the head,
     * after the wp_schema_framework_graph filter. Shared with the WP-CLI
     * command so `wp schema dump` and the live page can never disagree.
     *
     * @return array<string, mixed>
     */
    public function get_graph_data(SchemaGraph $graph): array
    {
        $graph_data = [
            '@context' => 'https://schema.org',
            '@graph' => []
        ];

        foreach ($graph->get_pieces() as $piece) {
            $piece_data = $piece->to_array();
            // Remove individual @context from pieces
            unset($piece_data['@context']);
            $graph_data['@graph'][] = $piece_data;
        }

        // Allow filtering of complete graph before output
        return (array) apply_filters('wp_schema_framework_graph', $graph_data);
    }

    /**
     * Output graph as JSON-LD scripts
     */
    private function output_graph(SchemaGraph $graph): void
    {
        $graph_data = $this->get_graph_data($graph);

        if (empty($graph_data['@graph'])) {
            return;
        }

        // JSON_HEX_TAG prevents </script> breakout inside <script type="application/ld+json">
        $encoded = json_encode($graph_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        if ($encoded === false) {
            return;
        }
        
        // Allow filtering of JSON string — but never echo a value that can close the script tag.
        $json = apply_filters('wp_schema_framework_json_output', $encoded, $graph_data);

        if (!is_string($json) || $json === '' || stripos($json, '</script') !== false) {
            $json = $encoded;
        }
        
        echo '<script type="application/ld+json">' . $json . '</script>' . PHP_EOL;
    }
}