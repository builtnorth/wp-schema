<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Providers;

use BuiltNorth\WPSchema\Contracts\SchemaProviderInterface;
use BuiltNorth\WPSchema\Graph\SchemaPiece;
use BuiltNorth\WPSchema\Services\SchemaIds;

/**
 * WebPage Provider
 *
 * Emits the WebPage node for every front-page and singular request. The page
 * node is *always* typed WebPage regardless of which entity type the post type
 * resolves to — that type belongs to the entity node (Article, Service, Hotel…),
 * which links up to this page via isPartOf + mainEntityOfPage. Mirrors Yoast,
 * where WebPage is unconditional and the "article type" setting drives only the
 * entity.
 *
 * The one case it yields: when the resolved type is a WebPage subtype that
 * PageTypeProvider handles (AboutPage, ContactPage…), that provider emits the
 * page node instead, so we don't produce two.
 *
 * @since 3.0.0
 */
class WebPageProvider implements SchemaProviderInterface
{
    public function can_provide(string $context): bool
    {
        if ($context === 'home') {
            return true;
        }

        if ($context !== 'singular') {
            return false;
        }

        $post = get_queried_object();
        if (!$post || !isset($post->ID)) {
            return false;
        }

        $default_type = $this->get_default_schema_type($post->post_type);
        $schema_type = apply_filters('wp_schema_framework_post_type_override', $default_type, $post->ID, $post->post_type, $post);

        return !in_array($schema_type, PageTypeProvider::get_supported_types(), true);
    }

    public function get_pieces(string $context): array
    {
        // Handle homepage
        if ($context === 'home') {
            $webpage = new SchemaPiece(SchemaIds::home_webpage_id(), 'WebPage', [], 'webpage');
            
            $webpage
                ->set('name', get_bloginfo('name'))
                ->set('headline', get_bloginfo('name'))
                ->set('url', home_url())
                ->set('inLanguage', get_bloginfo('language'))
                ->add_reference('publisher', SchemaIds::organization_id())
                ->add_reference('isPartOf', SchemaIds::website_id());

            // Add description
            $description = get_bloginfo('description');
            if ($description) {
                $webpage->set('description', $description);
            }

            // If front page is a static page
            if (get_option('show_on_front') === 'page') {
                $page_id = get_option('page_on_front');
                if ($page_id) {
                    $post = get_post($page_id);
                    if ($post) {
                        $webpage->set('headline', $post->post_title);

                        if ($post->post_excerpt) {
                            $webpage->set('description', wp_strip_all_tags($post->post_excerpt));
                        }

                        $webpage
                            ->set('datePublished', get_the_date('c', $page_id))
                            ->set('dateModified', get_the_modified_date('c', $page_id));

                        $thumb_id = get_post_thumbnail_id($page_id);
                        if ($thumb_id) {
                            $image_url = wp_get_attachment_image_url($thumb_id, 'full');
                            if ($image_url) {
                                $image_data = ['@type' => 'ImageObject', 'url' => $image_url];
                                $metadata = wp_get_attachment_metadata($thumb_id);
                                if (!empty($metadata['width'])) {
                                    $image_data['width'] = $metadata['width'];
                                }
                                if (!empty($metadata['height'])) {
                                    $image_data['height'] = $metadata['height'];
                                }
                                $webpage->set('image', $image_data);
                            }
                        }
                    }
                }
            }
            
            // Only add breadcrumb reference if a BreadcrumbList will be in the graph
            if (apply_filters('wp_schema_framework_has_breadcrumb', false)) {
                $webpage->add_reference('breadcrumb', '#breadcrumb');
            }

            // Allow filtering of homepage data
            $data = apply_filters('wp_schema_framework_homepage_data', $webpage->to_array());
            $data = apply_filters('wp_schema_framework_webpage_data', $data, 0, null);
            $webpage->from_array($data);
            
            return [$webpage];
        }
        
        // Handle regular pages
        $post = get_queried_object();
        if (!$post) {
            return [];
        }

        $webpage_id = SchemaIds::webpage_id($post);
        if ($webpage_id === '') {
            return [];
        }

        // Always WebPage: the entity type (Article, Service, Hotel…) lives on the
        // entity node, which points back here via mainEntityOfPage.
        $webpage = new SchemaPiece($webpage_id, 'WebPage', [], 'webpage');
        
        $webpage
            ->set('headline', $post->post_title)
            ->set('name', $post->post_title)
            ->set('url', get_permalink($post->ID))
            ->set('datePublished', get_the_date('c', $post->ID))
            ->set('dateModified', get_the_modified_date('c', $post->ID))
            ->set('inLanguage', get_bloginfo('language'))
            ->add_reference('publisher', SchemaIds::organization_id())
            ->add_reference('isPartOf', SchemaIds::website_id());

        if (AuthorProvider::has_resolvable_author($post)) {
            $webpage->add_reference('author', '#author');
        }

        // Add description from filter or excerpt
        $description = apply_filters('wp_schema_framework_post_description', '', $post->ID, $post);
        if ($description) {
            $webpage->set('description', $description);
        } elseif ($post->post_excerpt) {
            $webpage->set('description', wp_strip_all_tags($post->post_excerpt));
        }

        // Featured image with dimensions
        $thumb_id = get_post_thumbnail_id($post->ID);
        if ($thumb_id) {
            $image_url = wp_get_attachment_image_url($thumb_id, 'full');
            if ($image_url) {
                $image_data = ['@type' => 'ImageObject', 'url' => $image_url];
                $metadata = wp_get_attachment_metadata($thumb_id);
                if (!empty($metadata['width'])) {
                    $image_data['width'] = $metadata['width'];
                }
                if (!empty($metadata['height'])) {
                    $image_data['height'] = $metadata['height'];
                }
                $webpage->set('image', $image_data);
            }
        }
        
        // Only add breadcrumb reference if a BreadcrumbList will be in the graph
        if (apply_filters('wp_schema_framework_has_breadcrumb', false)) {
            $webpage->add_reference('breadcrumb', '#breadcrumb');
        }

        // Allow filtering of webpage data
        $data = apply_filters('wp_schema_framework_webpage_data', $webpage->to_array(), $post->ID, $post);
        $webpage->from_array($data);
        
        return [$webpage];
    }
    
    public function get_priority(): int
    {
        return 20; // Same priority as ArticleProvider
    }
    
    /**
     * Get default schema type for post type
     */
    private function get_default_schema_type(string $post_type): string
    {
        $mappings = [
            'page' => 'WebPage',
        ];
        
        return apply_filters('wp_schema_framework_post_type_mapping', $mappings[$post_type] ?? '', $post_type);
    }
}