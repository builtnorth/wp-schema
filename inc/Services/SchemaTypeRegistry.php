<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Services;

/**
 * Schema Type Registry
 * 
 * Manages available schema.org types for UI and validation.
 * 
 * @since 3.0.0
 */
class SchemaTypeRegistry
{
    /**
     * Get available schema types for UI
     */
    public function get_available_types(): array
    {
        // Comprehensive list of schema.org types commonly used
        $types = [
            // Content Types
            ['label' => 'Article', 'value' => 'Article'],
            ['label' => 'BlogPosting', 'value' => 'BlogPosting'],
            ['label' => 'NewsArticle', 'value' => 'NewsArticle'],
            ['label' => 'WebPage', 'value' => 'WebPage'],
            
            // Page Types
            ['label' => 'Contact Page', 'value' => 'ContactPage'],
            ['label' => 'About Page', 'value' => 'AboutPage'],
            ['label' => 'Privacy Policy Page', 'value' => 'PrivacyPolicyPage'],
            ['label' => 'Terms of Service Page', 'value' => 'TermsOfServicePage'],
            ['label' => 'Checkout Page', 'value' => 'CheckoutPage'],
            ['label' => 'Profile Page', 'value' => 'ProfilePage'],
            ['label' => 'FAQ Page', 'value' => 'FAQPage'],
            ['label' => 'Collection Page', 'value' => 'CollectionPage'],
            ['label' => 'Media Gallery', 'value' => 'MediaGallery'],
            
            // Commerce
            ['label' => 'Product', 'value' => 'Product'],
            ['label' => 'Service', 'value' => 'Service'],
            ['label' => 'Review', 'value' => 'Review'],
            
            // Business
            ['label' => 'LocalBusiness', 'value' => 'LocalBusiness'],
            ['label' => 'Restaurant', 'value' => 'Restaurant'],
            ['label' => 'Organization', 'value' => 'Organization'],
            
            // Media
            ['label' => 'VideoObject', 'value' => 'VideoObject'],
            ['label' => 'ImageObject', 'value' => 'ImageObject'],
            ['label' => 'AudioObject', 'value' => 'AudioObject'],
            
            // Other
            ['label' => 'Event', 'value' => 'Event'],
            ['label' => 'Recipe', 'value' => 'Recipe'],
            ['label' => 'Book', 'value' => 'Book'],
            ['label' => 'Movie', 'value' => 'Movie'],
            ['label' => 'MusicRecording', 'value' => 'MusicRecording'],
            ['label' => 'Person', 'value' => 'Person'],
            ['label' => 'JobPosting', 'value' => 'JobPosting'],
            ['label' => 'Course', 'value' => 'Course'],
            ['label' => 'SoftwareApplication', 'value' => 'SoftwareApplication'],
            ['label' => 'Place', 'value' => 'Place'],
            ['label' => 'CreativeWork', 'value' => 'CreativeWork'],
        ];

        // Allow filtering to add/remove types
        return apply_filters('wp_schema_type_registry_types', $types);
    }

    /**
     * Get post type to schema type mappings
     */
    public function get_post_type_mappings(): array
    {
        $mappings = [
            'post' => 'Article',
            'page' => 'WebPage',
            'product' => 'Product',
            'event' => 'Event',
            'news' => 'NewsArticle',
            'blog_post' => 'BlogPosting',
            'recipe' => 'Recipe',
            'restaurant' => 'Restaurant',
            'business' => 'LocalBusiness',
            'service' => 'Service',
            'review' => 'Review',
            'book' => 'Book',
            'movie' => 'Movie',
            'job' => 'JobPosting',
            'course' => 'Course',
            'faq' => 'FAQPage',
        ];

        return apply_filters('wp_schema_post_type_mappings', $mappings);
    }

    /**
     * Get schema type for a post type
     */
    public function get_schema_type_for_post_type(string $post_type): string
    {
        $mappings = $this->get_post_type_mappings();
        return $mappings[$post_type] ?? 'Article';
    }

    /**
     * Check if a schema type is valid
     */
    public function is_valid_type(string $type): bool
    {
        $available = $this->get_available_types();
        $values = array_column($available, 'value');
        return in_array($type, $values, true);
    }
}