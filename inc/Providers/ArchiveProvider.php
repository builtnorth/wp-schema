<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Providers;

use BuiltNorth\WPSchema\Contracts\SchemaProviderInterface;
use BuiltNorth\WPSchema\Graph\SchemaPiece;

/**
 * Archive Provider
 * 
 * Provides CollectionPage schema for WordPress archive pages including
 * categories, tags, authors, dates, and custom taxonomies.
 * 
 * @since 3.0.0
 */
class ArchiveProvider implements SchemaProviderInterface
{
    public function can_provide(string $context): bool
    {
        // Only provide for archive contexts
        return $context === 'archive';
    }
    
    public function get_pieces(string $context): array
    {
        $pieces = [];
        
        // Create CollectionPage piece
        $archive = new SchemaPiece('#archive', 'CollectionPage');
        
        // Set basic properties
        $archive->set('url', $this->get_current_url());
        
        // Set name and description based on archive type
        $this->set_archive_metadata($archive);
        
        // Add breadcrumb reference if available
        $archive->add_reference('breadcrumb', '#breadcrumb');
        
        // Add the main entity (list of items)
        $items = $this->get_archive_items();
        if (!empty($items)) {
            $main_entity = new SchemaPiece('#itemlist', 'ItemList');
            $main_entity->set('itemListElement', $items);
            $main_entity->set('numberOfItems', count($items));
            
            $pieces[] = $main_entity;
            $archive->add_reference('mainEntity', '#itemlist');
        }
        
        // Add pagination if applicable
        $this->add_pagination($archive);
        
        // Allow filtering of archive data
        $data = apply_filters('wp_schema_archive_data', $archive->to_array(), $context);
        $archive->from_array($data);
        
        $pieces[] = $archive;
        
        return $pieces;
    }
    
    public function get_priority(): int
    {
        return 10; // Standard priority
    }
    
    /**
     * Set archive metadata based on type
     */
    private function set_archive_metadata(SchemaPiece $archive): void
    {
        if (is_category()) {
            $category = get_queried_object();
            $archive->set('name', sprintf(__('Category: %s'), single_cat_title('', false)));
            
            $description = category_description();
            if ($description) {
                $archive->set('description', wp_strip_all_tags($description));
            }
            
            // Add category as about
            $archive->set('about', [
                '@type' => 'Thing',
                'name' => $category->name,
                '@id' => get_category_link($category->term_id),
            ]);
            
        } elseif (is_tag()) {
            $tag = get_queried_object();
            $archive->set('name', sprintf(__('Tag: %s'), single_tag_title('', false)));
            
            $description = tag_description();
            if ($description) {
                $archive->set('description', wp_strip_all_tags($description));
            }
            
            // Add tag as about
            $archive->set('about', [
                '@type' => 'Thing',
                'name' => $tag->name,
                '@id' => get_tag_link($tag->term_id),
            ]);
            
        } elseif (is_author()) {
            $author = get_queried_object();
            $archive->set('name', sprintf(__('Author: %s'), $author->display_name));
            
            $description = get_the_author_meta('description', $author->ID);
            if ($description) {
                $archive->set('description', wp_strip_all_tags($description));
            }
            
            // Add author reference
            $archive->set('author', [
                '@type' => 'Person',
                'name' => $author->display_name,
                '@id' => get_author_posts_url($author->ID),
            ]);
            
        } elseif (is_date()) {
            if (is_year()) {
                $archive->set('name', sprintf(__('Year: %s'), get_the_date('Y')));
            } elseif (is_month()) {
                $archive->set('name', sprintf(__('Month: %s'), get_the_date('F Y')));
            } elseif (is_day()) {
                $archive->set('name', sprintf(__('Day: %s'), get_the_date('F j, Y')));
            }
            
            $archive->set('description', __('Archives for ') . get_the_date());
            
        } elseif (is_tax()) {
            $term = get_queried_object();
            $taxonomy = get_taxonomy($term->taxonomy);
            $archive->set('name', sprintf('%s: %s', $taxonomy->labels->singular_name, single_term_title('', false)));
            
            $description = term_description();
            if ($description) {
                $archive->set('description', wp_strip_all_tags($description));
            }
            
            // Add term as about
            $archive->set('about', [
                '@type' => 'Thing',
                'name' => $term->name,
                '@id' => get_term_link($term),
            ]);
            
        } elseif (is_post_type_archive()) {
            $post_type = get_queried_object();
            $archive->set('name', post_type_archive_title('', false));
            
            if (!empty($post_type->description)) {
                $archive->set('description', $post_type->description);
            }
            
        } else {
            // Generic archive
            $archive->set('name', __('Archives'));
            $archive->set('description', get_bloginfo('description'));
        }
    }
    
    /**
     * Get items for the archive
     */
    private function get_archive_items(): array
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
                
                // Add item details
                $list_item = [
                    '@type' => $this->get_post_schema_type(),
                    '@id' => get_permalink(),
                    'headline' => get_the_title(),
                    'url' => get_permalink(),
                    'datePublished' => get_the_date('c'),
                    'dateModified' => get_the_modified_date('c'),
                ];
                
                // Add author
                $list_item['author'] = [
                    '@type' => 'Person',
                    'name' => get_the_author(),
                    'url' => get_author_posts_url(get_the_author_meta('ID')),
                ];
                
                // Add excerpt if available
                $excerpt = get_the_excerpt();
                if ($excerpt) {
                    $list_item['description'] = wp_strip_all_tags($excerpt);
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
        $type = apply_filters('wp_schema_archive_item_type', '', $post_type);
        if ($type) {
            return $type;
        }
        
        // Default mappings
        $mappings = [
            'post' => 'Article',
            'page' => 'WebPage',
        ];
        
        return $mappings[$post_type] ?? 'CreativeWork';
    }
    
    /**
     * Add pagination data
     */
    private function add_pagination(SchemaPiece $archive): void
    {
        global $wp_query;
        
        $paged = get_query_var('paged') ?: 1;
        $max_pages = $wp_query->max_num_pages;
        
        if ($max_pages > 1) {
            // Add current page info
            $archive->set('pageStart', (($paged - 1) * get_option('posts_per_page')) + 1);
            $archive->set('pageEnd', min($paged * get_option('posts_per_page'), $wp_query->found_posts));
            
            // Add prev/next links
            if ($paged > 1) {
                $archive->set('previousPageUrl', get_previous_posts_page_link());
            }
            
            if ($paged < $max_pages) {
                $archive->set('nextPageUrl', get_next_posts_page_link());
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