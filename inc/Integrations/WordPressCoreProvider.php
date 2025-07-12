<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Integrations;

use BuiltNorth\Schema\Contracts\SchemaProviderInterface;

/**
 * WordPress Core Provider
 * 
 * Provides basic schema from WordPress core data: WebSite and WebPage.
 * Uses only WordPress core functions and data - no external dependencies.
 * 
 * @since 3.0.0
 */
class WordPressCoreProvider implements SchemaProviderInterface
{
    public function can_provide(string $context, array $options = []): bool
    {
        // Always can provide basic site/page schema
        return true;
    }
    
    public function get_pieces(string $context, array $options = []): array
    {
        $pieces = [];
        
        // Always provide WebSite schema
        $pieces[] = $this->build_website_piece();
        
        // Provide WebPage schema for most contexts
        if (in_array($context, ['home', 'singular', 'archive'], true)) {
            $pieces[] = $this->build_webpage_piece($context);
        }
        
        return $pieces;
    }
    
    public function get_priority(): int
    {
        return 10; // Standard priority
    }
    
    /**
     * Build WebSite schema piece
     */
    private function build_website_piece(): array
    {
        $piece = [
            '@type' => 'WebSite',
            '@id' => home_url('/#website'),
            'name' => get_bloginfo('name'),
            'url' => home_url('/'),
        ];
        
        // Add description if available
        $description = get_bloginfo('description');
        if (!empty($description)) {
            $piece['description'] = $description;
        }
        
        // Add search functionality
        $piece['potentialAction'] = [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => home_url('/?s={search_term_string}')
            ],
            'query-input' => 'required name=search_term_string'
        ];
        
        // Add site logo if available
        if (has_custom_logo()) {
            $logo_id = get_theme_mod('custom_logo');
            $logo_url = wp_get_attachment_image_url($logo_id, 'full');
            if ($logo_url) {
                $piece['logo'] = [
                    '@type' => 'ImageObject',
                    'url' => $logo_url
                ];
            }
        }
        
        return $piece;
    }
    
    /**
     * Build WebPage schema piece
     */
    private function build_webpage_piece(string $context): array
    {
        $piece = [
            '@type' => 'WebPage',
            '@id' => $this->get_page_url() . '#webpage',
            'name' => $this->get_page_title($context),
            'url' => $this->get_page_url(),
            'isPartOf' => ['@id' => home_url('/#website')]
        ];
        
        // Add description if available
        $description = $this->get_page_description($context);
        if (!empty($description)) {
            $piece['description'] = $description;
        }
        
        
        return $piece;
    }
    
    /**
     * Get page title based on context
     */
    private function get_page_title(string $context): string
    {
        switch ($context) {
            case 'home':
                return get_bloginfo('name');
            
            case 'singular':
                return get_the_title() ?: get_bloginfo('name');
            
            case 'archive':
                if (is_category()) {
                    return single_cat_title('', false);
                } elseif (is_tag()) {
                    return single_tag_title('', false);
                } elseif (is_author()) {
                    return get_the_author();
                } elseif (is_date()) {
                    return get_the_archive_title();
                } else {
                    return get_the_archive_title();
                }
            
            default:
                return get_bloginfo('name');
        }
    }
    
    /**
     * Get page URL
     */
    private function get_page_url(): string
    {
        if (is_front_page()) {
            return home_url('/');
        }
        
        if (is_singular()) {
            return get_permalink() ?: home_url('/');
        }
        
        if (is_archive()) {
            return get_pagenum_link() ?: home_url('/');
        }
        
        return home_url('/');
    }
    
    /**
     * Get page description based on context
     */
    private function get_page_description(string $context): string
    {
        switch ($context) {
            case 'home':
                return get_bloginfo('description');
            
            case 'singular':
                $excerpt = get_the_excerpt();
                return !empty($excerpt) ? $excerpt : get_bloginfo('description');
            
            case 'archive':
                $description = get_the_archive_description();
                return !empty($description) ? strip_tags($description) : get_bloginfo('description');
            
            default:
                return get_bloginfo('description');
        }
    }
    
}