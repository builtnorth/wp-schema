<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Services;

use BuiltNorth\Schema\Generators\BaseGenerator;

/**
 * Schema Service
 * 
 * Handles core schema generation logic extracted from SchemaGenerator
 */
class SchemaService
{
    /**
     * Recursion guard for schema generation
     * @var bool
     */
    private static bool $is_generating = false;
    
    /**
     * Global recursion counter to detect deep loops
     * @var int
     */
    private static int $recursion_depth = 0;
    
    /**
     * Maximum allowed recursion depth
     * @var int
     */
    private static int $max_recursion_depth = 3;

    /**
     * Generate schema from various content sources with hook support
     *
     * @param mixed $content Content to extract schema from
     * @param string $type Schema type (faq, article, product, etc.)
     * @param array $options Generation options
     * @return array JSON-LD schema data or empty array
     */
    public function render($content = '', string $type = 'faq', array $options = []): array
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
            $schema = $this->generate_schema_by_type($type, $content, $options);
            
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
        $schema = $this->generate_schema_by_type($type, $data, $options);
        
        // Allow plugins/themes to modify final schema
        $schema = apply_filters('wp_schema_final_schema', $schema, $content, $type, $options);
        
        return $schema;
    }

    /**
     * Generate schema for a specific post with enhanced hook support
     *
     * @param int $post_id Post ID
     * @param array $options Generation options
     * @return array JSON-LD schema data
     */
    public function renderForPost(int $post_id, array $options = []): array
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SchemaService::renderForPost() called for post ID: ' . $post_id);
        }
        
        // Allow plugins/themes to override schema type for this post
        $schema_type = apply_filters('wp_schema_type_for_post', null, $post_id, $options);
        
        if (!$schema_type) {
            // Use the utility class for post type detection
            $schema_type = \BuiltNorth\Schema\Utilities\GetSchemaTypeFromPostType::render($post_id);
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SchemaService::renderForPost() - schema type: ' . $schema_type);
        }

        // Get data from integrations
        $data = apply_filters('wp_schema_data_for_post', null, $post_id, $schema_type, $options);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SchemaService::renderForPost() - data from integrations: ' . ($data !== null ? 'provided' : 'not provided'));
            if ($data !== null) {
                error_log('SchemaService::renderForPost() - integration data: ' . print_r($data, true));
            }
        }
        
        if ($data === null) {
            // No integrations provided data, return empty schema
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('SchemaService::renderForPost() - no data provided, returning empty schema');
            }
            return [];
        }

        // Generate schema based on type
        $schema = $this->generate_schema_by_type($schema_type, $data, $options);
        
        // Allow plugins/themes to modify final schema
        $schema = apply_filters('wp_schema_final_schema', $schema, $post_id, $schema_type, $options);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SchemaService::renderForPost() - final schema: ' . print_r($schema, true));
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
    public function renderForBlock(array $block, array $options = []): array
    {
        $block_name = $block['blockName'] ?? '';
        $attrs = $block['attrs'] ?? [];
        $content = $block['innerContent'][0] ?? '';

        // Allow plugins/themes to override schema type for this block
        $schema_type = apply_filters('wp_schema_type_for_block', null, $block, $options);
        
        if (!$schema_type) {
            // Detect schema type from block
            $schema_type = $this->detect_schema_type_from_block($block);
        }

        // Get data from integrations
        $data = apply_filters('wp_schema_data_for_block', null, $block, $schema_type, $options);
        
        if ($data === null) {
            // No integrations provided data, return empty schema
            return [];
        }

        // Generate schema based on type
        $schema = $this->generate_schema_by_type($schema_type, $data, $options);
        
        // Allow plugins/themes to modify final schema
        $schema = apply_filters('wp_schema_final_schema', $schema, $block, $schema_type, $options);
        
        return $schema;
    }

    /**
     * Generate schemas for current context
     *
     * @param array $options Generation options
     * @return array Array of schema data
     */
    public function renderForContext(array $options = []): array
    {
        // Prevent infinite recursion with multiple layers of protection
        self::$recursion_depth++;
        
        if (self::$is_generating || self::$recursion_depth > self::$max_recursion_depth) {
            error_log("SchemaService: Recursion detected (depth: " . self::$recursion_depth . "), aborting to prevent memory exhaustion");
            self::$recursion_depth--;
            return [];
        }
        
        self::$is_generating = true;
        
        // Get current context
        $context = $this->get_current_context();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SchemaService::renderForContext() - context: ' . $context);
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
            error_log('SchemaService::renderForContext() - schemas from integrations: ' . count($schemas));
        }
        
        // If no integrations provided schemas, generate basic ones
        if (empty($schemas)) {
            $schemas = $this->generate_basic_context_schemas($context, $options);
        }
        
        // Remove duplicates and empty schemas
        $schemas = array_filter($schemas);
        
        // Merge and consolidate schemas
        $schemas = $this->merge_and_consolidate_schemas($schemas);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SchemaService::renderForContext() - final schemas count: ' . count($schemas));
        }
        
        self::$is_generating = false;
        self::$recursion_depth--;
        return $schemas;
    }

    /**
     * Generate schema by type
     *
     * @param string $type Schema type
     * @param array $data Extracted data
     * @param array $options Generation options
     * @return array JSON-LD schema data
     */
    private function generate_schema_by_type(string $type, array $data, array $options = []): array
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
     * Detect schema type from block
     *
     * @param array $block Block data
     * @return string Schema type
     */
    private function detect_schema_type_from_block(array $block): string
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

    /**
     * Merge and consolidate schemas to eliminate duplicates and merge organization data
     *
     * @param array $schemas Array of schemas to merge
     * @return array Consolidated schemas
     */
    private function merge_and_consolidate_schemas(array $schemas): array
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
            error_log('SchemaService::merge_and_consolidate_schemas() - organization schemas: ' . count($organization_schemas));
            error_log('SchemaService::merge_and_consolidate_schemas() - other schemas: ' . count($other_schemas));
            foreach ($other_schemas as $schema) {
                error_log('SchemaService::merge_and_consolidate_schemas() - other schema type: ' . ($schema['@type'] ?? 'unknown'));
            }
        }
        
        // Merge organization schemas into one comprehensive schema
        $merged_organization = null;
        if (!empty($organization_schemas)) {
            $merged_organization = $this->merge_organization_schemas($organization_schemas);
        }
        
        // Remove duplicate schemas of other types
        $unique_schemas = $this->remove_duplicate_schemas($other_schemas);
        
        // Combine merged organization with unique other schemas
        $final_schemas = [];
        if ($merged_organization) {
            $final_schemas[] = $merged_organization;
        }
        $final_schemas = array_merge($final_schemas, $unique_schemas);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SchemaService::merge_and_consolidate_schemas() - merged ' . count($organization_schemas) . ' organization schemas into 1');
            error_log('SchemaService::merge_and_consolidate_schemas() - final schemas count: ' . count($final_schemas));
        }
        
        return $final_schemas;
    }

    /**
     * Merge multiple organization schemas into one comprehensive schema
     *
     * @param array $organization_schemas Array of organization schemas
     * @return array Merged organization schema
     */
    private function merge_organization_schemas(array $organization_schemas): ?array
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
    private function remove_duplicate_schemas(array $schemas): array
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
                    $unique_schemas[$existing_index] = $this->merge_schema_data($unique_schemas[$existing_index], $schema);
                }
            } else {
                // For other types, keep all
                $unique_schemas[] = $schema;
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SchemaService::remove_duplicate_schemas() - input schemas: ' . count($schemas) . ', output schemas: ' . count($unique_schemas));
            foreach ($unique_schemas as $schema) {
                error_log('SchemaService::remove_duplicate_schemas() - schema type: ' . ($schema['@type'] ?? 'unknown'));
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
    private function merge_schema_data(array $existing, array $new): array
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
} 