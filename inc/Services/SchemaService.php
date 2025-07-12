<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Services;

use BuiltNorth\Schema\Core\SchemaService as CoreSchemaService;

/**
 * Schema Service (Legacy Facade)
 * 
 * Maintains backwards compatibility while delegating to new architecture.
 * 
 * @deprecated Use BuiltNorth\Schema\Core\SchemaService directly
 */
class SchemaService
{
    private CoreSchemaService $coreService;
    
    public function __construct()
    {
        $this->coreService = new CoreSchemaService();
    }

    /**
     * Generate schema from various content sources with hook support
     *
     * @param mixed $content Content to extract schema from
     * @param string $type Schema type (faq, article, product, etc.)
     * @param array $options Generation options
     * @return array JSON-LD schema data or empty array
     * @deprecated Use renderForContext() instead
     */
    public function render($content = '', string $type = 'faq', array $options = []): array
    {
        // For legacy compatibility, try to generate using new architecture
        return $this->coreService->render_for_context(null, $options);
    }
    
    /**
     * Generate schemas for current context
     *
     * @param array $options Generation options
     * @return array Array of schema data
     */
    public function renderForContext(array $options = []): array
    {
        return $this->coreService->render_for_context(null, $options);
    }
    
    /**
     * Generate schema for a specific post
     *
     * @param int $post_id Post ID
     * @param array $options Generation options
     * @return array JSON-LD schema data
     */
    public function renderForPost(int $post_id, array $options = []): array
    {
        return $this->coreService->render_for_post($post_id, $options);
    }
    
    /**
     * Generate schema for a specific block
     *
     * @param array $block Block data
     * @param array $options Generation options
     * @return array JSON-LD schema data
     */
    public function renderForBlock(array $block, array $options = []): array
    {
        return $this->coreService->render_for_block($block, $options);
    }
}