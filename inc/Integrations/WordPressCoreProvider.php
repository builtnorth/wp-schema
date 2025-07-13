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
        
        // Add navigation reference if site navigation exists
        if ($this->has_site_navigation()) {
            $piece['hasPart'] = [['@id' => home_url('/#navigation')]];
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
        
        // Add mainEntity relationship for organization pages
        $main_entity_id = $this->get_main_entity_for_context($context);
        if ($main_entity_id) {
            $piece['mainEntity'] = ['@id' => $main_entity_id];
        }
        
        // Add breadcrumb reference if breadcrumb schema exists
        // Note: External plugins can provide breadcrumb schema via hooks
        if ($this->has_breadcrumb_schema()) {
            $piece['breadcrumb'] = ['@id' => home_url('/#breadcrumb')];
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
    
    /**
     * Get main entity @id for the current context
     */
    private function get_main_entity_for_context(string $context): ?string
    {
        switch ($context) {
            case 'home':
                // For homepage, the main entity is typically the organization
                // Check if we have organization data (Polaris or other)
                if ($this->has_organization_data()) {
                    return home_url('/#organization');
                }
                break;
                
            case 'singular':
                // For singular pages, the main entity could be:
                // - Article (for blog posts)
                // - Product (for WooCommerce products)
                // - Event (for event posts)
                // - Person (for author/team pages)
                $post_type = get_post_type();
                $post_id = get_the_ID();
                
                switch ($post_type) {
                    case 'post':
                        return get_permalink($post_id) . '#article';
                    case 'product':
                        return get_permalink($post_id) . '#product';
                    case 'event':
                        return get_permalink($post_id) . '#event';
                    case 'person':
                    case 'team':
                        return get_permalink($post_id) . '#person';
                    case 'page':
                        // For pages, check if it's a special page type
                        if ($this->is_about_page($post_id)) {
                            return home_url('/#organization');
                        }
                        if ($this->is_contact_page($post_id)) {
                            return home_url('/#organization');
                        }
                        break;
                }
                break;
                
            case 'archive':
                // For archive pages, main entity is usually the website itself
                return home_url('/#website');
        }
        
        return null;
    }
    
    /**
     * Check if organization data exists (from Polaris or other sources)
     */
    private function has_organization_data(): bool
    {
        // Check for Polaris organization data
        $polaris_org = get_option('polaris_organization', []);
        if (!empty($polaris_org['information']['name'])) {
            return true;
        }
        
        // Could check for other organization data sources here
        return false;
    }
    
    /**
     * Check if this is an about page
     */
    private function is_about_page(int $post_id): bool
    {
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }
        
        $slug = $post->post_name;
        $title = strtolower($post->post_title);
        
        $about_indicators = ['about', 'about-us', 'who-we-are', 'our-story', 'company'];
        
        foreach ($about_indicators as $indicator) {
            if (strpos($slug, $indicator) !== false || strpos($title, $indicator) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if this is a contact page
     */
    private function is_contact_page(int $post_id): bool
    {
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }
        
        $slug = $post->post_name;
        $title = strtolower($post->post_title);
        
        $contact_indicators = ['contact', 'contact-us', 'get-in-touch', 'reach-out'];
        
        foreach ($contact_indicators as $indicator) {
            if (strpos($slug, $indicator) !== false || strpos($title, $indicator) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if breadcrumb schema is being provided
     */
    private function has_breadcrumb_schema(): bool
    {
        // Check if any breadcrumb schema will be provided via filters
        // External plugins can hook into 'wp_schema_pieces' to provide breadcrumb schema
        $test_pieces = apply_filters('wp_schema_pieces', [], 'singular', []);
        
        foreach ($test_pieces as $piece) {
            if (isset($piece['@type']) && $piece['@type'] === 'BreadcrumbList') {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if site navigation schema is being provided
     */
    private function has_site_navigation(): bool
    {
        // Check if Core navigation provider can provide schema
        if (class_exists('BuiltNorth\Schema\Integrations\CoreNavigationProvider')) {
            $provider = new \BuiltNorth\Schema\Integrations\CoreNavigationProvider();
            return $provider->can_provide('singular');
        }
        
        return false;
    }
    
}