<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Defaults;

use BuiltNorth\Schema\Contracts\DataProviderInterface;

/**
 * Polaris Blocks Breadcrumb Provider
 * 
 * Provides breadcrumb schema data from WordPress hierarchy.
 * Safe implementation that generates breadcrumbs without template parsing.
 * 
 * @since 2.0.0
 */
class PolarisBlocksBreadcrumbProvider implements DataProviderInterface
{
    private string $providerId = 'polaris_blocks_breadcrumb';
    private int $priority = 10;
    
    /**
     * Get provider ID
     */
    public function getProviderId(): string
    {
        return $this->providerId;
    }
    
    /**
     * Get provider priority
     */
    public function getPriority(): int
    {
        return $this->priority;
    }
    
    /**
     * Check if provider is available
     */
    public function isAvailable(): bool
    {
        return class_exists('PolarisBlocks\\App') || function_exists('polaris_blocks_init');
    }
    
    /**
     * Check if we can provide data for this context
     */
    public function canProvide(string $context, array $options = []): bool
    {
        // Provide breadcrumb data for contexts where it makes sense
        return in_array($context, ['singular', 'taxonomy', 'archive']) && !is_front_page();
    }
    
    /**
     * Provide breadcrumb schema data
     */
    public function provide(string $context, array $options = []): array
    {
        $breadcrumb_items = $this->generateBreadcrumbItems();
        
        if (empty($breadcrumb_items)) {
            return [];
        }
        
        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumb_items
        ];
    }
    
    /**
     * Get cache key for this provider
     */
    public function getCacheKey(string $context, array $options = []): string
    {
        $key = "polaris_breadcrumb_{$context}";
        
        if (is_singular() && !empty($options['post_id'])) {
            $key .= "_post_{$options['post_id']}";
        } elseif (is_tax() || is_category() || is_tag()) {
            $term = get_queried_object();
            if ($term && isset($term->term_id)) {
                $key .= "_term_{$term->term_id}";
            }
        }
        
        return $key;
    }
    
    /**
     * Get supported schema types
     */
    public function getSupportedSchemaTypes(): array
    {
        return ['BreadcrumbList'];
    }
    
    /**
     * Generate breadcrumb items from WordPress hierarchy
     */
    private function generateBreadcrumbItems(): array
    {
        $breadcrumb_items = [];
        $position = 1;
        
        // Always start with home
        $breadcrumb_items[] = [
            '@type' => 'ListItem',
            'position' => $position,
            'name' => get_bloginfo('name'),
            'item' => home_url('/')
        ];
        $position++;
        
        if (is_singular()) {
            $post = get_post();
            if ($post) {
                // Add parent pages if it's a page
                if ($post->post_type === 'page' && $post->post_parent) {
                    $ancestors = get_post_ancestors($post);
                    $ancestors = array_reverse($ancestors);
                    
                    foreach ($ancestors as $ancestor_id) {
                        $ancestor = get_post($ancestor_id);
                        if ($ancestor) {
                            $breadcrumb_items[] = [
                                '@type' => 'ListItem',
                                'position' => $position,
                                'name' => $ancestor->post_title,
                                'item' => get_permalink($ancestor)
                            ];
                            $position++;
                        }
                    }
                }
                
                // Add current post/page
                $breadcrumb_items[] = [
                    '@type' => 'ListItem',
                    'position' => $position,
                    'name' => $post->post_title
                ];
            }
        } elseif (is_tax() || is_category() || is_tag()) {
            $term = get_queried_object();
            if ($term) {
                // Add parent terms if any
                if ($term->parent) {
                    $ancestors = get_ancestors($term->term_id, $term->taxonomy);
                    $ancestors = array_reverse($ancestors);
                    
                    foreach ($ancestors as $ancestor_id) {
                        $ancestor = get_term($ancestor_id, $term->taxonomy);
                        if ($ancestor && !is_wp_error($ancestor)) {
                            $breadcrumb_items[] = [
                                '@type' => 'ListItem',
                                'position' => $position,
                                'name' => $ancestor->name,
                                'item' => get_term_link($ancestor)
                            ];
                            $position++;
                        }
                    }
                }
                
                // Add current term
                $breadcrumb_items[] = [
                    '@type' => 'ListItem',
                    'position' => $position,
                    'name' => $term->name
                ];
            }
        } elseif (is_archive()) {
            $breadcrumb_items[] = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => get_the_archive_title()
            ];
        }
        
        return $breadcrumb_items;
    }
}