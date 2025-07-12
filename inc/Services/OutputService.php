<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Services;

/**
 * Output Service
 * 
 * Handles schema output and rendering logic extracted from SchemaGenerator
 */
class OutputService
{
    /**
     * Output schema to head
     *
     * @return void
     */
    public function outputSchema(): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('OutputService::outputSchema() called - is_singular: ' . (is_singular() ? 'true' : 'false'));
        }
        
        // Get current context
        $context = $this->get_current_context();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('OutputService::outputSchema() - context: ' . $context);
        }
        
        // Use SchemaService to generate schemas
        $schemaService = new SchemaService();
        $schemas = $schemaService->renderForContext([]);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('OutputService::outputSchema() - schemas from integrations: ' . count($schemas));
        }
        
        // If no integrations provided schemas, generate basic ones
        if (empty($schemas)) {
            $schemas = $this->generate_basic_context_schemas($context, []);
        }
        
        // Remove duplicates and empty schemas
        $schemas = array_filter($schemas);
        
        // Ensure we only have one organization/business schema
        $org_schemas = [];
        $non_org_schemas = [];
        
        foreach ($schemas as $schema) {
            if (isset($schema['@type']) && in_array($schema['@type'], ['Organization', 'LocalBusiness', 'HomeAndConstructionBusiness'])) {
                $org_schemas[] = $schema;
            } else {
                $non_org_schemas[] = $schema;
            }
        }
        
        // Keep only the first organization schema (usually the most complete one from PolarisCoreIntegration)
        if (!empty($org_schemas)) {
            $schemas = array_merge([$org_schemas[0]], $non_org_schemas);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('OutputService::outputSchema() - kept organization schema: ' . print_r($org_schemas[0], true));
            }
        } else {
            $schemas = $non_org_schemas;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('OutputService::outputSchema() - final schemas count: ' . count($schemas));
        }
        
        if (!empty($schemas)) {
            $this->outputSchemas($schemas);
        }
    }

    /**
     * Output schema as script tag
     *
     * @param array $schema Schema data
     * @return string HTML script tag
     */
    public function outputSchemaScript(array $schema): string
    {
        if (empty($schema)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('OutputService::outputSchemaScript() - empty schema provided');
            }
            return '';
        }

        $json = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('OutputService::outputSchemaScript() - JSON encoding error: ' . json_last_error_msg());
            }
            return '';
        }
        
        $html = '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n";
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('OutputService::outputSchemaScript() - generated HTML: ' . $html);
        }
        
        return $html;
    }

    /**
     * Output multiple schemas as script tags
     *
     * @param array $schemas Array of schema data
     * @return void
     */
    public function outputSchemas(array $schemas): void
    {
        if (empty($schemas) || !is_array($schemas)) {
            return;
        }

        foreach ($schemas as $schema) {
            if (!empty($schema)) {
                echo $this->outputSchemaScript($schema);
            }
        }
    }

    /**
     * Add schema to post REST API response
     *
     * @param \WP_REST_Response $response Response object
     * @param \WP_Post $post Post object
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response Modified response
     */
    public function addToRestResponse($response, $post, $request)
    {
        $schemaService = new SchemaService();
        $schema = $schemaService->renderForPost($post->ID);
        $response->data['schema'] = $schema;
        
        return $response;
    }

    /**
     * Get current context
     *
     * @return string Context type
     */
    private function get_current_context(): string
    {
        if (is_front_page()) {
            return 'home';
        } elseif (is_singular()) {
            return 'singular';
        } elseif (is_tax() || is_category() || is_tag()) {
            return 'taxonomy';
        } elseif (is_archive()) {
            return 'archive';
        } elseif (is_search()) {
            return 'search';
        } elseif (is_404()) {
            return '404';
        }
        
        return 'home';
    }

    /**
     * Generate basic context schemas without framework dependencies
     *
     * @param string $context Current context
     * @param array $options Generation options
     * @return array Array of schema data
     */
    private function generate_basic_context_schemas(string $context, array $options = []): array
    {
        $schemas = [];
        $entity = $this->get_current_entity();
        
        // Basic organization schema using WordPress site info
        $org_schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'url' => home_url('/'),
        ];
        
        // Add description if available
        $description = get_bloginfo('description');
        if (!empty($description)) {
            $org_schema['description'] = $description;
        }
        
        $schemas[] = $org_schema;
        
        // Add context-specific schemas
        switch ($context) {
            case 'home':
                // WebSite schema
                $schemas[] = [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => $options['site_name'] ?? get_bloginfo('name'),
                    'url' => $options['canonical_url'] ?? home_url('/'),
                    'description' => $options['description'] ?? get_bloginfo('description'),
                ];
                
                // WebPage schema
                $schemas[] = [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebPage',
                    'name' => $options['title'] ?? get_bloginfo('name'),
                    'url' => $options['canonical_url'] ?? home_url('/'),
                    'description' => $options['description'] ?? get_bloginfo('description'),
                ];
                break;
                
            case 'singular':
                // Basic WebPage schema only - avoid entity-specific schemas that might cause recursion
                $schemas[] = [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebPage',
                    'name' => $options['title'] ?? ($entity ? get_the_title($entity) : get_bloginfo('name')),
                    'url' => $options['canonical_url'] ?? ($entity ? get_permalink($entity) : home_url('/')),
                    'description' => $options['description'] ?? ($entity ? get_the_excerpt($entity) : get_bloginfo('description')),
                ];
                break;
                
            case 'taxonomy':
                // Basic WebPage schema only
                $schemas[] = [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebPage',
                    'name' => $options['title'] ?? ($entity ? get_the_title($entity) : get_bloginfo('name')),
                    'url' => $options['canonical_url'] ?? ($entity ? get_permalink($entity) : home_url('/')),
                    'description' => $options['description'] ?? ($entity ? get_the_excerpt($entity) : get_bloginfo('description')),
                ];
                break;
                
            case 'archive':
                // Basic WebPage schema only  
                $schemas[] = [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebPage',
                    'name' => $options['title'] ?? get_the_archive_title(),
                    'url' => $options['canonical_url'] ?? get_pagenum_link(),
                    'description' => $options['description'] ?? get_the_archive_description(),
                ];
                break;
        }

        // Skip navigation schema to avoid recursion - let new architecture handle it
        
        return $schemas;
    }

    /**
     * Get current entity
     *
     * @return mixed Entity object or null
     */
    private function get_current_entity()
    {
        if (is_singular()) {
            return get_post();
        } elseif (is_tax() || is_category() || is_tag()) {
            return get_queried_object();
        }
        
        return null;
    }
} 