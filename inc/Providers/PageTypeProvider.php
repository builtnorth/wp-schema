<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Providers;

use BuiltNorth\Schema\Contracts\SchemaProviderInterface;
use BuiltNorth\Schema\Graph\SchemaPiece;

/**
 * Page Type Provider
 * 
 * Provides specialized page type schemas (ContactPage, AboutPage, etc.)
 * when selected via schema type override (e.g., from polaris-seo).
 * 
 * @since 3.0.0
 */
class PageTypeProvider implements SchemaProviderInterface
{
    public function can_provide(string $context): bool
    {
        // Only provide for singular pages
        if ($context !== 'singular' || !is_singular()) {
            return false;
        }
        
        // Check if there's a schema type override
        $post_id = get_the_ID();
        if (!$post_id) {
            return false;
        }
        
        $schema_type = apply_filters('wp_schema_post_type_override', '', $post_id, get_post_type(), get_post());
        
        // Check if it's one of our supported page types
        return in_array($schema_type, $this->get_supported_types(), true);
    }
    
    public function get_pieces(string $context): array
    {
        $post = get_post();
        if (!$post) {
            return [];
        }
        
        // Get the schema type override
        $schema_type = apply_filters('wp_schema_post_type_override', '', $post->ID, $post->post_type, $post);
        
        if (!in_array($schema_type, $this->get_supported_types(), true)) {
            return [];
        }
        
        // Create the appropriate page type
        $page = new SchemaPiece('#' . $post->post_name, $schema_type);
        
        // Set common properties
        $this->set_common_properties($page, $post);
        
        // Set type-specific properties
        switch ($schema_type) {
            case 'ContactPage':
                $this->set_contact_page_properties($page, $post);
                break;
            case 'AboutPage':
                $this->set_about_page_properties($page, $post);
                break;
            case 'PrivacyPolicyPage':
                $this->set_privacy_policy_properties($page, $post);
                break;
            case 'TermsOfServicePage':
                $this->set_terms_of_service_properties($page, $post);
                break;
            case 'CheckoutPage':
                $this->set_checkout_page_properties($page, $post);
                break;
            case 'ProfilePage':
                $this->set_profile_page_properties($page, $post);
                break;
            case 'FAQPage':
                $this->set_faq_page_properties($page, $post);
                break;
            case 'CollectionPage':
                $this->set_collection_page_properties($page, $post);
                break;
            case 'MediaGallery':
                $this->set_media_gallery_properties($page, $post);
                break;
        }
        
        // Allow filtering of page data
        $data = apply_filters('wp_schema_page_type_data', $page->to_array(), $context, $schema_type);
        $page->from_array($data);
        
        return [$page];
    }
    
    public function get_priority(): int
    {
        return 15; // Higher priority than ArticleProvider
    }
    
    /**
     * Get supported page types
     */
    private function get_supported_types(): array
    {
        return [
            'ContactPage',
            'AboutPage',
            'PrivacyPolicyPage',
            'TermsOfServicePage',
            'CheckoutPage',
            'ProfilePage',
            'FAQPage',
            'CollectionPage',
            'MediaGallery',
        ];
    }
    
    /**
     * Set common properties for all page types
     */
    private function set_common_properties(SchemaPiece $page, \WP_Post $post): void
    {
        // Basic properties
        $page->set('url', get_permalink($post));
        $page->set('name', get_the_title($post));
        $page->set('headline', get_the_title($post));
        
        // Dates
        $page->set('datePublished', get_the_date('c', $post));
        $page->set('dateModified', get_the_modified_date('c', $post));
        
        // Description
        if ($post->post_excerpt) {
            $page->set('description', wp_strip_all_tags($post->post_excerpt));
        }
        
        // Author
        $author_id = $post->post_author;
        if ($author_id) {
            $page->set('author', [
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', $author_id),
                'url' => get_author_posts_url($author_id),
            ]);
        }
        
        // Publisher (organization)
        $page->add_reference('publisher', '#organization');
        
        // Featured image
        if (has_post_thumbnail($post->ID)) {
            $image_url = get_the_post_thumbnail_url($post->ID, 'full');
            if ($image_url) {
                $page->set('image', [
                    '@type' => 'ImageObject',
                    'url' => $image_url,
                ]);
            }
        }
        
        // Breadcrumb
        $page->add_reference('breadcrumb', '#breadcrumb');
        
        // Main entity (the content)
        $page->add_reference('mainEntity', '#main-content');
    }
    
    /**
     * Set ContactPage specific properties
     */
    private function set_contact_page_properties(SchemaPiece $page, \WP_Post $post): void
    {
        // Add organization reference as contact point provider
        $page->add_reference('contactPoint', '#organization');
        
        // Add potential action for contacting
        $page->set('potentialAction', [
            '@type' => 'CommunicateAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => get_permalink($post),
            ],
        ]);
    }
    
    /**
     * Set AboutPage specific properties
     */
    private function set_about_page_properties(SchemaPiece $page, \WP_Post $post): void
    {
        // Reference the organization this page is about
        $page->add_reference('about', '#organization');
        
        // Add main entity reference
        $page->set('mainEntity', [
            '@id' => '#organization',
        ]);
    }
    
    /**
     * Set PrivacyPolicyPage specific properties
     */
    private function set_privacy_policy_properties(SchemaPiece $page, \WP_Post $post): void
    {
        // Mark as accessible for free
        $page->set('isAccessibleForFree', true);
        
        // Add in language
        $page->set('inLanguage', get_locale());
        
        // Last reviewed date (use modified date)
        $page->set('lastReviewed', get_the_modified_date('c', $post));
    }
    
    /**
     * Set TermsOfServicePage specific properties
     */
    private function set_terms_of_service_properties(SchemaPiece $page, \WP_Post $post): void
    {
        // Mark as accessible for free
        $page->set('isAccessibleForFree', true);
        
        // Add in language
        $page->set('inLanguage', get_locale());
        
        // Last reviewed date
        $page->set('lastReviewed', get_the_modified_date('c', $post));
    }
    
    /**
     * Set CheckoutPage specific properties
     */
    private function set_checkout_page_properties(SchemaPiece $page, \WP_Post $post): void
    {
        // Add potential action for purchasing
        $page->set('potentialAction', [
            '@type' => 'BuyAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => get_permalink($post),
            ],
        ]);
        
        // Mark as part of website
        $page->add_reference('isPartOf', '#website');
    }
    
    /**
     * Set ProfilePage specific properties
     */
    private function set_profile_page_properties(SchemaPiece $page, \WP_Post $post): void
    {
        // If this is an author archive or user profile
        if (is_author()) {
            $author = get_queried_object();
            if ($author instanceof \WP_User) {
                $page->set('about', [
                    '@type' => 'Person',
                    'name' => $author->display_name,
                    'url' => get_author_posts_url($author->ID),
                ]);
            }
        }
    }
    
    /**
     * Set FAQPage specific properties
     */
    private function set_faq_page_properties(SchemaPiece $page, \WP_Post $post): void
    {
        // Look for FAQ content in the post
        $faq_items = apply_filters('wp_schema_faq_items', [], $post->ID);
        
        if (!empty($faq_items)) {
            $page->set('mainEntity', $faq_items);
        }
    }
    
    /**
     * Set CollectionPage specific properties
     */
    private function set_collection_page_properties(SchemaPiece $page, \WP_Post $post): void
    {
        // This is for manually curated collection pages
        // Different from archive pages which are automatic
        
        // Look for collection items
        $collection_items = apply_filters('wp_schema_collection_items', [], $post->ID);
        
        if (!empty($collection_items)) {
            $page->set('hasPart', $collection_items);
        }
    }
    
    /**
     * Set MediaGallery specific properties
     */
    private function set_media_gallery_properties(SchemaPiece $page, \WP_Post $post): void
    {
        // Look for gallery items
        $gallery_items = apply_filters('wp_schema_gallery_items', [], $post->ID);
        
        if (!empty($gallery_items)) {
            $page->set('hasPart', $gallery_items);
        }
        
        // Add image count if available
        $image_count = apply_filters('wp_schema_gallery_image_count', 0, $post->ID);
        if ($image_count > 0) {
            $page->set('numberOfItems', $image_count);
        }
    }
}