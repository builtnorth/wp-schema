<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Integrations;

use BuiltNorth\Schema\Contracts\SchemaProviderInterface;

/**
 * Post Type Schema Provider
 * 
 * Provides schema markup based on post type mappings and SEO settings.
 * Integrates with polaris-seo for enhanced schema customization.
 */
class PostTypeSchemaProvider implements SchemaProviderInterface
{
    /**
     * Post type to schema type mapping
     */
    private array $post_type_mappings = [
        // Article types
        'post' => 'Article',
        'article' => 'Article',
        'blog' => 'Article',
        'blog_post' => 'Article',
        'polaris_blog' => 'Article',

        // WebPage types
        'page' => 'WebPage',
        'polaris_page' => 'WebPage',
        'landing_page' => 'WebPage',
        'static_page' => 'WebPage',

        // Product types
        'product' => 'Product',
        'woocommerce_product' => 'Product',
        'polaris_product' => 'Product',
        'item' => 'Product',
        'goods' => 'Product',
        'download' => 'Product',
        'edd_download' => 'Product',

        // Event types
        'event' => 'Event',
        'polaris_event' => 'Event',
        'tribe_events' => 'Event',
        'event_organiser' => 'Event',
        'ai1ec_event' => 'Event',

        // Recipe types
        'recipe' => 'Recipe',
        'polaris_recipe' => 'Recipe',
        'wprm_recipe' => 'Recipe',
        'yummly_recipe' => 'Recipe',
        'zrdn_recipe' => 'Recipe',

        // Business types
        'restaurant' => 'Restaurant',
        'polaris_restaurant' => 'Restaurant',
        'business' => 'LocalBusiness',
        'polaris_business' => 'LocalBusiness',
        'service' => 'Service',
        'polaris_service' => 'Service',

        // Review types
        'review' => 'Review',
        'polaris_review' => 'Review',
        'testimonial' => 'Review',

        // FAQ types
        'faq' => 'FAQPage',
        'polaris_faq' => 'FAQPage',

        // Person types
        'person' => 'Person',
        'polaris_person' => 'Person',
        'team_member' => 'Person',

        // Job types
        'job' => 'JobPosting',
        'job_listing' => 'JobPosting',

        // Course types
        'course' => 'Course',
        'sfwd-courses' => 'Course', // LearnDash
        'llms_course' => 'Course', // LifterLMS

        // News types
        'news' => 'NewsArticle',
        'polaris_news' => 'NewsArticle',
    ];

    public function can_provide(string $context, array $options = []): bool
    {
        // Only provide on singular pages (posts, pages, custom post types)
        if ($context !== 'singular') {
            return false;
        }

        // Skip admin, feeds, etc.
        if (is_admin() || is_feed() || is_robots() || is_trackback()) {
            return false;
        }

        return is_singular();
    }

    public function get_pieces(string $context, array $options = []): array
    {
        if (!is_singular()) {
            return [];
        }

        $post = get_queried_object();
        if (!$post || !isset($post->ID)) {
            return [];
        }

        // Get schema type (with SEO override support)
        $schema_type = $this->get_schema_type_for_post($post);
        
        // Generate schema based on type
        $schema_data = $this->generate_schema_for_post($post, $schema_type);
        
        if (empty($schema_data)) {
            return [];
        }

        return [$schema_data];
    }

    public function get_priority(): int
    {
        return 30; // Lower priority than organization/website
    }

    /**
     * Get schema type for a post
     */
    private function get_schema_type_for_post($post): string
    {
        // First check if polaris-seo has an override
        if (function_exists('polaris_seo_get_post_schema_type')) {
            $seo_override = polaris_seo_get_post_schema_type($post->ID);
            if (!empty($seo_override)) {
                return $seo_override;
            }
        }

        // Check post meta for SEO settings
        $seo_settings = get_post_meta($post->ID, 'polaris_seo_settings', true);
        if ($seo_settings) {
            $seo_data = is_string($seo_settings) ? json_decode($seo_settings, true) : $seo_settings;
            if (isset($seo_data['schema_type']) && !empty($seo_data['schema_type'])) {
                return $seo_data['schema_type'];
            }
        }

        // Use post type mapping
        $schema_type = $this->post_type_mappings[$post->post_type] ?? 'Article';

        // Allow filtering
        return apply_filters('wp_schema_post_type_mapping', $schema_type, $post->post_type, $post);
    }

    /**
     * Generate schema markup for a post
     */
    private function generate_schema_for_post($post, string $schema_type): array
    {
        $schema_data = [
            '@type' => $schema_type,
            '@id' => get_permalink($post->ID) . '#' . strtolower($schema_type),
            'headline' => $post->post_title,
            'name' => $post->post_title,
            'url' => get_permalink($post->ID),
        ];

        // Add common properties based on schema type
        switch ($schema_type) {
            case 'Article':
            case 'BlogPosting':
            case 'NewsArticle':
                $schema_data = $this->add_article_properties($schema_data, $post);
                break;
            case 'WebPage':
                $schema_data = $this->add_webpage_properties($schema_data, $post);
                break;
            case 'Product':
                $schema_data = $this->add_product_properties($schema_data, $post);
                break;
            case 'Event':
                $schema_data = $this->add_event_properties($schema_data, $post);
                break;
            case 'Person':
                $schema_data = $this->add_person_properties($schema_data, $post);
                break;
        }

        // Add SEO meta description if available
        $seo_settings = get_post_meta($post->ID, 'polaris_seo_settings', true);
        if ($seo_settings) {
            $seo_data = is_string($seo_settings) ? json_decode($seo_settings, true) : $seo_settings;
            if (isset($seo_data['meta_description']) && !empty($seo_data['meta_description'])) {
                $schema_data['description'] = $seo_data['meta_description'];
            }
        }

        // Fallback to excerpt
        if (empty($schema_data['description']) && !empty($post->post_excerpt)) {
            $schema_data['description'] = wp_strip_all_tags($post->post_excerpt);
        }

        return $schema_data;
    }

    /**
     * Add Article-specific properties
     */
    private function add_article_properties(array $schema_data, $post): array
    {
        $schema_data['datePublished'] = get_the_date('c', $post->ID);
        $schema_data['dateModified'] = get_the_modified_date('c', $post->ID);
        
        // Author
        $author = get_userdata($post->post_author);
        if ($author) {
            $schema_data['author'] = [
                '@type' => 'Person',
                'name' => $author->display_name,
                'url' => get_author_posts_url($post->post_author),
            ];
        }

        // Publisher (use organization from wp-schema)
        $schema_data['publisher'] = [
            '@id' => home_url('/#organization')
        ];

        // Featured image
        if (has_post_thumbnail($post->ID)) {
            $image_id = get_post_thumbnail_id($post->ID);
            $image_url = wp_get_attachment_image_url($image_id, 'full');
            if ($image_url) {
                $schema_data['image'] = [
                    '@type' => 'ImageObject',
                    'url' => $image_url,
                ];
            }
        }

        return $schema_data;
    }

    /**
     * Add WebPage-specific properties
     */
    private function add_webpage_properties(array $schema_data, $post): array
    {
        $schema_data['isPartOf'] = [
            '@id' => home_url('/#website')
        ];

        $schema_data['datePublished'] = get_the_date('c', $post->ID);
        $schema_data['dateModified'] = get_the_modified_date('c', $post->ID);

        return $schema_data;
    }

    /**
     * Add Product-specific properties
     */
    private function add_product_properties(array $schema_data, $post): array
    {
        // Basic product schema - can be extended by WooCommerce providers
        if (!empty($post->post_excerpt)) {
            $schema_data['description'] = wp_strip_all_tags($post->post_excerpt);
        }

        // Featured image as product image
        if (has_post_thumbnail($post->ID)) {
            $image_id = get_post_thumbnail_id($post->ID);
            $image_url = wp_get_attachment_image_url($image_id, 'full');
            if ($image_url) {
                $schema_data['image'] = $image_url;
            }
        }

        return $schema_data;
    }

    /**
     * Add Event-specific properties
     */
    private function add_event_properties(array $schema_data, $post): array
    {
        // Basic event schema - can be extended by event plugin providers
        if (!empty($post->post_excerpt)) {
            $schema_data['description'] = wp_strip_all_tags($post->post_excerpt);
        }

        return $schema_data;
    }

    /**
     * Add Person-specific properties
     */
    private function add_person_properties(array $schema_data, $post): array
    {
        if (!empty($post->post_excerpt)) {
            $schema_data['description'] = wp_strip_all_tags($post->post_excerpt);
        }

        // Featured image as person image
        if (has_post_thumbnail($post->ID)) {
            $image_id = get_post_thumbnail_id($post->ID);
            $image_url = wp_get_attachment_image_url($image_id, 'full');
            if ($image_url) {
                $schema_data['image'] = [
                    '@type' => 'ImageObject',
                    'url' => $image_url,
                ];
            }
        }

        return $schema_data;
    }

    /**
     * Get post type mappings (for external use)
     */
    public function get_post_type_mappings(): array
    {
        return apply_filters('wp_schema_post_type_mappings', $this->post_type_mappings);
    }

    /**
     * Add or update a post type mapping
     */
    public function add_post_type_mapping(string $post_type, string $schema_type): void
    {
        $this->post_type_mappings[$post_type] = $schema_type;
    }
}