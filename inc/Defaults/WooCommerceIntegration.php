<?php

namespace BuiltNorth\Schema\Defaults;

/**
 * WooCommerce Integration
 * 
 * Provides automatic schema data generation for WooCommerce products, reviews, and organization data.
 * Note: Schema types are determined by post type, not by blocks.
 */
class WooCommerceIntegration extends BaseIntegration
{
    /**
     * Integration name
     *
     * @var string
     */
    protected static $integration_name = 'woocommerce';

    /**
     * Register WordPress hooks for WooCommerce integration
     *
     * @return void
     */
    protected static function register_hooks()
    {
        // Override schema type for WooCommerce products
        add_filter('wp_schema_type_for_post', [self::class, 'override_schema_type'], 10, 3);
        
        // Provide schema data for WooCommerce products
        add_filter('wp_schema_data_for_post', [self::class, 'provide_product_data'], 10, 4);
        
        // Provide schema data for WooCommerce blocks
        add_filter('wp_schema_data_for_block', [self::class, 'provide_block_data'], 10, 4);
        
        // Add organization schema for WooCommerce shop
        add_filter('wp_schema_final_schema', [self::class, 'add_organization_schema'], 10, 4);
    }

    /**
     * Override schema type for WooCommerce products
     *
     * @param string|null $schema_type Current schema type
     * @param int $post_id Post ID
     * @param array $options Generation options
     * @return string|null Schema type
     */
    public static function override_schema_type($schema_type, $post_id, $options)
    {
        $post_type = get_post_type($post_id);
        
        if ($post_type === 'product') {
            return 'Product';
        }
        
        return $schema_type;
    }

    /**
     * Provide schema data for WooCommerce products
     *
     * @param array|null $custom_data Custom data
     * @param int $post_id Post ID
     * @param string $schema_type Schema type
     * @param array $options Generation options
     * @return array|null Schema data
     */
    public static function provide_product_data($custom_data, $post_id, $schema_type, $options)
    {
        if ($schema_type !== 'Product' || get_post_type($post_id) !== 'product') {
            return $custom_data;
        }

        $product = wc_get_product($post_id);
        if (!$product) {
            return $custom_data;
        }

        $product_data = [
            'name' => $product->get_name(),
            'description' => $product->get_description(),
            'url' => get_permalink($post_id),
            'sku' => $product->get_sku(),
            'brand' => [
                '@type' => 'Brand',
                'name' => get_bloginfo('name')
            ]
        ];

        // Add image
        $image_id = $product->get_image_id();
        if ($image_id) {
            $product_data['image'] = wp_get_attachment_image_url($image_id, 'full');
        }

        // Add price information
        if ($product->get_price()) {
            $product_data['offers'] = [
                '@type' => 'Offer',
                'price' => $product->get_price(),
                'priceCurrency' => get_woocommerce_currency(),
                'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'url' => get_permalink($post_id)
            ];

            // Add price range for variable products
            if ($product->is_type('variable')) {
                $prices = $product->get_variation_prices();
                if (!empty($prices['price'])) {
                    $min_price = current($prices['price']);
                    $max_price = end($prices['price']);
                    
                    if ($min_price !== $max_price) {
                        $product_data['offers']['priceSpecification'] = [
                            '@type' => 'PriceSpecification',
                            'minPrice' => $min_price,
                            'maxPrice' => $max_price,
                            'priceCurrency' => get_woocommerce_currency()
                        ];
                    }
                }
            }
        }

        // Add rating information
        if ($product->get_average_rating()) {
            $product_data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $product->get_average_rating(),
                'reviewCount' => $product->get_review_count(),
                'bestRating' => 5,
                'worstRating' => 1
            ];
        }

        // Add category information
        $categories = wc_get_product_category_list($post_id, ', ');
        if ($categories) {
            $product_data['category'] = strip_tags($categories);
        }

        return $product_data;
    }

    /**
     * Provide schema data for WooCommerce blocks
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
        
        // Handle product blocks - only provide data if schema type supports it
        if (strpos($block_name, 'woocommerce/') === 0 && $schema_type === 'Product') {
            $attrs = $block['attrs'] ?? [];
            $product_id = $attrs['productId'] ?? 0;
            
            if ($product_id) {
                return self::provide_product_data(null, $product_id, 'Product', $options);
            }
        }

        return $custom_data;
    }

    /**
     * Add organization schema for WooCommerce shop
     *
     * @param array $schema Final schema
     * @param mixed $content Content
     * @param string $type Schema type
     * @param array $options Generation options
     * @return array Modified schema
     */
    public static function add_organization_schema($schema, $content, $type, $options)
    {
        // Add organization schema to product pages
        if ($type === 'Product' && is_product()) {
            $organization_data = [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'url' => home_url('/'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => get_site_icon_url()
                ]
            ];

            // Add contact information if available
            $shop_address = wc_get_page_permalink('shop');
            if ($shop_address) {
                $organization_data['url'] = $shop_address;
            }

            return [$organization_data, $schema];
        }

        return $schema;
    }

    /**
     * Check if WooCommerce is available
     *
     * @return bool
     */
    public static function is_available()
    {
        return class_exists('WooCommerce');
    }

    /**
     * Get integration description
     *
     * @return string
     */
    public static function get_description()
    {
        return 'Schema data for WooCommerce products, reviews, and organization information';
    }

    /**
     * Get supported schema types
     *
     * @return array
     */
    public static function get_supported_schema_types()
    {
        return ['Product', 'Organization', 'Brand', 'Offer', 'AggregateRating'];
    }
} 