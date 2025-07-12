<?php

namespace BuiltNorth\Schema\Defaults;

/**
 * WordPress Core Integration
 * 
 * Provides basic schema data generation using WordPress core functionality.
 * This integration provides WebSite, WebPage, and basic navigation schemas
 * without any framework dependencies.
 */
class WordPressCoreIntegration extends BaseIntegration
{
    /**
     * Integration name
     *
     * @var string
     */
    protected static $integration_name = 'wordpress_core';

    /**
     * Register WordPress hooks for WordPress Core integration
     *
     * @return void
     */
    protected static function register_hooks()
    {
        // Provide context-based schemas using WordPress core
        add_filter('wp_schema_context_schemas', [self::class, 'provide_context_schemas'], 10, 3);
    }

    /**
     * Provide context-based schemas using WordPress core
     *
     * @param array $schemas Existing schemas
     * @param string $context Current context
     * @param array $options Generation options
     * @return array Modified schemas
     */
    public static function provide_context_schemas($schemas, $context, $options)
    {
        $entity = self::get_current_entity();
        
        // Add context-specific schemas
        switch ($context) {
            case 'home':
                // WebSite schema
                $website_schema = [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => $options['site_name'] ?? get_bloginfo('name'),
                    'url' => $options['canonical_url'] ?? home_url('/'),
                    'description' => $options['description'] ?? get_bloginfo('description'),
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => [
                            '@type' => 'EntryPoint',
                            'urlTemplate' => home_url('/?s={search_term_string}')
                        ],
                        'query-input' => 'required name=search_term_string'
                    ]
                ];
                $schemas[] = $website_schema;
                
                // WebPage schema
                $webpage_schema = [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebPage',
                    'name' => $options['title'] ?? get_bloginfo('name'),
                    'url' => $options['canonical_url'] ?? home_url('/'),
                    'description' => $options['description'] ?? get_bloginfo('description'),
                ];
                $schemas[] = $webpage_schema;
                break;
                
            case 'singular':
                if ($entity) {
                    // Main entity schema (Article, Product, etc.)
                    $schema_type = self::get_schema_type_for_entity($entity);
                    $entity_data = self::build_entity_data($entity, $options);
                    $entity_schema = [
                        '@context' => 'https://schema.org',
                        '@type' => ucfirst($schema_type),
                    ];
                    $entity_schema = array_merge($entity_schema, $entity_data);
                    $schemas[] = $entity_schema;
                }
                
                // WebPage schema
                $webpage_schema = [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebPage',
                    'name' => $options['title'] ?? ($entity ? get_the_title($entity) : get_bloginfo('name')),
                    'url' => $options['canonical_url'] ?? ($entity ? get_permalink($entity) : home_url('/')),
                    'description' => $options['description'] ?? ($entity ? get_the_excerpt($entity) : get_bloginfo('description')),
                ];
                $schemas[] = $webpage_schema;
                break;
                
            case 'taxonomy':
                // WebSite schema (for breadcrumbs)
                $website_schema = [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => $options['site_name'] ?? get_bloginfo('name'),
                    'url' => $options['canonical_url'] ?? home_url('/'),
                    'description' => $options['description'] ?? get_bloginfo('description'),
                ];
                $schemas[] = $website_schema;
                
                // WebPage schema
                $webpage_schema = [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebPage',
                    'name' => $options['title'] ?? ($entity ? get_the_title($entity) : get_bloginfo('name')),
                    'url' => $options['canonical_url'] ?? ($entity ? get_permalink($entity) : home_url('/')),
                    'description' => $options['description'] ?? ($entity ? get_the_excerpt($entity) : get_bloginfo('description')),
                ];
                $schemas[] = $webpage_schema;
                break;
                
            case 'archive':
                // WebPage schema
                $webpage_schema = [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebPage',
                    'name' => $options['title'] ?? get_the_archive_title(),
                    'url' => $options['canonical_url'] ?? get_pagenum_link(),
                    'description' => $options['description'] ?? get_the_archive_description(),
                ];
                $schemas[] = $webpage_schema;
                break;
        }

        // Add basic navigation schema for all contexts
        $navigation_schema = [
            '@context' => 'https://schema.org',
            '@type' => 'SiteNavigationElement',
            'name' => 'Main Navigation',
            'url' => $options['canonical_url'] ?? ($entity ? get_permalink($entity) : home_url('/')),
        ];
        $schemas[] = $navigation_schema;

        return $schemas;
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
     * Check if integration is available
     *
     * @return bool
     */
    public static function is_available()
    {
        return true; // Always available since it uses WordPress core
    }

    /**
     * Get integration description
     *
     * @return string
     */
    public static function get_description()
    {
        return 'Provides basic schema generation using WordPress core functionality (WebSite, WebPage, Navigation).';
    }

    /**
     * Get supported schema types
     *
     * @return array
     */
    public static function get_supported_schema_types()
    {
        return ['website', 'webpage', 'navigation', 'article', 'product'];
    }
} 