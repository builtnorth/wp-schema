<?php

namespace BuiltNorth\Schema\Defaults;

/**
 * Polaris Blocks Integration
 * 
 * Provides automatic schema data generation for Polaris Blocks plugin blocks.
 * Note: Schema types are determined by post type, not by blocks.
 */
class PolarisBlocksIntegration extends BaseIntegration
{
    /**
     * Integration name
     *
     * @var string
     */
    protected static $integration_name = 'polaris_blocks';

    /**
     * Register WordPress hooks for Polaris Blocks integration
     *
     * @return void
     */
    protected static function register_hooks()
    {
        // Provide schema data for Polaris blocks
        add_filter('wp_schema_data_for_block', [self::class, 'provide_block_data'], 10, 4);
    }

    /**
     * Provide schema data for Polaris blocks
     *
     * @param array|null $custom_data Custom data
     * @param array $block Block data
     * @param string $schema_type Schema type
     * @param array $options Generation options
     * @return array|null Schema data
     */
    public static function provide_block_data($custom_data, $block, $schema_type, $options)
    {
        $block_name = $block['blockName'] ?? '';
        $attrs = $block['attrs'] ?? [];
        $content = $block['innerContent'][0] ?? '';

        switch ($block_name) {
            case 'polaris/accordion':
                return self::get_accordion_data($attrs, $content, $schema_type);
            case 'polaris/map':
                return self::get_map_data($attrs, $content, $schema_type);
            case 'polaris/contact-information':
                return self::get_contact_data($attrs, $content, $schema_type);
            case 'polaris/social-media':
                return self::get_social_media_data($attrs, $content, $schema_type);
            case 'polaris/business-hours':
                return self::get_business_hours_data($attrs, $content, $schema_type);
            case 'polaris/breadcrumbs':
                return self::get_breadcrumbs_data($attrs, $content, $schema_type);
            case 'polaris/price-card':
                return self::get_price_card_data($attrs, $content, $schema_type);
            case 'polaris/post-feed':
                return self::get_post_feed_data($attrs, $content, $schema_type);
            case 'polaris/taxonomy-feed':
                return self::get_taxonomy_feed_data($attrs, $content, $schema_type);
            case 'polaris/features':
                return self::get_features_data($attrs, $content, $schema_type);
            case 'polaris/image-gallery-slider':
                return self::get_image_gallery_data($attrs, $content, $schema_type);
            case 'polaris/featured-image':
                return self::get_featured_image_data($attrs, $content, $schema_type);
            case 'polaris/meta-logo':
                return self::get_meta_logo_data($attrs, $content, $schema_type);
            default:
                return $custom_data;
        }
    }





    /**
     * Get accordion/FAQ data
     *
     * @param array $attrs Block attributes
     * @param string $content Block content
     * @param string $schema_type Schema type
     * @return array|null FAQ data
     */
    private static function get_accordion_data($attrs, $content, $schema_type)
    {
        // Only provide FAQ data if the schema type supports it
        if ($schema_type !== 'FAQPage' && $schema_type !== 'WebPage' && $schema_type !== 'Article') {
            return null;
        }

        // Parse accordion items from content
        preg_match_all('/<div[^>]*class="[^"]*accordion-item[^"]*"[^>]*>(.*?)<\/div>/s', $content, $item_matches);
        
        $faq_items = [];
        foreach ($item_matches[1] ?? [] as $item_content) {
            // Extract question (title)
            preg_match('/<h[1-6][^>]*class="[^"]*accordion-item__title[^"]*"[^>]*>(.*?)<\/h[1-6]>/s', $item_content, $title_match);
            
            // Extract answer (content)
            preg_match('/<div[^>]*class="[^"]*accordion-item__content[^"]*"[^>]*>(.*?)<\/div>/s', $item_content, $content_match);
            
            if ($title_match[1] && $content_match[1]) {
                $faq_items[] = [
                    '@type' => 'Question',
                    'name' => wp_strip_all_tags($title_match[1]),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => wp_strip_all_tags($content_match[1])
                    ]
                ];
            }
        }

        if (empty($faq_items)) {
            return null;
        }

        return [
            'mainEntity' => $faq_items
        ];
    }

    /**
     * Get map/place data
     *
     * @param array $attrs Block attributes
     * @param string $content Block content
     * @param string $schema_type Schema type
     * @return array|null Place data
     */
    private static function get_map_data($attrs, $content, $schema_type)
    {
        // Only provide place data if the schema type supports it
        if ($schema_type !== 'Place' && $schema_type !== 'LocalBusiness' && $schema_type !== 'Organization') {
            return null;
        }

        // For block-level map data, we'll provide basic structure
        // The actual business data will be handled by PolarisOrganizationIntegration
        $place_data = [
            'name' => get_bloginfo('name')
            // Note: Address, telephone, and email will be provided by PolarisOrganizationIntegration
            // for organization schemas, so we don't duplicate them here
        ];

        return $place_data;
    }

    /**
     * Get contact information data
     *
     * @param array $attrs Block attributes
     * @param string $content Block content
     * @param string $schema_type Schema type
     * @return array|null Contact data
     */
    private static function get_contact_data($attrs, $content, $schema_type)
    {
        // Only provide contact data if the schema type supports it
        if ($schema_type !== 'ContactPoint' && $schema_type !== 'Organization' && $schema_type !== 'LocalBusiness') {
            return null;
        }

        // For block-level contact data, we'll provide basic structure
        // The actual business data will be handled by PolarisOrganizationIntegration
        $contact_data = [
            '@type' => 'ContactPoint',
            'contactType' => 'customer service'
        ];

        if ($attrs['name'] ?? false) {
            $contact_data['name'] = get_bloginfo('name');
        }

        // Note: Email, phone, and address will be provided by PolarisOrganizationIntegration
        // for organization schemas, so we don't duplicate them here

        return $contact_data;
    }

    /**
     * Get social media data
     *
     * @param array $attrs Block attributes
     * @param string $content Block content
     * @param string $schema_type Schema type
     * @return array|null Social media data
     */
    private static function get_social_media_data($attrs, $content, $schema_type)
    {
        // Only provide social media data if the schema type supports it
        if ($schema_type !== 'Organization' && $schema_type !== 'Person' && $schema_type !== 'LocalBusiness') {
            return null;
        }

        // For block-level social media data, we'll provide basic structure
        // The actual business social data will be handled by PolarisOrganizationIntegration
        return [
            'name' => get_bloginfo('name')
            // Note: sameAs will be provided by PolarisOrganizationIntegration
            // for organization schemas, so we don't duplicate it here
        ];
    }

    /**
     * Get business hours data
     *
     * @param array $attrs Block attributes
     * @param string $content Block content
     * @param string $schema_type Schema type
     * @return array|null Business hours data
     */
    private static function get_business_hours_data($attrs, $content, $schema_type)
    {
        // Only provide business hours data if the schema type supports it
        if ($schema_type !== 'OpeningHoursSpecification' && $schema_type !== 'LocalBusiness' && $schema_type !== 'Organization') {
            return null;
        }

        // For block-level business hours data, we'll provide basic structure
        // The actual business hours will be handled by PolarisOrganizationIntegration
        return [
            '@type' => 'OpeningHoursSpecification'
            // Note: openingHours will be provided by PolarisOrganizationIntegration
            // for organization schemas, so we don't duplicate it here
        ];
    }

    /**
     * Get breadcrumbs data
     *
     * @param array $attrs Block attributes
     * @param string $content Block content
     * @param string $schema_type Schema type
     * @return array|null Breadcrumbs data
     */
    private static function get_breadcrumbs_data($attrs, $content, $schema_type)
    {
        // Only provide breadcrumbs data if the schema type supports it
        if ($schema_type !== 'BreadcrumbList' && $schema_type !== 'WebPage' && $schema_type !== 'Article') {
            return null;
        }

        // Parse breadcrumb items from content
        preg_match_all('/<a[^>]*>(.*?)<\/a>/s', $content, $link_matches);
        
        $breadcrumb_items = [];
        foreach ($link_matches[1] ?? [] as $index => $link_text) {
            $breadcrumb_items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => wp_strip_all_tags($link_text)
            ];
        }

        if (empty($breadcrumb_items)) {
            return null;
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumb_items
        ];
    }

    /**
     * Get price card data
     *
     * @param array $attrs Block attributes
     * @param string $content Block content
     * @param string $schema_type Schema type
     * @return array|null Price card data
     */
    private static function get_price_card_data($attrs, $content, $schema_type)
    {
        // Only provide offer data if the schema type supports it
        if ($schema_type !== 'Offer' && $schema_type !== 'Product' && $schema_type !== 'Service') {
            return null;
        }

        // Extract price information from content
        preg_match('/<span[^>]*class="[^"]*price[^"]*"[^>]*>(.*?)<\/span>/s', $content, $price_match);
        preg_match('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/s', $content, $title_match);

        if (!$price_match[1] && !$title_match[1]) {
            return null;
        }

        return [
            '@type' => 'Offer',
            'name' => $title_match[1] ?? 'Product',
            'price' => $price_match[1] ?? '',
            'priceCurrency' => 'USD'
        ];
    }

    /**
     * Get post feed data
     *
     * @param array $attrs Block attributes
     * @param string $content Block content
     * @param string $schema_type Schema type
     * @return array|null Post feed data
     */
    private static function get_post_feed_data($attrs, $content, $schema_type)
    {
        // Only provide item list data if the schema type supports it
        if ($schema_type !== 'ItemList' && $schema_type !== 'WebPage' && $schema_type !== 'Article') {
            return null;
        }

        // Parse post items from content
        preg_match_all('/<article[^>]*>(.*?)<\/article>/s', $content, $article_matches);
        
        $list_items = [];
        foreach ($article_matches[1] ?? [] as $index => $article_content) {
            preg_match('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/s', $article_content, $title_match);
            
            $list_items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $title_match[1] ?? 'Post'
            ];
        }

        if (empty($list_items)) {
            return null;
        }

        return [
            '@type' => 'ItemList',
            'itemListElement' => $list_items
        ];
    }

    /**
     * Get taxonomy feed data
     *
     * @param array $attrs Block attributes
     * @param string $content Block content
     * @param string $schema_type Schema type
     * @return array|null Taxonomy feed data
     */
    private static function get_taxonomy_feed_data($attrs, $content, $schema_type)
    {
        // Only provide item list data if the schema type supports it
        if ($schema_type !== 'ItemList' && $schema_type !== 'WebPage' && $schema_type !== 'Article') {
            return null;
        }

        // Parse taxonomy items from content
        preg_match_all('/<a[^>]*>(.*?)<\/a>/s', $content, $link_matches);
        
        $list_items = [];
        foreach ($link_matches[1] ?? [] as $index => $link_text) {
            $list_items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => wp_strip_all_tags($link_text)
            ];
        }

        if (empty($list_items)) {
            return null;
        }

        return [
            '@type' => 'ItemList',
            'itemListElement' => $list_items
        ];
    }

    /**
     * Get features data
     *
     * @param array $attrs Block attributes
     * @param string $content Block content
     * @param string $schema_type Schema type
     * @return array|null Features data
     */
    private static function get_features_data($attrs, $content, $schema_type)
    {
        // Only provide item list data if the schema type supports it
        if ($schema_type !== 'ItemList' && $schema_type !== 'WebPage' && $schema_type !== 'Article') {
            return null;
        }

        // Parse feature items from content
        preg_match_all('/<div[^>]*class="[^"]*feature[^"]*"[^>]*>(.*?)<\/div>/s', $content, $feature_matches);
        
        $list_items = [];
        foreach ($feature_matches[1] ?? [] as $index => $feature_content) {
            preg_match('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/s', $feature_content, $title_match);
            
            $list_items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $title_match[1] ?? 'Feature'
            ];
        }

        if (empty($list_items)) {
            return null;
        }

        return [
            '@type' => 'ItemList',
            'itemListElement' => $list_items
        ];
    }

    /**
     * Get image gallery data
     *
     * @param array $attrs Block attributes
     * @param string $content Block content
     * @param string $schema_type Schema type
     * @return array|null Image gallery data
     */
    private static function get_image_gallery_data($attrs, $content, $schema_type)
    {
        // Only provide image gallery data if the schema type supports it
        if ($schema_type !== 'ImageGallery' && $schema_type !== 'WebPage' && $schema_type !== 'Article') {
            return null;
        }

        // Parse images from content
        preg_match_all('/<img[^>]*src="([^"]*)"[^>]*>/s', $content, $image_matches);
        
        $images = [];
        foreach ($image_matches[1] ?? [] as $image_url) {
            $images[] = [
                '@type' => 'ImageObject',
                'url' => $image_url
            ];
        }

        if (empty($images)) {
            return null;
        }

        return [
            '@type' => 'ImageGallery',
            'image' => $images
        ];
    }

    /**
     * Get featured image data
     *
     * @param array $attrs Block attributes
     * @param string $content Block content
     * @param string $schema_type Schema type
     * @return array|null Featured image data
     */
    private static function get_featured_image_data($attrs, $content, $schema_type)
    {
        // Only provide image data if the schema type supports it
        if ($schema_type !== 'ImageObject' && $schema_type !== 'WebPage' && $schema_type !== 'Article') {
            return null;
        }

        // Extract image URL from content
        preg_match('/<img[^>]*src="([^"]*)"[^>]*>/s', $content, $image_match);
        
        if ($image_match[1]) {
            return [
                '@type' => 'ImageObject',
                'url' => $image_match[1]
            ];
        }

        return null;
    }

    /**
     * Get meta logo data
     *
     * @param array $attrs Block attributes
     * @param string $content Block content
     * @param string $schema_type Schema type
     * @return array|null Meta logo data
     */
    private static function get_meta_logo_data($attrs, $content, $schema_type)
    {
        // Only provide image data if the schema type supports it
        if ($schema_type !== 'ImageObject' && $schema_type !== 'Organization' && $schema_type !== 'LocalBusiness') {
            return null;
        }

        // Get logo from WordPress customizer
        $logo_id = get_theme_mod('custom_logo');
        if ($logo_id) {
            $logo_url = wp_get_attachment_image_url($logo_id, 'full');
            return [
                '@type' => 'ImageObject',
                'url' => $logo_url
            ];
        }

        return null;
    }

    /**
     * Check if Polaris Blocks is available
     *
     * @return bool
     */
    public static function is_available()
    {
        return class_exists('PolarisBlocks') || function_exists('polaris_blocks_init');
    }

    /**
     * Get integration description
     *
     * @return string
     */
    public static function get_description()
    {
        return 'Schema data for Polaris Blocks plugin blocks (accordion, map, contact info, social media, etc.)';
    }

    /**
     * Get supported schema types
     *
     * @return array
     */
    public static function get_supported_schema_types()
    {
        return ['FAQPage', 'Place', 'ContactPoint', 'Organization', 'OpeningHoursSpecification', 'BreadcrumbList', 'Offer', 'ItemList', 'ImageGallery', 'ImageObject', 'WebPage', 'Article', 'LocalBusiness', 'Person'];
    }
} 