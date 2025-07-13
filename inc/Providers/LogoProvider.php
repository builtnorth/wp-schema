<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Providers;

use BuiltNorth\Schema\Contracts\SchemaProviderInterface;
use BuiltNorth\Schema\Graph\SchemaPiece;

/**
 * Logo Provider
 * 
 * Adds logo to Organization schema using WordPress core's custom logo feature.
 * 
 * @since 3.0.0
 */
class LogoProvider implements SchemaProviderInterface
{
    public function __construct()
    {
        // Add filter on construction to ensure it's always registered
        add_filter('wp_schema_organization_data', [$this, 'add_logo_to_organization'], 20, 2);
    }
    
    public function can_provide(string $context): bool
    {
        // This provider modifies organization data on all pages
        return true;
    }
    
    public function get_pieces(string $context): array
    {
        // This provider doesn't create new pieces
        return [];
    }
    
    public function get_priority(): int
    {
        return 20; // Run after OrganizationProvider
    }
    
    /**
     * Add logo to organization data from WordPress core
     */
    public function add_logo_to_organization(array $data, string $context): array
    {
        $logo_id = null;
        
        // Try site_logo first (FSE themes)
        $site_logo = get_option('site_logo');
        if ($site_logo) {
            $logo_id = (int) $site_logo;
        }
        
        // Fall back to custom_logo (classic themes)
        if (!$logo_id && current_theme_supports('custom-logo')) {
            $custom_logo = get_theme_mod('custom_logo');
            if ($custom_logo) {
                $logo_id = (int) $custom_logo;
            }
        }
        
        if (!$logo_id) {
            return $data;
        }
        
        // Get logo URL
        $logo_url = wp_get_attachment_image_url($logo_id, 'full');
        
        if (!$logo_url) {
            return $data;
        }
        
        // Get logo metadata
        $logo_metadata = wp_get_attachment_metadata($logo_id);
        
        // Add logo as ImageObject
        $logo = [
            '@type' => 'ImageObject',
            'url' => $logo_url,
        ];
        
        // Add dimensions if available
        if (!empty($logo_metadata['width']) && !empty($logo_metadata['height'])) {
            $logo['width'] = $logo_metadata['width'];
            $logo['height'] = $logo_metadata['height'];
        }
        
        // Add alt text if available
        $alt_text = get_post_meta($logo_id, '_wp_attachment_image_alt', true);
        if ($alt_text) {
            $logo['caption'] = $alt_text;
        }
        
        $data['logo'] = $logo;
        
        return $data;
    }
}