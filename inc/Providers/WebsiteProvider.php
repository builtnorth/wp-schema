<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Providers;

use BuiltNorth\Schema\Contracts\SchemaProviderInterface;
use BuiltNorth\Schema\Graph\SchemaPiece;

/**
 * Website Provider
 * 
 * Provides WebSite schema for the website.
 * 
 * @since 3.0.0
 */
class WebsiteProvider implements SchemaProviderInterface
{
    public function can_provide(string $context): bool
    {
        // Website appears on every page
        return true;
    }
    
    public function get_pieces(string $context): array
    {
        $website = new SchemaPiece('#website', 'WebSite');
        
        // Basic website data
        $website
            ->set('name', get_bloginfo('name'))
            ->set('url', home_url('/'))
            ->add_reference('publisher', '#organization');
        
        // Add description if available
        $description = get_bloginfo('description');
        if ($description) {
            $website->set('description', $description);
        }
        
        // Add search action for home/archive contexts
        if (in_array($context, ['home', 'archive'], true)) {
            $search_url = home_url('/?s={search_term_string}');
            $website->set('potentialAction', [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $search_url,
                ],
                'query-input' => 'required name=search_term_string',
            ]);
        }
        
        // Allow filtering of website data
        $data = apply_filters('wp_schema_website_data', $website->to_array(), $context);
        $website->from_array($data);
        
        return [$website];
    }
    
    public function get_priority(): int
    {
        return 5; // High priority - foundational piece
    }
}