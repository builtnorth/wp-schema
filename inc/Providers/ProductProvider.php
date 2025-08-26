<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Providers;

use BuiltNorth\WPSchema\Contracts\SchemaProviderInterface;
use BuiltNorth\WPSchema\Graph\SchemaPiece;

/**
 * Product Provider
 * 
 * Provides Product schema with smart detection for popular e-commerce plugins.
 * Supports WooCommerce, Easy Digital Downloads, and custom integrations.
 * 
 * @since 3.0.0
 */
class ProductProvider implements SchemaProviderInterface
{
    public function can_provide(string $context): bool
    {
        if ($context !== 'singular') {
            return false;
        }
        
        // Auto-detect WooCommerce products
        if (class_exists('WooCommerce') && get_post_type() === 'product') {
            return true;
        }
        
        // Auto-detect Easy Digital Downloads
        if (class_exists('Easy_Digital_Downloads') && get_post_type() === 'download') {
            return true;
        }
        
        // Auto-detect BigCommerce
        if (function_exists('bigcommerce') && get_post_type() === 'bigcommerce_product') {
            return true;
        }
        
        // Allow custom integration via filter
        return apply_filters('wp_schema_framework_is_product', false, get_the_ID(), $context);
    }
    
    public function get_pieces(string $context): array
    {
        $product_data = $this->get_product_data();
        
        if (empty($product_data)) {
            return [];
        }
        
        // Create product schema piece
        $product = new SchemaPiece('#product', 'Product');
        
        // Set basic product data
        $product->set('name', $product_data['name'] ?? get_the_title());
        
        if (!empty($product_data['description'])) {
            $product->set('description', wp_strip_all_tags($product_data['description']));
        }
        
        if (!empty($product_data['sku'])) {
            $product->set('sku', $product_data['sku']);
        }
        
        if (!empty($product_data['mpn'])) {
            $product->set('mpn', $product_data['mpn']);
        }
        
        if (!empty($product_data['gtin'])) {
            $product->set('gtin', $product_data['gtin']);
        }
        
        // Add image
        if (!empty($product_data['image'])) {
            $product->set('image', [
                '@type' => 'ImageObject',
                'url' => $product_data['image']
            ]);
        }
        
        // Add brand
        if (!empty($product_data['brand'])) {
            $product->set('brand', [
                '@type' => 'Brand',
                'name' => $product_data['brand']
            ]);
        }
        
        // Add offer (price, availability, etc.)
        $offer = $this->build_offer($product_data);
        if (!empty($offer)) {
            $product->set('offers', $offer);
        }
        
        // Add aggregate rating if reviews exist
        if (!empty($product_data['aggregateRating'])) {
            $product->set('aggregateRating', [
                '@type' => 'AggregateRating',
                'ratingValue' => $product_data['aggregateRating']['ratingValue'],
                'reviewCount' => $product_data['aggregateRating']['reviewCount'],
                'bestRating' => $product_data['aggregateRating']['bestRating'] ?? 5,
                'worstRating' => $product_data['aggregateRating']['worstRating'] ?? 1,
            ]);
        }
        
        // Add review if provided
        if (!empty($product_data['review'])) {
            $reviews = [];
            foreach ($product_data['review'] as $review_data) {
                $reviews[] = [
                    '@type' => 'Review',
                    'reviewRating' => [
                        '@type' => 'Rating',
                        'ratingValue' => $review_data['ratingValue'] ?? 5,
                        'bestRating' => $review_data['bestRating'] ?? 5,
                    ],
                    'author' => [
                        '@type' => 'Person',
                        'name' => $review_data['author'] ?? 'Anonymous',
                    ],
                    'reviewBody' => $review_data['reviewBody'] ?? '',
                    'datePublished' => $review_data['datePublished'] ?? '',
                ];
            }
            $product->set('review', $reviews);
        }
        
        // Allow filtering of product data
        $data = apply_filters('wp_schema_framework_product_data', $product->to_array(), $context, get_the_ID());
        $product->from_array($data);
        
        return [$product];
    }
    
    public function get_priority(): int
    {
        return 20; // Same as article
    }
    
    /**
     * Get product data from various sources
     */
    private function get_product_data(): array
    {
        // Try custom filter first (highest priority)
        $custom_data = apply_filters('wp_schema_framework_get_product_data', null, get_the_ID());
        if (is_array($custom_data)) {
            return $custom_data;
        }
        
        // Auto-detect WooCommerce
        if (class_exists('WooCommerce') && function_exists('wc_get_product')) {
            $data = $this->get_woocommerce_data();
            if (!empty($data)) {
                return $data;
            }
        }
        
        // Auto-detect Easy Digital Downloads
        if (class_exists('Easy_Digital_Downloads') && function_exists('edd_get_download')) {
            $data = $this->get_edd_data();
            if (!empty($data)) {
                return $data;
            }
        }
        
        // Auto-detect BigCommerce
        if (function_exists('bigcommerce') && function_exists('bigcommerce_get_product')) {
            $data = $this->get_bigcommerce_data();
            if (!empty($data)) {
                return $data;
            }
        }
        
        return [];
    }
    
    /**
     * Get WooCommerce product data
     */
    private function get_woocommerce_data(): array
    {
        $product = wc_get_product(get_the_ID());
        if (!$product) {
            return [];
        }
        
        $data = [
            'name' => $product->get_name(),
            'description' => $product->get_short_description() ?: $product->get_description(),
            'sku' => $product->get_sku(),
            'url' => $product->get_permalink(),
        ];
        
        // Add price and currency
        if ($product->get_price()) {
            $data['price'] = $product->get_price();
            $data['currency'] = get_woocommerce_currency();
            $data['availability'] = $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
            
            // Add sale prices if applicable
            if ($product->is_on_sale() && $product->get_regular_price()) {
                $data['regular_price'] = $product->get_regular_price();
                
                // Add valid dates for sale
                if ($product->get_date_on_sale_from()) {
                    $data['priceValidFrom'] = $product->get_date_on_sale_from()->format('c');
                }
                if ($product->get_date_on_sale_to()) {
                    $data['priceValidUntil'] = $product->get_date_on_sale_to()->format('c');
                }
            }
        }
        
        // Add image
        if ($image_id = $product->get_image_id()) {
            $data['image'] = wp_get_attachment_url($image_id);
        }
        
        // Add reviews/ratings
        if ($product->get_review_count() > 0 && $product->get_average_rating() > 0) {
            $data['aggregateRating'] = [
                'ratingValue' => $product->get_average_rating(),
                'reviewCount' => $product->get_review_count(),
            ];
        }
        
        // Add brand if available (could be attribute or taxonomy)
        $brand = $product->get_attribute('pa_brand') ?: $product->get_attribute('brand');
        if ($brand) {
            $data['brand'] = $brand;
        }
        
        // Add GTIN/MPN if available
        $gtin = $product->get_attribute('gtin') ?: get_post_meta($product->get_id(), '_gtin', true);
        if ($gtin) {
            $data['gtin'] = $gtin;
        }
        
        $mpn = $product->get_attribute('mpn') ?: get_post_meta($product->get_id(), '_mpn', true);
        if ($mpn) {
            $data['mpn'] = $mpn;
        }
        
        // Add seller/vendor for marketplace sites
        if (function_exists('dokan')) {
            $seller_id = get_post_field('post_author', $product->get_id());
            $store_info = dokan_get_store_info($seller_id);
            if (!empty($store_info['store_name'])) {
                $data['seller'] = $store_info['store_name'];
            }
        }
        
        return apply_filters('wp_schema_framework_woocommerce_product_data', $data, $product);
    }
    
    /**
     * Get Easy Digital Downloads product data
     */
    private function get_edd_data(): array
    {
        $download = edd_get_download(get_the_ID());
        if (!$download) {
            return [];
        }
        
        $data = [
            'name' => $download->post_title,
            'description' => $download->post_excerpt ?: wp_trim_words($download->post_content, 50),
            'url' => get_permalink($download->ID),
        ];
        
        // Add price
        if (edd_has_variable_prices($download->ID)) {
            $prices = edd_get_variable_prices($download->ID);
            if (!empty($prices)) {
                $lowest_price = min(array_column($prices, 'amount'));
                $data['price'] = $lowest_price;
            }
        } else {
            $data['price'] = edd_get_download_price($download->ID);
        }
        
        if (!empty($data['price'])) {
            $data['currency'] = edd_get_currency();
            $data['availability'] = 'https://schema.org/InStock'; // Digital products always in stock
        }
        
        // Add image
        if (has_post_thumbnail($download->ID)) {
            $data['image'] = get_the_post_thumbnail_url($download->ID, 'full');
        }
        
        // Add sales count as review proxy (EDD doesn't have built-in reviews)
        $sales = edd_get_download_sales_stats($download->ID);
        if ($sales > 0) {
            // Use sales as a confidence indicator (not real reviews)
            $data['aggregateRating'] = [
                'ratingValue' => 4.5, // Default high rating for products with sales
                'reviewCount' => $sales,
            ];
        }
        
        return apply_filters('wp_schema_framework_edd_product_data', $data, $download);
    }
    
    /**
     * Get BigCommerce product data
     */
    private function get_bigcommerce_data(): array
    {
        // BigCommerce implementation would go here
        // Keeping it as a stub for now since it's less common
        
        $product_id = get_the_ID();
        $data = [
            'name' => get_the_title(),
            'description' => get_the_excerpt(),
            'url' => get_permalink(),
        ];
        
        return apply_filters('wp_schema_framework_bigcommerce_product_data', $data, $product_id);
    }
    
    /**
     * Build offer schema from product data
     */
    private function build_offer(array $product_data): array
    {
        if (empty($product_data['price'])) {
            return [];
        }
        
        $offer = [
            '@type' => 'Offer',
            'price' => $product_data['price'],
            'priceCurrency' => $product_data['currency'] ?? 'USD',
            'availability' => $product_data['availability'] ?? 'https://schema.org/InStock',
            'url' => $product_data['url'] ?? get_permalink(),
        ];
        
        // Add sale price if different from regular
        if (!empty($product_data['regular_price']) && $product_data['regular_price'] != $product_data['price']) {
            $offer['priceSpecification'] = [
                '@type' => 'PriceSpecification',
                'price' => $product_data['price'],
                'priceCurrency' => $product_data['currency'] ?? 'USD',
            ];
        }
        
        // Add price valid dates
        if (!empty($product_data['priceValidFrom'])) {
            $offer['priceValidFrom'] = $product_data['priceValidFrom'];
        }
        if (!empty($product_data['priceValidUntil'])) {
            $offer['priceValidUntil'] = $product_data['priceValidUntil'];
        }
        
        // Add seller if available
        if (!empty($product_data['seller'])) {
            $offer['seller'] = [
                '@type' => 'Organization',
                'name' => $product_data['seller'],
            ];
        } else {
            // Default to site organization
            $offer['seller'] = ['@id' => '#organization'];
        }
        
        return $offer;
    }
}