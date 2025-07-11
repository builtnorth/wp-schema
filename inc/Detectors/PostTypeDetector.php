<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Detectors;

/**
 * Post Type Detector
 * 
 * Detects appropriate schema types for posts based on post type with plugin override capability
 * 
 * @package BuiltNorth\Utility
 * @since 1.0.0
 */
class PostTypeDetector
{
    /**
     * Get schema type for a post with plugin override capability
     *
     * @param int $post_id Post ID
     * @return string Schema type
     */
    public static function render($post_id)
    {
        // Allow plugins to override schema type
        $schema_type = apply_filters('wp_utility_schema_type_for_post', null, $post_id);
        
        if ($schema_type) {
            return $schema_type;
        }
        
        // Basic detection based on post type
        $post_type = get_post_type($post_id);
        
        return self::get_schema_type_from_post_type($post_type);
    }

    /**
     * Get schema type from post type
     *
     * @param string $post_type Post type
     * @return string Schema type
     */
    private static function get_schema_type_from_post_type($post_type)
    {
        switch ($post_type) {
            // Product types
            case 'product':
            case 'woocommerce_product':
            case 'simple_products':
            case 'variable_products':
            case 'grouped_products':
            case 'external_products':
            case 'download':
            case 'edds_download':
                return 'Product';
                
            // Service types
            case 'service':
            case 'services':
            case 'professional_service':
                return 'Service';
                
            // Software types
            case 'software':
            case 'app':
            case 'application':
            case 'plugin':
            case 'theme':
            case 'tool':
                return 'SoftwareApplication';
                
            // Restaurant types
            case 'restaurant':
            case 'food':
            case 'cafe':
            case 'bar':
            case 'food_establishment':
                return 'Restaurant';
                
            // Hotel types
            case 'hotel':
            case 'accommodation':
                return 'Hotel';
                
            // Event types
            case 'event':
            case 'events':
            case 'tribe_events':
            case 'event_organiser':
            case 'ai1ec_event':
            case 'calendar_event':
            case 'meeting':
            case 'conference':
            case 'workshop':
                return 'Event';
                
            // Organization types
            case 'organization':
            case 'company':
            case 'corporate':
            case 'business_profile':
                return 'Organization';
                
            // Person types
            case 'person':
            case 'team_member':
            case 'staff':
            case 'employee':
            case 'speaker':
            case 'instructor':
            case 'author':
            case 'profile':
            case 'team':
                return 'Person';
                
            // Article types
            case 'article':
            case 'post':
            case 'blog':
            case 'blog_post':
            case 'polaris_blog':
                return 'Article';
                
            // News types
            case 'news':
            case 'news_article':
            case 'press_release':
            case 'announcement':
            case 'press':
            case 'media_release':
                return 'NewsArticle';
                
            // BlogPosting types
            case 'blog_post':
            case 'polaris_blog_post':
            case 'blogpost':
                return 'BlogPosting';
                
            // FAQ types
            case 'faq':
            case 'faqs':
            case 'questions':
            case 'help':
            case 'knowledge_base':
            case 'support':
                return 'FAQPage';
                
            // Recipe types
            case 'recipe':
            case 'recipes':
            case 'wprm_recipe':
            case 'yummly_recipe':
            case 'zrdn_recipe':
            case 'recipe_card':
            case 'cooking_recipe':
            case 'food_recipe':
                return 'Recipe';
                
            // Business types
            case 'business':
            case 'local_business':
            case 'store':
            case 'shop':
            case 'retail_store':
                return 'LocalBusiness';
                
            // Review types
            case 'review':
            case 'testimonial':
            case 'rating':
            case 'feedback':
            case 'customer_review':
            case 'product_review':
                return 'Review';
                
            // Book types
            case 'book':
            case 'publication':
            case 'ebook':
            case 'digital_book':
                return 'Book';
                
            // Movie types
            case 'movie':
            case 'film':
            case 'video':
            case 'video_content':
            case 'media':
                return 'Movie';
                
            // Music types
            case 'music':
            case 'song':
            case 'album':
            case 'track':
            case 'audio':
            case 'podcast':
                return 'MusicRecording';
                
            // Job types
            case 'job':
            case 'job_listing':
            case 'career':
            case 'employment':
            case 'position':
            case 'vacancy':
                return 'JobPosting';
                
            // Course types
            case 'course':
            case 'lesson':
            case 'module':
            case 'training':
            case 'education':
            case 'learning':
            case 'sfwd-courses':
            case 'llms_course':
            case 'course_unit':
                return 'Course';
                
            // Game types
            case 'game':
            case 'video_game':
            case 'mobile_game':
            case 'online_game':
            case 'gaming':
                return 'Game';
                
            // Page types (default for most content)
            case 'page':
            case 'landing_page':
            case 'static_page':
            case 'polaris_page':
            default:
                return 'WebPage'; // Better default than Product
        }
    }
} 