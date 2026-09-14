<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Providers;

use BuiltNorth\WPSchema\Contracts\SchemaProviderInterface;
use BuiltNorth\WPSchema\Graph\SchemaPiece;
use BuiltNorth\WPSchema\Services\SchemaIds;

/**
 * Page Type Provider
 *
 * Contributes subtype-specific properties (ContactPage, AboutPage, FAQPage…) to
 * the page node WebPageProvider emits.
 *
 * It deliberately emits no node of its own. A page that is "also a ContactPage"
 * is one thing with two types — schema.org expresses that as a single node typed
 * `["WebPage","ContactPage"]`, not two nodes competing to describe the same URL.
 * WebPageProvider owns the node and appends the subtype; this provider decorates
 * it through `wp_schema_framework_piece_id_webpage`, the seam SchemaGraph already
 * documents for contributing properties to an existing node.
 *
 * @since 3.0.0
 */
class PageTypeProvider implements SchemaProviderInterface
{
    public function __construct()
    {
        add_filter('wp_schema_framework_piece_id_webpage', [$this, 'decorate_page_node'], 10, 2);
    }

    /**
     * This provider contributes to another provider's node, so it never
     * supplies a piece of its own.
     */
    public function can_provide(string $context): bool
    {
        return false;
    }

    public function get_pieces(string $context): array
    {
        return [];
    }

    /**
     * Add subtype-specific properties to the page node.
     *
     * @param mixed  $page    The WebPage piece, per the piece filter contract.
     * @param string $context Current context.
     * @return mixed The piece, decorated when a supported subtype applies.
     */
    public function decorate_page_node($page, string $context = '')
    {
        if (!$page instanceof SchemaPiece) {
            return $page;
        }

        $post = get_post();
        if (!$post) {
            return $page;
        }

        // The subtype is whatever WebPageProvider resolved onto the node's
        // @type, so the two can never disagree about which page this is.
        $schema_type = '';
        foreach ($page->get_types() as $type) {
            if (in_array($type, self::get_supported_types(), true)) {
                $schema_type = $type;
                break;
            }
        }

        if ($schema_type === '') {
            return $page;
        }

        // No common properties here. url/name/headline/dates/description/image/
        // publisher/isPartOf/author all belong to WebPageProvider, which owns
        // this node — re-setting them would overwrite its values, and in the
        // case of `author` would replace a reference to the #author node with
        // an inline blob, severing the graph link. This provider adds only what
        // is specific to the subtype.
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

        $data = apply_filters('wp_schema_framework_page_type_data', $page->to_array(), $context, $schema_type);
        $page->from_array($data);

        return $page;
    }

    public function get_priority(): int
    {
        return 15; // Higher priority than ArticleProvider
    }

    /**
     * WebPage subtypes this provider decorates. Public so WebPageProvider can
     * append the matching subtype to the page node's @type.
     *
     * @return string[]
     */
    public static function get_supported_types(): array
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
    /**
     * Set ContactPage specific properties
     */
    private function set_contact_page_properties(SchemaPiece $page, \WP_Post $post): void
    {
        // Add organization reference as contact point provider
        $page->add_reference('contactPoint', SchemaIds::organization_id());
        
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
        // Reference the organization this page is about — unless something
        // already claimed `about`. A consumer filtering wp_schema_framework_
        // webpage_data may have pointed the page at a specific entity (e.g. a
        // primary storefront location, whose business data lives on the
        // organization node); overwriting that would discard a deliberate
        // decision made with more context than this provider has.
        if (!$page->has('about')) {
            $page->add_reference('about', SchemaIds::organization_id());
        }

        // Add main entity reference
        $page->set('mainEntity', [
            '@id' => SchemaIds::organization_id(),
        ]);
    }
    
    /**
     * Set PrivacyPolicyPage specific properties
     */
    private function set_privacy_policy_properties(SchemaPiece $page, \WP_Post $post): void
    {
        // Mark as accessible for free
        $page->set('isAccessibleForFree', true);

        // WebPageProvider already sets inLanguage from get_bloginfo('language');
        // only fill it when nothing has.
        if (!$page->has('inLanguage')) {
            $page->set('inLanguage', get_locale());
        }

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

        // WebPageProvider already sets inLanguage from get_bloginfo('language');
        // only fill it when nothing has.
        if (!$page->has('inLanguage')) {
            $page->set('inLanguage', get_locale());
        }

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
        
        // Mark as part of website — WebPageProvider already sets this to the
        // same node, so only fill it when nothing has.
        if (!$page->has('isPartOf')) {
            $page->add_reference('isPartOf', SchemaIds::website_id());
        }
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
        $faq_items = apply_filters('wp_schema_framework_faq_items', [], $post->ID);
        
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
        $collection_items = apply_filters('wp_schema_framework_collection_items', [], $post->ID);
        
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
        $gallery_items = apply_filters('wp_schema_framework_gallery_items', [], $post->ID);
        
        if (!empty($gallery_items)) {
            $page->set('hasPart', $gallery_items);
        }
        
        // Add image count if available
        $image_count = apply_filters('wp_schema_framework_gallery_image_count', 0, $post->ID);
        if ($image_count > 0) {
            $page->set('numberOfItems', $image_count);
        }
    }
}