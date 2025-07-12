<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Defaults;

use BuiltNorth\Schema\Contracts\DataProviderInterface;
use BuiltNorth\Schema\Models\ImageObject;

/**
 * WebSite Schema Provider
 * 
 * Provides WebSite schema with search functionality.
 */
class WebSiteProvider implements DataProviderInterface
{
    public function getProviderId(): string { return 'wordpress_website'; }
    public function getPriority(): int { return 10; }
    public function isAvailable(): bool { return true; }
    
    public function canProvide(string $context, array $options = []): bool
    {
        return $context === 'home' || $context === 'taxonomy';
    }
    
    public function provide(string $context, array $options = []): array
    {
        return [
            '@type' => 'WebSite',
            'name' => get_bloginfo('name'),
            'url' => home_url('/'),
            'description' => get_bloginfo('description'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => home_url('/?s={search_term_string}')
                ],
                'query-input' => 'required name=search_term_string'
            ]
        ];
    }
    
    public function getCacheKey(string $context, array $options = []): string
    {
        return "wp_website_{$context}_" . md5(get_bloginfo('name') . get_bloginfo('description'));
    }
    
    public function getSupportedSchemaTypes(): array { return ['WebSite']; }
}

/**
 * WebPage Schema Provider
 * 
 * Provides WebPage schema for all page contexts.
 */
class WebPageProvider implements DataProviderInterface
{
    public function getProviderId(): string { return 'wordpress_webpage'; }
    public function getPriority(): int { return 10; }
    public function isAvailable(): bool { return true; }
    
    public function canProvide(string $context, array $options = []): bool
    {
        return true; // All contexts need WebPage schema
    }
    
    public function provide(string $context, array $options = []): array
    {
        return [
            '@type' => 'WebPage',
            'name' => $this->getPageTitle($context),
            'url' => $this->getPageUrl($context),
            'description' => $this->getPageDescription($context)
        ];
    }
    
    private function getPageTitle(string $context): string
    {
        if (is_singular()) {
            return get_the_title();
        } elseif (is_archive()) {
            return get_the_archive_title();
        }
        return get_bloginfo('name');
    }
    
    private function getPageUrl(string $context): string
    {
        if (is_singular()) {
            return get_permalink();
        } elseif (is_archive()) {
            return get_pagenum_link();
        }
        return home_url('/');
    }
    
    private function getPageDescription(string $context): string
    {
        if (is_singular()) {
            return get_the_excerpt() ?: get_bloginfo('description');
        } elseif (is_archive()) {
            return get_the_archive_description() ?: get_bloginfo('description');
        }
        return get_bloginfo('description');
    }
    
    public function getCacheKey(string $context, array $options = []): string
    {
        $postId = get_queried_object_id();
        return "wp_webpage_{$context}_{$postId}";
    }
    
    public function getSupportedSchemaTypes(): array { return ['WebPage']; }
}

/**
 * Article Schema Provider
 * 
 * Provides Article/BlogPosting schema for posts and custom post types.
 */
class ArticleProvider implements DataProviderInterface
{
    public function getProviderId(): string { return 'wordpress_article'; }
    public function getPriority(): int { return 15; }
    public function isAvailable(): bool { return true; }
    
    public function canProvide(string $context, array $options = []): bool
    {
        return $context === 'singular' && is_singular(['post']);
    }
    
    public function provide(string $context, array $options = []): array
    {
        $post = get_post();
        if (!$post) {
            return [];
        }
        
        $schema = [
            '@type' => $post->post_type === 'post' ? 'BlogPosting' : 'Article',
            'headline' => get_the_title($post),
            'description' => get_the_excerpt($post),
            'url' => get_permalink($post),
            'datePublished' => get_the_date('c', $post),
            'dateModified' => get_the_modified_date('c', $post),
            'author' => [
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', $post->post_author)
            ]
        ];
        
        // Add featured image if available
        $imageId = get_post_thumbnail_id($post->ID);
        if ($imageId) {
            $image = ImageObject::fromAttachment($imageId);
            if ($image && $image->isValid()) {
                $schema['image'] = $image->toArray();
            }
        }
        
        return $schema;
    }
    
    public function getCacheKey(string $context, array $options = []): string
    {
        $postId = get_the_ID();
        $modified = get_the_modified_date('U');
        return "wp_article_{$postId}_{$modified}";
    }
    
    public function getSupportedSchemaTypes(): array { return ['Article', 'BlogPosting']; }
}

/**
 * Navigation Schema Provider
 * 
 * Provides SiteNavigationElement schema.
 */
class NavigationProvider implements DataProviderInterface
{
    public function getProviderId(): string { return 'wordpress_navigation'; }
    public function getPriority(): int { return 5; }
    public function isAvailable(): bool { return true; }
    
    public function canProvide(string $context, array $options = []): bool
    {
        return has_nav_menu('primary') || has_nav_menu('main');
    }
    
    public function provide(string $context, array $options = []): array
    {
        $menuLocations = get_nav_menu_locations();
        $menuId = $menuLocations['primary'] ?? $menuLocations['main'] ?? null;
        
        if (!$menuId) {
            return [];
        }
        
        $menuItems = wp_get_nav_menu_items($menuId);
        if (!$menuItems) {
            return [];
        }
        
        $navigationElements = [];
        foreach ($menuItems as $item) {
            if ($item->menu_item_parent == 0) { // Only top-level items
                $navigationElements[] = [
                    '@type' => 'SiteNavigationElement',
                    'name' => $item->title,
                    'url' => $item->url
                ];
            }
        }
        
        return [
            '@type' => 'SiteNavigationElement',
            'name' => 'Main Navigation',
            'hasPart' => $navigationElements
        ];
    }
    
    public function getCacheKey(string $context, array $options = []): string
    {
        $menuHash = md5(wp_json_encode(get_nav_menu_locations()));
        return "wp_navigation_{$context}_{$menuHash}";
    }
    
    public function getSupportedSchemaTypes(): array { return ['SiteNavigationElement']; }
}