<?php

namespace BuiltNorth\Schema;

use BuiltNorth\Schema\Generators\BaseGenerator;

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
     * Recursion guard for schema generation
     * @var bool
     */
    private static $is_generating = false;
    
    /**
     * Global recursion counter to detect deep loops
     * @var int
     */
    private static $recursion_depth = 0;
    
    /**
     * Maximum allowed recursion depth
     * @var int
     */
    private static $max_recursion_depth = 3;
    
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
        
        // Register REST API routes
        add_action('rest_api_init', [self::class, 'register_rest_routes']);
        
        // Schema output is handled by the SEO plugin via SEOSchema.php
        // add_action('wp_head', [self::class, 'output_schema']);
        
        // Add schema to REST API responses
        add_action('rest_api_init', [self::class, 'add_schema_to_rest']);
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
        if (empty($content)) {
            return [];
        }

        // Allow plugins/themes to override the entire schema generation process
        $schema_override = apply_filters('wp_schema_override_generation', null, $content, $type, $options);
        if ($schema_override !== null) {
            return $schema_override;
        }

        // Allow plugins/themes to modify the content before processing
        $content = apply_filters('wp_schema_content_before_processing', $content, $type, $options);

        // Allow plugins/themes to override the schema type
        $type = apply_filters('wp_schema_type_override', $type, $content, $options);

        // If content is already an array, use it directly but still apply final schema filter
        if (is_array($content)) {
            $schema = self::generate_schema_by_type($type, $content, $options);
            
            // Allow plugins/themes to modify final schema
            $schema = apply_filters('wp_schema_final_schema', $schema, $content, $type, $options);
            
            return $schema;
        }

        // Get data from hooks (primary method)
        $data = apply_filters('wp_schema_extracted_data', null, $content, $type, $options);
        
        if ($data === null) {
            // No hooks provided data, return empty schema
            return [];
        }
        
        // Generate schema based on type
        $schema = self::generate_schema_by_type($type, $data, $options);
        
        // Allow plugins/themes to modify final schema
        $schema = apply_filters('wp_schema_final_schema', $schema, $content, $type, $options);
        
        return $schema;
    }

    /**
     * Register REST API routes for schema generation
     *
     * @return void
     */
    public static function register_rest_routes()
    {
        register_rest_route('wp-schema/v1', '/generate', [
            'methods' => 'POST',
            'callback' => [self::class, 'rest_generate_schema'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);

        register_rest_route('wp-schema/v1', '/post/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [self::class, 'rest_get_post_schema'],
            'permission_callback' => '__return_true'
        ]);
    }

    /**
     * REST API callback for schema generation
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public static function rest_generate_schema($request)
    {
        $content = $request->get_param('content');
        $type = $request->get_param('type') ?: 'Article';
        $options = $request->get_param('options') ?: [];

        $schema = self::render($content, $type, $options);

        return new \WP_REST_Response($schema, 200);
    }

    /**
     * REST API callback for getting post schema
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public static function rest_get_post_schema($request)
    {
        $post_id = $request->get_param('id');
        $options = $request->get_param('options') ?: [];

        $schema = self::render_for_post($post_id, $options);

        return new \WP_REST_Response($schema, 200);
    }

    /**
     * Add schema to REST API responses
     *
     * @return void
     */
    public static function add_schema_to_rest()
    {
        add_action('rest_prepare_post', [self::class, 'add_schema_to_post_response'], 10, 3);
        add_action('rest_prepare_page', [self::class, 'add_schema_to_post_response'], 10, 3);
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
        $schema = self::render_for_post($post->ID);
        $response->data['schema'] = $schema;
        
        return $response;
    }

    /**
     * Output schema to head
     *
     * @return void
     */
    public static function output_schema()
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SchemaGenerator::output_schema() called - is_singular: ' . (is_singular() ? 'true' : 'false'));
        }
        
        // Get current context
        $context = self::get_current_context();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SchemaGenerator::output_schema() - context: ' . $context);
        }
        
        // Allow integrations to provide context-based schemas
        // Use render_for_context which has recursion protection
        $schemas = self::render_for_context([]);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SchemaGenerator::output_schema() - schemas from integrations: ' . count($schemas));
        }
        
        // If no integrations provided schemas, generate basic ones
        if (empty($schemas)) {
            $schemas = self::generate_basic_context_schemas($context, []);
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
                error_log('SchemaGenerator::output_schema() - kept organization schema: ' . print_r($org_schemas[0], true));
            }
        } else {
            $schemas = $non_org_schemas;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SchemaGenerator::output_schema() - final schemas count: ' . count($schemas));
        }
        
        if (!empty($schemas)) {
            self::output_schemas($schemas);
        }
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
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SchemaGenerator::render_for_post() called for post ID: ' . $post_id);
        }
        
        // Allow plugins/themes to override schema type for this post
        $schema_type = apply_filters('wp_schema_type_for_post', null, $post_id, $options);
        
        if (!$schema_type) {
            // Use the utility class for post type detection
            $schema_type = \BuiltNorth\Schema\Utilities\GetSchemaTypeFromPostType::render($post_id);
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SchemaGenerator::render_for_post() - schema type: ' . $schema_type);
        }

        // Get data from integrations
        $data = apply_filters('wp_schema_data_for_post', null, $post_id, $schema_type, $options);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SchemaGenerator::render_for_post() - data from integrations: ' . ($data !== null ? 'provided' : 'not provided'));
            if ($data !== null) {
                error_log('SchemaGenerator::render_for_post() - integration data: ' . print_r($data, true));
            }
        }
        
        if ($data === null) {
            // No integrations provided data, return empty schema
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('SchemaGenerator::render_for_post() - no data provided, returning empty schema');
            }
            return [];
        }

        // Generate schema based on type
        $schema = self::generate_schema_by_type($schema_type, $data, $options);
        
        // Allow plugins/themes to modify final schema
        $schema = apply_filters('wp_schema_final_schema', $schema, $post_id, $schema_type, $options);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SchemaGenerator::render_for_post() - final schema: ' . print_r($schema, true));
        }
        
        return $schema;
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
        $block_name = $block['blockName'] ?? '';
        $attrs = $block['attrs'] ?? [];
        $content = $block['innerContent'][0] ?? '';

        // Allow plugins/themes to override schema type for this block
        $schema_type = apply_filters('wp_schema_type_for_block', null, $block, $options);
        
        if (!$schema_type) {
            // Detect schema type from block
            $schema_type = self::detect_schema_type_from_block($block);
        }

        // Get data from integrations
        $data = apply_filters('wp_schema_data_for_block', null, $block, $schema_type, $options);
        
        if ($data === null) {
            // No integrations provided data, return empty schema
            return [];
        }

        // Generate schema based on type
        $schema = self::generate_schema_by_type($schema_type, $data, $options);
        
        // Allow plugins/themes to modify final schema
        $schema = apply_filters('wp_schema_final_schema', $schema, $block, $schema_type, $options);
        
        return $schema;
    }

    /**
     * Detect schema type from block
     *
     * @param array $block Block data
     * @return string Schema type
     */
    private static function detect_schema_type_from_block($block)
    {
        $block_name = $block['blockName'] ?? '';
        $attrs = $block['attrs'] ?? [];

        // Check for schema type in block attributes
        if (!empty($attrs['schemaType'])) {
            return $attrs['schemaType'];
        }

        // Map common block names to schema types
        $block_schema_map = [
            'core/paragraph' => 'Article',
            'core/heading' => 'Article',
            'core/image' => 'ImageObject',
            'core/video' => 'VideoObject',
            'core/audio' => 'AudioObject',
            'core/gallery' => 'ImageGallery',
            'core/list' => 'ItemList',
            'core/quote' => 'Quotation',
            'core/pullquote' => 'Quotation',
            'core/table' => 'Table',
            'core/embed' => 'WebPage',
        ];

        return $block_schema_map[$block_name] ?? 'Article';
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
        if (empty($schema)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('SchemaGenerator::output_schema_script() - empty schema provided');
            }
            return '';
        }

        $json = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('SchemaGenerator::output_schema_script() - JSON encoding error: ' . json_last_error_msg());
            }
            return '';
        }
        
        $html = '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n";
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SchemaGenerator::output_schema_script() - generated HTML: ' . $html);
        }
        
        return $html;
    }

    /**
     * Output multiple schemas as script tags
     *
     * @param array $schemas Array of schema data
     * @return void
     */
    public static function output_schemas($schemas)
    {
        if (empty($schemas) || !is_array($schemas)) {
            return;
        }

        foreach ($schemas as $schema) {
            if (!empty($schema)) {
                echo self::output_schema_script($schema);
            }
        }
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
     * Generate schema by type
     *
     * @param string $type Schema type
     * @param array $data Extracted data
     * @param array $options Generation options
     * @return array JSON-LD schema data
     */
    private static function generate_schema_by_type($type, $data, $options = [])
    {

        // Allow plugins/themes to override generation for specific types
        $generated_schema = apply_filters('wp_schema_generate_for_type', null, $type, $data, $options);
        if ($generated_schema !== null) {
            return $generated_schema;
        }

        // Use specific generators for known types
        switch ($type) {
            case 'organization':
                return \BuiltNorth\Schema\Generators\OrganizationGenerator::generate($data, $options);
            case 'local_business':
                return \BuiltNorth\Schema\Generators\LocalBusinessGenerator::generate($data, $options);
            case 'website':
                return \BuiltNorth\Schema\Generators\WebSiteGenerator::generate($data, $options);
            case 'webpage':
                return \BuiltNorth\Schema\Generators\WebPageGenerator::generate($data, $options);
            case 'article':
                return \BuiltNorth\Schema\Generators\ArticleGenerator::generate($data, $options);
            case 'product':
                return \BuiltNorth\Schema\Generators\ProductGenerator::generate($data, $options);
            case 'person':
                return \BuiltNorth\Schema\Generators\PersonGenerator::generate($data, $options);
            case 'faq':
                return \BuiltNorth\Schema\Generators\FaqGenerator::generate($data, $options);
            case 'review':
                return \BuiltNorth\Schema\Generators\ReviewGenerator::generate($data, $options);
            case 'aggregate_rating':
                return \BuiltNorth\Schema\Generators\AggregateRatingGenerator::generate($data, $options);
            case 'navigation':
                return \BuiltNorth\Schema\Generators\NavigationGenerator::generate($data, $options);
            default:
                // Allow plugins/themes to handle unknown types
                $custom_schema = apply_filters('wp_schema_generate_custom_type', null, $type, $data, $options);
                if ($custom_schema !== null) {
                    return $custom_schema;
                }

                // Fallback to basic schema generation
                $schema_data = [
                    '@context' => 'https://schema.org',
                    '@type' => ucfirst($type)
                ];

                // Merge data into schema
                foreach ($data as $key => $value) {
                    if (!empty($value)) {
                        $schema_data[$key] = $value;
                    }
                }

                return $schema_data;
        }
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
        // Prevent infinite recursion with multiple layers of protection
        self::$recursion_depth++;
        
        if (self::$is_generating || self::$recursion_depth > self::$max_recursion_depth) {
            error_log("SchemaGenerator: Recursion detected (depth: " . self::$recursion_depth . "), aborting to prevent memory exhaustion");
            self::$recursion_depth--;
            return [];
        }
        
        self::$is_generating = true;
        
        // Get current context
        $context = self::get_current_context();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SchemaGenerator::render_for_context() - context: ' . $context);
        }
        
        // Get schemas from new architecture first
        $schemas = [];
        try {
            if (class_exists('\\BuiltNorth\\Schema\\Core\\Container')) {
                $container = \BuiltNorth\Schema\Core\Container::getInstance();
                if ($container->has('BuiltNorth\\Schema\\Contracts\\SchemaManagerInterface')) {
                    $schemaManager = $container->get('BuiltNorth\\Schema\\Contracts\\SchemaManagerInterface');
                    $schemas = $schemaManager->generateSchemas($context, $options);
                }
            }
        } catch (\Exception $e) {
            error_log('Error using new architecture: ' . $e->getMessage());
        }
        
        // Allow legacy integrations to add schemas (only if not already generating to prevent loops)
        $legacySchemas = [];
        if (self::$recursion_depth <= 1) {
            $legacySchemas = apply_filters('wp_schema_context_schemas', [], $context, $options);
        }
        $schemas = array_merge($schemas, $legacySchemas);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SchemaGenerator::render_for_context() - schemas from integrations: ' . count($schemas));
        }
        
        // If no integrations provided schemas, generate basic ones
        if (empty($schemas)) {
            $schemas = self::generate_basic_context_schemas($context, $options);
        }
        
        // Remove duplicates and empty schemas
        $schemas = array_filter($schemas);
        
        // Merge and consolidate schemas
        $schemas = self::merge_and_consolidate_schemas($schemas);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SchemaGenerator::render_for_context() - final schemas count: ' . count($schemas));
        }
        
        self::$is_generating = false;
        self::$recursion_depth--;
        return $schemas;
    }

    /**
     * Get current context
     *
     * @return string Context type
     */
    private static function get_current_context()
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
     * Get current entity
     *
     * @return mixed Entity object or null
     */
    private static function get_current_entity()
    {
        if (is_singular()) {
            return get_post();
        } elseif (is_tax() || is_category() || is_tag()) {
            return get_queried_object();
        }
        
        return null;
    }

    /**
     * Get schema type for entity
     *
     * @param mixed $entity Entity object
     * @return string Schema type
     */
    private static function get_schema_type_for_entity($entity)
    {
        if (is_a($entity, 'WP_Post')) {
            $post_type = $entity->post_type;
            
            switch ($post_type) {
                case 'post':
                    return 'article';
                case 'page':
                    return 'webpage';
                case 'product':
                    return 'product';
                default:
                    return 'article';
            }
        } elseif (is_a($entity, 'WP_Term')) {
            return 'webpage';
        }
        
        return 'article';
    }

    /**
     * Build entity data for schema generation
     *
     * @param mixed $entity Entity object
     * @param array $options Generation options
     * @return array Entity data
     */
    private static function build_entity_data($entity, $options)
    {
        if (is_a($entity, 'WP_Post')) {
            return [
                'name' => $options['title'] ?? get_the_title($entity),
                'description' => $options['description'] ?? get_the_excerpt($entity),
                'url' => $options['canonical_url'] ?? get_permalink($entity),
                'datePublished' => get_the_date('c', $entity),
                'dateModified' => get_the_modified_date('c', $entity),
                'author' => get_the_author_meta('display_name', $entity->post_author),
                'image' => self::get_featured_image_url($entity->ID),
            ];
        }
        
        return [];
    }

    /**
     * Merge and consolidate schemas to eliminate duplicates and merge organization data
     *
     * @param array $schemas Array of schemas to merge
     * @return array Consolidated schemas
     */
    private static function merge_and_consolidate_schemas($schemas)
    {
        $organization_schemas = [];
        $other_schemas = [];
        
        // Separate organization schemas from other schemas
        foreach ($schemas as $schema) {
            if (isset($schema['@type']) && in_array($schema['@type'], ['Organization', 'LocalBusiness', 'HomeAndConstructionBusiness', 'FoodEstablishment'])) {
                $organization_schemas[] = $schema;
            } else {
                $other_schemas[] = $schema;
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SchemaGenerator::merge_and_consolidate_schemas() - organization schemas: ' . count($organization_schemas));
            error_log('SchemaGenerator::merge_and_consolidate_schemas() - other schemas: ' . count($other_schemas));
            foreach ($other_schemas as $schema) {
                error_log('SchemaGenerator::merge_and_consolidate_schemas() - other schema type: ' . ($schema['@type'] ?? 'unknown'));
            }
        }
        
        // Merge organization schemas into one comprehensive schema
        $merged_organization = null;
        if (!empty($organization_schemas)) {
            $merged_organization = self::merge_organization_schemas($organization_schemas);
        }
        
        // Remove duplicate schemas of other types
        $unique_schemas = self::remove_duplicate_schemas($other_schemas);
        
        // Combine merged organization with unique other schemas
        $final_schemas = [];
        if ($merged_organization) {
            $final_schemas[] = $merged_organization;
        }
        $final_schemas = array_merge($final_schemas, $unique_schemas);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SchemaGenerator::merge_and_consolidate_schemas() - merged ' . count($organization_schemas) . ' organization schemas into 1');
            error_log('SchemaGenerator::merge_and_consolidate_schemas() - final schemas count: ' . count($final_schemas));
        }
        
        return $final_schemas;
    }

    /**
     * Merge multiple organization schemas into one comprehensive schema
     *
     * @param array $organization_schemas Array of organization schemas
     * @return array Merged organization schema
     */
    private static function merge_organization_schemas($organization_schemas)
    {
        if (empty($organization_schemas)) {
            return null;
        }
        
        // Start with the first schema as the base
        $merged = $organization_schemas[0];
        
        // Merge data from other organization schemas
        for ($i = 1; $i < count($organization_schemas); $i++) {
            $schema = $organization_schemas[$i];
            
            // Merge basic properties (prefer non-empty values)
            $basic_props = ['name', 'description', 'url', 'logo'];
            foreach ($basic_props as $prop) {
                if (!empty($schema[$prop]) && (empty($merged[$prop]) || $merged[$prop] === get_bloginfo('name'))) {
                    $merged[$prop] = $schema[$prop];
                }
            }
            
            // Merge contact information
            if (!empty($schema['telephone']) && empty($merged['telephone'])) {
                $merged['telephone'] = $schema['telephone'];
            }
            if (!empty($schema['email']) && empty($merged['email'])) {
                $merged['email'] = $schema['email'];
            }
            
            // Merge address (prefer more complete addresses)
            if (!empty($schema['address']) && (empty($merged['address']) || count($schema['address']) > count($merged['address']))) {
                $merged['address'] = $schema['address'];
            }
            
            // Merge geo coordinates
            if (!empty($schema['geo']) && empty($merged['geo'])) {
                $merged['geo'] = $schema['geo'];
            }
            
            // Merge social media (combine arrays)
            if (!empty($schema['sameAs'])) {
                if (empty($merged['sameAs'])) {
                    $merged['sameAs'] = $schema['sameAs'];
                } else {
                    $merged['sameAs'] = array_unique(array_merge($merged['sameAs'], $schema['sameAs']));
                }
            }
            
            // Merge business hours (prefer more complete hours)
            if (!empty($schema['openingHoursSpecification']) && (empty($merged['openingHoursSpecification']) || count($schema['openingHoursSpecification']) > count($merged['openingHoursSpecification']))) {
                $merged['openingHoursSpecification'] = $schema['openingHoursSpecification'];
            }
            
            // Merge other properties that might be useful
            if (!empty($schema['potentialAction']) && empty($merged['potentialAction'])) {
                $merged['potentialAction'] = $schema['potentialAction'];
            }
        }
        
        return $merged;
    }

    /**
     * Remove duplicate schemas of the same type
     *
     * @param array $schemas Array of schemas
     * @return array Unique schemas
     */
    private static function remove_duplicate_schemas($schemas)
    {
        $unique_schemas = [];
        $seen_types = [];
        
        foreach ($schemas as $schema) {
            $schema_type = $schema['@type'] ?? 'unknown';
            
            // For certain schema types, we want to keep only one
            if (in_array($schema_type, ['WebSite', 'WebPage', 'SiteNavigationElement', 'BreadcrumbList'])) {
                if (!in_array($schema_type, $seen_types)) {
                    $unique_schemas[] = $schema;
                    $seen_types[] = $schema_type;
                } else {
                    // If we've seen this type before, merge the best data
                    $existing_index = array_search($schema_type, $seen_types);
                    $unique_schemas[$existing_index] = self::merge_schema_data($unique_schemas[$existing_index], $schema);
                }
            } else {
                // For other types, keep all
                $unique_schemas[] = $schema;
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SchemaGenerator::remove_duplicate_schemas() - input schemas: ' . count($schemas) . ', output schemas: ' . count($unique_schemas));
            foreach ($unique_schemas as $schema) {
                error_log('SchemaGenerator::remove_duplicate_schemas() - schema type: ' . ($schema['@type'] ?? 'unknown'));
            }
        }
        
        return $unique_schemas;
    }

    /**
     * Merge schema data, preferring non-empty values
     *
     * @param array $existing Existing schema
     * @param array $new New schema
     * @return array Merged schema
     */
    private static function merge_schema_data($existing, $new)
    {
        $merged = $existing;
        
        foreach ($new as $key => $value) {
            if ($key === '@context' || $key === '@type') {
                continue; // Don't merge these
            }
            
            if (empty($merged[$key]) && !empty($value)) {
                $merged[$key] = $value;
            } elseif (is_array($value) && is_array($merged[$key])) {
                // For arrays, merge them
                $merged[$key] = array_merge($merged[$key], $value);
            }
        }
        
        return $merged;
    }

    /**
     * Generate basic context schemas without framework dependencies
     *
     * @param string $context Current context
     * @param array $options Generation options
     * @return array Array of schema data
     */
    private static function generate_basic_context_schemas($context, $options = [])
    {
        $schemas = [];
        $entity = self::get_current_entity();
        
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
     * Get featured image URL
     *
     * @param int $post_id Post ID
     * @return string Image URL or empty string
     */
    private static function get_featured_image_url($post_id)
    {
        $image_id = get_post_thumbnail_id($post_id);
        if ($image_id) {
            return wp_get_attachment_image_url($image_id, 'full');
        }
        
        return '';
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