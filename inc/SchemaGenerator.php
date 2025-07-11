<?php

namespace BuiltNorth\Schema;

use BuiltNorth\Schema\Generators\BaseGenerator;

/**
 * Schema Generator Utility
 * 
 * Provides easy access to schema generation functionality with enhanced hook support
 */
class SchemaGenerator
{
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
        
        // Add schema to head
        add_action('wp_head', [self::class, 'output_schema']);
        
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

        // If content is already an array, use it directly
        if (is_array($content)) {
            return self::generate_schema_by_type($type, $content, $options);
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
        if (is_singular()) {
            global $post;
            $schema = self::render_for_post($post->ID);
            if (!empty($schema)) {
                echo self::output_schema_script($schema);
            }
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
        // Allow plugins/themes to override schema type for this post
        $schema_type = apply_filters('wp_schema_type_for_post', null, $post_id, $options);
        
        if (!$schema_type) {
            // Use the utility class for post type detection
            $schema_type = \BuiltNorth\Schema\Utilities\GetSchemaTypeFromPostType::render($post_id);
        }

        // Allow plugins/themes to provide custom data for this post
        $custom_data = apply_filters('wp_schema_data_for_post', null, $post_id, $schema_type, $options);
        
        if ($custom_data !== null) {
            return self::generate_schema_by_type($schema_type, $custom_data, $options);
        }

        // Get post content for schema generation
        $post = get_post($post_id);
        if (!$post) {
            return [];
        }

        // Generate schema using the post content
        return self::render($post->post_content, $schema_type, $options);
    }

    /**
     * Generate schema for a specific block with enhanced hook support
     *
     * @param array $block Block data
     * @param array $options Generation options
     * @return array JSON-LD schema data
     */
    public static function render_for_block($block, $options = [])
    {
        // Allow plugins/themes to override schema type for this block
        $schema_type = apply_filters('wp_schema_type_for_block', null, $block, $options);
        
        if (!$schema_type) {
            // Auto-detect schema type based on block name
            $schema_type = self::detect_schema_type_from_block($block);
        }

        // Allow plugins/themes to provide custom data for this block
        $custom_data = apply_filters('wp_schema_data_for_block', null, $block, $schema_type, $options);
        
        if ($custom_data !== null) {
            return self::generate_schema_by_type($schema_type, $custom_data, $options);
        }

        // Extract data from block content
        $block_content = $block['innerContent'][0] ?? '';
        $block_attrs = $block['attrs'] ?? [];
        
        // Allow plugins/themes to modify block content and attributes
        $block_content = apply_filters('wp_schema_block_content', $block_content, $block, $options);
        $block_attrs = apply_filters('wp_schema_block_attributes', $block_attrs, $block, $options);

        // Combine content and attributes for processing
        $combined_data = array_merge(['content' => $block_content], $block_attrs);
        
        return self::render($combined_data, $schema_type, $options);
    }

    /**
     * Detect schema type from block name and attributes
     *
     * @param array $block Block data
     * @return string Schema type
     */
    private static function detect_schema_type_from_block($block)
    {
        $block_name = $block['blockName'] ?? '';
        $block_attrs = $block['attrs'] ?? [];

        // Allow plugins/themes to provide custom detection logic
        $detected_type = apply_filters('wp_schema_detect_type_from_block', null, $block_name, $block_attrs);
        
        if ($detected_type) {
            return $detected_type;
        }

        // Default block-based detection
        $block_schema_map = [
            'core/faq' => 'FAQPage',
            'core/testimonial' => 'Review',
            'core/product' => 'Product',
            'core/event' => 'Event',
            'core/recipe' => 'Recipe',
            'core/person' => 'Person',
            'core/organization' => 'Organization',
            'core/business' => 'LocalBusiness',
            'core/service' => 'Service',
            'core/review' => 'Review',
            'core/rating' => 'AggregateRating',
        ];

        // Check for custom block patterns
        foreach ($block_schema_map as $pattern => $schema_type) {
            if (strpos($block_name, $pattern) !== false) {
                return $schema_type;
            }
        }

        // Check block attributes for schema hints
        if (!empty($block_attrs['schemaType'])) {
            return $block_attrs['schemaType'];
        }

        if (!empty($block_attrs['isReview'])) {
            return 'Review';
        }

        if (!empty($block_attrs['isFAQ'])) {
            return 'FAQPage';
        }

        if (!empty($block_attrs['isProduct'])) {
            return 'Product';
        }

        // Default to Article for content blocks
        return 'Article';
    }

    /**
     * Collect schema data from all blocks in a post
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

        // Parse blocks
        $blocks = parse_blocks($post->post_content);
        $schemas = [];

        foreach ($blocks as $block) {
            if (empty($block['blockName'])) {
                continue; // Skip non-block content
            }

            // Allow plugins/themes to skip certain blocks
            $should_process = apply_filters('wp_schema_should_process_block', true, $block, $post_id, $options);
            if (!$should_process) {
                continue;
            }

            $block_schema = self::render_for_block($block, $options);
            if (!empty($block_schema)) {
                $schemas[] = $block_schema;
            }
        }

        // Allow plugins/themes to modify collected schemas
        $schemas = apply_filters('wp_schema_collected_block_schemas', $schemas, $post_id, $options);

        return $schemas;
    }

    /**
     * Output JSON-LD schema script tag
     *
     * @param array $schema JSON-LD schema data
     * @return void
     */
    public static function output_schema_script($schema)
    {
        if (!empty($schema)) {
            echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
        }
    }

    /**
     * Detect patterns in content
     *
     * @param string $content Content to analyze
     * @param string $type Schema type
     * @return array Detected patterns
     * @deprecated Use hooks instead. This method is no longer supported.
     */
    public static function detect_patterns($content, $type)
    {
        return [];
    }

    /**
     * Get the best pattern for a schema type
     *
     * @param string $schema_type Schema type
     * @param array $detected_patterns Detected patterns
     * @return string Best pattern to use
     * @deprecated Use hooks instead. This method is no longer supported.
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