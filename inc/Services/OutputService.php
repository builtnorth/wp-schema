<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Services;

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
        
        $graph = $this->graph_builder->build_for_context($context);
        
        if ($graph->is_empty()) {
            return;
        }
        
        $this->output_graph($graph);
    }
    
    /**
     * Output graph as JSON-LD scripts
     */
    private function output_graph($graph): void
    {
        $pieces = $graph->get_pieces();
        
        if (empty($pieces)) {
            return;
        }
        
        // Build @graph array with all pieces
        $graph_data = [
            '@context' => 'https://schema.org',
            '@graph' => []
        ];
        
        foreach ($pieces as $piece) {
            $piece_data = $piece->to_array();
            // Remove individual @context from pieces
            unset($piece_data['@context']);
            $graph_data['@graph'][] = $piece_data;
        }
        
        $json = json_encode($graph_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        if ($json) {
            echo '<script type="application/ld+json">' . $json . '</script>' . PHP_EOL;
        }
    }
}