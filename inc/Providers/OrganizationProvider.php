<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Providers;

use BuiltNorth\WPSchema\Contracts\SchemaProviderInterface;
use BuiltNorth\WPSchema\Graph\SchemaPiece;

/**
 * Organization Provider
 * 
 * Provides Organization schema for the website.
 * 
 * @since 3.0.0
 */
class OrganizationProvider implements SchemaProviderInterface
{
    public function can_provide(string $context): bool
    {
        // Organization appears on every page
        return true;
    }
    
    public function get_pieces(string $context): array
    {
        $organization = new SchemaPiece('#organization', 'Organization');
        
        // Basic organization data
        $organization
            ->set('name', get_bloginfo('name'))
            ->set('url', home_url('/'));
        
        // Add description if available
        $description = get_bloginfo('description');
        if ($description) {
            $organization->set('description', $description);
        }
        
        // Allow filtering of organization data
        $data = apply_filters('wp_schema_organization_data', $organization->to_array(), $context);
        $organization->from_array($data);
        
        return [$organization];
    }
    
    public function get_priority(): int
    {
        return 5; // High priority - foundational piece
    }
}