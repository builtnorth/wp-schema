<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Providers;

use BuiltNorth\Schema\Contracts\SchemaProviderInterface;
use BuiltNorth\Schema\Graph\SchemaPiece;

/**
 * Site Icon Provider
 * 
 * Adds site icon (favicon) to WebSite schema using WordPress core's site icon feature.
 * 
 * @since 3.0.0
 */
class SiteIconProvider implements SchemaProviderInterface
{
    public function __construct()
    {
        // Add filter on construction to ensure it's always registered
        add_filter('wp_schema_website_data', [$this, 'add_icon_to_website'], 20, 2);
    }
    
    public function can_provide(string $context): bool
    {
        // This provider modifies website data on all pages
        return true;
    }
    
    public function get_pieces(string $context): array
    {
        // This provider doesn't create new pieces
        return [];
    }
    
    public function get_priority(): int
    {
        return 20; // Run after WebsiteProvider
    }
    
    /**
     * Add site icon to website data from WordPress core
     */
    public function add_icon_to_website(array $data, string $context): array
    {
        // Get site icon ID
        $site_icon_id = get_option('site_icon');
        
        if (!$site_icon_id) {
            return $data;
        }
        
        // Get icon URL (512x512 is standard for site icons)
        $icon_url = wp_get_attachment_image_url($site_icon_id, [512, 512]);
        
        if (!$icon_url) {
            return $data;
        }
        
        // Add icon as ImageObject
        $data['image'] = [
            '@type' => 'ImageObject',
            'url' => $icon_url,
            'width' => 512,
            'height' => 512,
        ];
        
        return $data;
    }
}