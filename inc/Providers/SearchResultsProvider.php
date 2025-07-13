<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Providers;

use BuiltNorth\Schema\Contracts\SchemaProviderInterface;
use BuiltNorth\Schema\Graph\SchemaPiece;

/**
 * Search Results Provider
 * 
 * Provides SearchResultsPage schema for WordPress search results pages.
 * 
 * @since 3.0.0
 */
class SearchResultsProvider implements SchemaProviderInterface
{
    public function can_provide(string $context): bool
    {
        // Only provide for search context
        return $context === 'search';
    }
    
    public function get_pieces(string $context): array
    {
        $pieces = [];
        
        // Create SearchResultsPage piece
        $search_page = new SchemaPiece('#searchresults', 'SearchResultsPage');
        
        // Get search query
        $search_query = get_search_query();
        
        // Set basic properties
        $search_page->set('url', $this->get_current_url());
        $search_page->set('name', sprintf(__('Search Results for: %s'), $search_query));
        
        // Add breadcrumb reference if available
        $search_page->add_reference('breadcrumb', '#breadcrumb');
        
        // Add search action that was performed
        $search_page->set('potentialAction', [
            '@type' => 'SearchAction',
            'query' => $search_query,
            'result' => '#itemlist',
        ]);
        
        // Get search results count
        global $wp_query;
        $result_count = $wp_query->found_posts;
        
        // Add description with result count
        if ($result_count > 0) {
            $search_page->set('description', sprintf(
                _n(
                    'Found %d result for "%s"',
                    'Found %d results for "%s"',
                    $result_count
                ),
                $result_count,
                $search_query
            ));
        } else {
            $search_page->set('description', sprintf(
                __('No results found for "%s"'),
                $search_query
            ));
        }
        
        // Add the main entity (list of search results) if there are results
        if ($result_count > 0) {
            $items = $this->get_search_result_items();
            if (!empty($items)) {
                $main_entity = new SchemaPiece('#itemlist', 'ItemList');
                $main_entity->set('itemListElement', $items);
                $main_entity->set('numberOfItems', count($items));
                
                $pieces[] = $main_entity;
                $search_page->add_reference('mainEntity', '#itemlist');
            }
        }
        
        // Add pagination if applicable
        $this->add_pagination($search_page);
        
        // Add search statistics
        $search_page->set('searchResultsCount', $result_count);
        
        // Allow filtering of search results data
        $data = apply_filters('wp_schema_search_results_data', $search_page->to_array(), $context, $search_query);
        $search_page->from_array($data);
        
        $pieces[] = $search_page;
        
        return $pieces;
    }
    
    public function get_priority(): int
    {
        return 10; // Standard priority
    }
    
    /**
     * Get search result items
     */
    private function get_search_result_items(): array
    {
        $items = [];
        $position = 1;
        
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                
                $item = [
                    '@type' => 'ListItem',
                    'position' => $position,
                    'url' => get_permalink(),
                    'name' => get_the_title(),
                ];
                
                // Determine schema type based on post type
                $schema_type = $this->get_post_schema_type();
                
                // Add item details
                $list_item = [
                    '@type' => $schema_type,
                    '@id' => get_permalink(),
                    'headline' => get_the_title(),
                    'url' => get_permalink(),
                ];
                
                // Add dates for time-based content
                if (in_array($schema_type, ['Article', 'BlogPosting', 'NewsArticle'])) {
                    $list_item['datePublished'] = get_the_date('c');
                    $list_item['dateModified'] = get_the_modified_date('c');
                }
                
                // Add author for authored content
                if (in_array($schema_type, ['Article', 'BlogPosting', 'NewsArticle'])) {
                    $list_item['author'] = [
                        '@type' => 'Person',
                        'name' => get_the_author(),
                        'url' => get_author_posts_url(get_the_author_meta('ID')),
                    ];
                }
                
                // Add excerpt/description with search term highlighting
                $excerpt = get_the_excerpt();
                if ($excerpt) {
                    // Highlight search terms in excerpt
                    $highlighted_excerpt = $this->highlight_search_terms($excerpt, get_search_query());
                    $list_item['description'] = wp_strip_all_tags($highlighted_excerpt);
                }
                
                // Add featured image if available
                if (has_post_thumbnail()) {
                    $thumbnail_id = get_post_thumbnail_id();
                    $thumbnail_url = wp_get_attachment_image_url($thumbnail_id, 'full');
                    $thumbnail_metadata = wp_get_attachment_metadata($thumbnail_id);
                    
                    if ($thumbnail_url) {
                        $image = [
                            '@type' => 'ImageObject',
                            'url' => $thumbnail_url,
                        ];
                        
                        if (!empty($thumbnail_metadata['width']) && !empty($thumbnail_metadata['height'])) {
                            $image['width'] = $thumbnail_metadata['width'];
                            $image['height'] = $thumbnail_metadata['height'];
                        }
                        
                        $list_item['image'] = $image;
                    }
                }
                
                // Add relevance score if available
                if (isset($GLOBALS['wp_query']->posts[$position - 1]->relevance_score)) {
                    $item['relevanceScore'] = $GLOBALS['wp_query']->posts[$position - 1]->relevance_score;
                }
                
                $item['item'] = $list_item;
                $items[] = $item;
                $position++;
            }
            
            // Reset post data
            wp_reset_postdata();
        }
        
        return $items;
    }
    
    /**
     * Get schema type for posts
     */
    private function get_post_schema_type(): string
    {
        $post_type = get_post_type();
        
        // Check for override filter
        $type = apply_filters('wp_schema_search_item_type', '', $post_type);
        if ($type) {
            return $type;
        }
        
        // Default mappings
        $mappings = [
            'post' => 'Article',
            'page' => 'WebPage',
            'attachment' => 'MediaObject',
        ];
        
        return $mappings[$post_type] ?? 'CreativeWork';
    }
    
    /**
     * Highlight search terms in text
     */
    private function highlight_search_terms(string $text, string $search_query): string
    {
        // Split search query into individual terms
        $terms = array_filter(explode(' ', $search_query));
        
        // Don't highlight if no terms
        if (empty($terms)) {
            return $text;
        }
        
        // Escape terms for regex
        $terms = array_map('preg_quote', $terms);
        
        // Create pattern to match any term
        $pattern = '/\b(' . implode('|', $terms) . ')\b/i';
        
        // Wrap matches in mark tags (will be stripped later, but indicates relevance)
        $highlighted = preg_replace($pattern, '<mark>$1</mark>', $text);
        
        return $highlighted;
    }
    
    /**
     * Add pagination data
     */
    private function add_pagination(SchemaPiece $search_page): void
    {
        global $wp_query;
        
        $paged = get_query_var('paged') ?: 1;
        $max_pages = $wp_query->max_num_pages;
        
        if ($max_pages > 1) {
            // Add current page info
            $search_page->set('pageStart', (($paged - 1) * get_option('posts_per_page')) + 1);
            $search_page->set('pageEnd', min($paged * get_option('posts_per_page'), $wp_query->found_posts));
            
            // Add prev/next links
            if ($paged > 1) {
                $search_page->set('previousPageUrl', get_previous_posts_page_link());
            }
            
            if ($paged < $max_pages) {
                $search_page->set('nextPageUrl', get_next_posts_page_link());
            }
        }
    }
    
    /**
     * Get current URL
     */
    private function get_current_url(): string
    {
        global $wp;
        return home_url(add_query_arg([], $wp->request));
    }
}