<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Core;

/**
 * Output Service
 * 
 * Handles outputting schema JSON-LD to WordPress pages.
 * Simple, focused service for the new architecture.
 * 
 * @since 3.0.0
 */
class OutputService
{
    private SchemaService $schemaService;
    
    public function __construct()
    {
        $this->schemaService = new SchemaService();
    }
    
    /**
     * Initialize output hooks
     */
    public function init(): void
    {
        add_action('wp_head', [$this, 'output_schema'], 2);
    }
    
    /**
     * Output schema to page head
     */
    public function output_schema(): void
    {
        // Skip on admin pages, feeds, etc.
        if (is_admin() || is_feed() || is_robots() || is_trackback()) {
            return;
        }
        
        // Generate schema for current context
        $schemas = $this->schemaService->render_for_context();
        
        if (empty($schemas)) {
            return;
        }
        
        // Output each schema as a separate script tag
        foreach ($schemas as $schema) {
            $this->output_schema_script($schema);
        }
    }
    
    /**
     * Output a single schema as JSON-LD script tag
     */
    private function output_schema_script(array $schema): void
    {
        if (empty($schema)) {
            return;
        }
        
        $json = $this->encode_schema($schema);
        if (!$json) {
            return;
        }
        
        echo '<script type="application/ld+json">' . $json . '</script>' . PHP_EOL;
    }
    
    /**
     * Encode schema as JSON with proper formatting
     */
    private function encode_schema(array $schema): ?string
    {
        $json = json_encode(
            $schema, 
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            if (WP_DEBUG) {
                error_log('WP Schema JSON encoding error: ' . json_last_error_msg());
            }
            return null;
        }
        
        return $json;
    }
    
    /**
     * Get schema for specific context (for testing/debugging)
     */
    public function get_schema_for_context(?string $context = null, array $options = []): array
    {
        return $this->schemaService->render_for_context($context, $options);
    }
    
    /**
     * Output schema for specific context (for manual output)
     */
    public function output_schema_for_context(?string $context = null, array $options = []): void
    {
        $schemas = $this->get_schema_for_context($context, $options);
        
        foreach ($schemas as $schema) {
            $this->output_schema_script($schema);
        }
    }
}