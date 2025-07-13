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
        foreach ($graph->get_pieces() as $piece) {
            $this->output_piece($piece);
        }
    }
    
    /**
     * Output single piece as JSON-LD
     */
    private function output_piece($piece): void
    {
        $data = $piece->to_array();
        
        // Add schema.org context
        if (!isset($data['@context'])) {
            $data['@context'] = 'https://schema.org';
        }
        
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        if ($json) {
            echo '<script type="application/ld+json">' . $json . '</script>' . PHP_EOL;
        }
    }
}