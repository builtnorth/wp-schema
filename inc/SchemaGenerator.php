<?php

namespace BuiltNorth\Schema;

use BuiltNorth\Schema\Generators\BaseGenerator;
use BuiltNorth\Schema\Services\SchemaService;
use BuiltNorth\Schema\Services\OutputService;
use BuiltNorth\Schema\Services\RestApiService;

// Bootstrap the new architecture
require_once __DIR__ . '/bootstrap.php';

/**
 * Schema Generator Utility
 * 
 * Provides easy access to schema generation functionality with enhanced hook support
 */
class SchemaGenerator
{
    /**
     * Service instances
     */
    private static ?SchemaService $schemaService = null;
    private static ?OutputService $outputService = null;
    private static ?RestApiService $restApiService = null;
    
    /**
     * Initialize the schema generator
     *
     * @return void
     */
    public static function init()
    {
        // Initialize default integrations
        if (class_exists('BuiltNorth\Schema\Defaults\DefaultIntegrations')) {
            \BuiltNorth\Schema\Defaults\DefaultIntegrations::init();
        }
        
        // Initialize services
        self::getSchemaService();
        self::getOutputService();
        self::getRestApiService();
        
        // Register REST API routes
        add_action('rest_api_init', [self::getRestApiService(), 'registerRoutes']);
        
        // Schema output is handled by the SEO plugin via SEOSchema.php
        // add_action('wp_head', [self::class, 'output_schema']);
        
        // Add schema to REST API responses
        add_action('rest_api_init', [self::getRestApiService(), 'addSchemaToRest']);
    }

    /**
     * Get SchemaService instance
     */
    private static function getSchemaService(): SchemaService
    {
        if (self::$schemaService === null) {
            self::$schemaService = new SchemaService();
        }
        return self::$schemaService;
    }

    /**
     * Get OutputService instance
     */
    private static function getOutputService(): OutputService
    {
        if (self::$outputService === null) {
            self::$outputService = new OutputService();
        }
        return self::$outputService;
    }

    /**
     * Get RestApiService instance
     */
    private static function getRestApiService(): RestApiService
    {
        if (self::$restApiService === null) {
            self::$restApiService = new RestApiService();
        }
        return self::$restApiService;
    }

    /**
     * Generate schema from various content sources with hook support
     *
     * @param mixed $content Content to extract schema from
     * @param string $type Schema type (faq, article, product, etc.)
     * @param array $options Generation options
     * @return array JSON-LD schema data or empty array
     */
    public static function render($content = '', $type = 'faq', $options = [])
    {
        return self::getSchemaService()->render($content, $type, $options);
    }

    /**
     * Register REST API routes for schema generation
     *
     * @return void
     */
    public static function register_rest_routes()
    {
        self::getRestApiService()->registerRoutes();
    }

    /**
     * REST API callback for schema generation
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public static function rest_generate_schema($request)
    {
        return self::getRestApiService()->generateSchema($request);
    }

    /**
     * REST API callback for getting post schema
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public static function rest_get_post_schema($request)
    {
        return self::getRestApiService()->getPostSchema($request);
    }

    /**
     * Add schema to REST API responses
     *
     * @return void
     */
    public static function add_schema_to_rest()
    {
        self::getRestApiService()->addSchemaToRest();
    }

    /**
     * Add schema to post REST API response
     *
     * @param WP_REST_Response $response Response object
     * @param WP_Post $post Post object
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Modified response
     */
    public static function add_schema_to_post_response($response, $post, $request)
    {
        return self::getRestApiService()->addSchemaToPostResponse($response, $post, $request);
    }

    /**
     * Output schema to head
     *
     * @return void
     */
    public static function output_schema()
    {
        self::getOutputService()->outputSchema();
    }

    /**
     * Generate schema for a specific post with enhanced hook support
     *
     * @param int $post_id Post ID
     * @param array $options Generation options
     * @return array JSON-LD schema data
     */
    public static function render_for_post($post_id, $options = [])
    {
        return self::getSchemaService()->renderForPost($post_id, $options);
    }

    /**
     * Generate schema for a specific block
     *
     * @param array $block Block data
     * @param array $options Generation options
     * @return array JSON-LD schema data
     */
    public static function render_for_block($block, $options = [])
    {
        return self::getSchemaService()->renderForBlock($block, $options);
    }



    /**
     * Collect schemas from all blocks in a post
     *
     * @param int $post_id Post ID
     * @param array $options Generation options
     * @return array Array of schema data
     */
    public static function collect_schemas_from_blocks($post_id, $options = [])
    {
        $post = get_post($post_id);
        if (!$post) {
            return [];
        }

        // Parse blocks from post content
        $blocks = parse_blocks($post->post_content);
        if (empty($blocks)) {
            return [];
        }

        $schemas = [];
        
        foreach ($blocks as $block) {
            // Skip empty blocks
            if (empty($block['blockName'])) {
                continue;
            }

            // Check if block should be processed
            $should_process = apply_filters('wp_schema_should_process_block', true, $block, $post_id, $options);
            if (!$should_process) {
                continue;
            }

            $schema = self::render_for_block($block, $options);
            if (!empty($schema)) {
                $schemas[] = $schema;
            }
        }

        // Allow plugins/themes to modify collected schemas
        $schemas = apply_filters('wp_schema_collected_block_schemas', $schemas, $post_id, $options);

        return $schemas;
    }

    /**
     * Output schema as script tag
     *
     * @param array $schema Schema data
     * @return string HTML script tag
     */
    public static function output_schema_script($schema)
    {
        return self::getOutputService()->outputSchemaScript($schema);
    }

    /**
     * Output multiple schemas as script tags
     *
     * @param array $schemas Array of schema data
     * @return void
     */
    public static function output_schemas($schemas)
    {
        self::getOutputService()->outputSchemas($schemas);
    }

    /**
     * Detect patterns in content
     *
     * @param string $content Content to analyze
     * @param string $type Schema type
     * @return array Detected patterns
     */
    public static function detect_patterns($content, $type)
    {
        // This is a placeholder for pattern detection
        // In the hook-based system, patterns are provided by integrations
        return [];
    }

    /**
     * Get best pattern for schema type
     *
     * @param string $schema_type Schema type
     * @param array $detected_patterns Detected patterns
     * @return string Best pattern
     */
    public static function get_best_pattern($schema_type, $detected_patterns)
    {
        return '';
    }



    /**
     * Quick organization schema
     *
     * @param array $data Organization data
     * @return array JSON-LD schema data
     */
    public static function organization($data)
    {
        return self::render($data, 'organization');
    }

    /**
     * Quick local business schema
     *
     * @param array $data Business data
     * @return array JSON-LD schema data
     */
    public static function local_business($data)
    {
        return self::render($data, 'local_business');
    }

    /**
     * Quick website schema
     *
     * @param array $data Website data
     * @return array JSON-LD schema data
     */
    public static function website($data)
    {
        return self::render($data, 'website');
    }

    /**
     * Quick article schema
     *
     * @param mixed $content Article content
     * @return array JSON-LD schema data
     */
    public static function article($content)
    {
        return self::render($content, 'article');
    }

    /**
     * Generate schemas for current context
     *
     * @param array $options Generation options
     * @return array Array of schema data
     */
    public static function render_for_context($options = [])
    {
        return self::getSchemaService()->renderForContext($options);
    }



    /**
     * Quick FAQ schema
     *
     * @param mixed $content FAQ content
     * @return array JSON-LD schema data
     */
    public static function faq($content)
    {
        return self::render($content, 'faq');
    }

    /**
     * Quick product schema
     *
     * @param array $data Product data
     * @return array JSON-LD schema data
     */
    public static function product($data)
    {
        return self::render($data, 'product');
    }

    /**
     * Quick person schema
     *
     * @param array $data Person data
     * @return array JSON-LD schema data
     */
    public static function person($data)
    {
        return self::render($data, 'person');
    }
}