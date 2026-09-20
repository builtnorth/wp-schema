<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Providers;

use BuiltNorth\WPSchema\Contracts\SchemaProviderInterface;
use BuiltNorth\WPSchema\Graph\SchemaPiece;

/**
 * Comment Provider
 * 
 * Adds Comment schema to posts and pages that have comments.
 * Comments are added as part of the main entity (Article, BlogPosting, etc.)
 * 
 * @since 3.0.0
 */
class CommentProvider implements SchemaProviderInterface
{
    /**
     * Max characters of comment body kept in JSON-LD (after stripping tags).
     * Full threads otherwise bloat every singular page's script tag.
     */
    private const MAX_COMMENT_TEXT_LENGTH = 280;

    public function __construct()
    {
        // Hook into piece filters to add comments
        add_filter('wp_schema_framework_piece_article', [$this, 'add_comments_to_piece'], 30, 2);
        add_filter('wp_schema_framework_piece_webpage', [$this, 'add_comments_to_piece'], 30, 2);
        add_filter('wp_schema_framework_piece_blogposting', [$this, 'add_comments_to_piece'], 30, 2);
        add_filter('wp_schema_framework_piece_newsarticle', [$this, 'add_comments_to_piece'], 30, 2);
    }
    
    public function can_provide(string $context): bool
    {
        // This provider modifies existing content, doesn't create new pieces
        return false;
    }
    
    public function get_pieces(string $context): array
    {
        // This provider doesn't create new pieces
        return [];
    }
    
    public function get_priority(): int
    {
        return 30; // Run after content providers
    }
    
    /**
     * Add comments to schema piece
     */
    public function add_comments_to_piece(SchemaPiece $piece, string $context): SchemaPiece
    {
        // Get piece data
        $data = $piece->to_array();
        
        // Add comments
        $data = $this->add_comments($data, $context);
        
        // Update piece with modified data
        $piece->from_array($data);
        
        return $piece;
    }
    
    /**
     * Add comments to content
     */
    private function add_comments(array $data, string $context): array
    {
        // Only add comments on singular pages
        if ($context !== 'singular' || !is_singular()) {
            return $data;
        }
        
        $post_id = get_the_ID();
        if (!$post_id) {
            return $data;
        }
        
        // Check if comments are open or exist
        if (!comments_open($post_id) && !get_comments_number($post_id)) {
            return $data;
        }
        
        // Add comment count
        $comment_count = (int) get_comments_number($post_id);
        if ($comment_count > 0) {
            $data['commentCount'] = $comment_count;
        }
        
        // Add discussion URL (comments section)
        $data['discussionUrl'] = get_permalink($post_id) . '#comments';
        
        // Get approved comments
        $comments = get_comments([
            'post_id' => $post_id,
            'status' => 'approve',
            'hierarchical' => false, // Get all comments, not threaded
            'number' => 50, // Limit to 50 comments for performance
            'orderby' => 'comment_date_gmt',
            'order' => 'ASC',
        ]);
        
        if (!empty($comments)) {
            $comment_schema = $this->build_comment_schema($comments);
            if (!empty($comment_schema)) {
                $data['comment'] = $comment_schema;
            }
        }
        
        // Add interaction statistics
        $this->add_interaction_statistics($data, $post_id, $comment_count);
        
        return $data;
    }
    
    /**
     * Build comment schema
     */
    private function build_comment_schema(array $comments): array
    {
        $comment_schema = [];
        
        foreach ($comments as $comment) {
            $schema = [
                '@type' => 'Comment',
                '@id' => get_comment_link($comment),
                'author' => $this->get_comment_author($comment),
                'dateCreated' => get_comment_date('c', $comment),
                'text' => self::truncate_comment_text(
                    wp_strip_all_tags($comment->comment_content)
                ),
            ];
            
            // Add parent reference for replies
            if ($comment->comment_parent) {
                $schema['parentItem'] = [
                    '@id' => get_comment_link($comment->comment_parent),
                ];
            }
            
            // Add rating if available (for review-type comments)
            $rating = get_comment_meta($comment->comment_ID, 'rating', true);
            if ($rating) {
                $schema['reviewRating'] = [
                    '@type' => 'Rating',
                    'ratingValue' => $rating,
                    'bestRating' => 5,
                    'worstRating' => 1,
                ];
            }
            
            // Add upvote count if available
            $upvotes = get_comment_meta($comment->comment_ID, 'upvotes', true);
            if ($upvotes) {
                $schema['upvoteCount'] = (int) $upvotes;
            }
            
            $comment_schema[] = $schema;
        }
        
        return $comment_schema;
    }

    /**
     * Cap comment body length for public JSON-LD.
     *
     * Filter: wp_schema_framework_comment_text_max_length (int, default 280).
     * Pass 0 or negative to keep the full stripped text.
     */
    public static function truncate_comment_text(string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if ($text === '') {
            return '';
        }

        $max = (int) apply_filters('wp_schema_framework_comment_text_max_length', self::MAX_COMMENT_TEXT_LENGTH);
        if ($max <= 0) {
            return $text;
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text) <= $max) {
                return $text;
            }
            return rtrim(mb_substr($text, 0, $max)) . '…';
        }

        if (strlen($text) <= $max) {
            return $text;
        }

        return rtrim(substr($text, 0, $max)) . '…';
    }
    
    /**
     * Get comment author schema
     */
    private function get_comment_author($comment): array
    {
        $author = [
            '@type' => 'Person',
            'name' => $comment->comment_author,
        ];
        
        // Add author URL if available (http(s) only)
        if (!empty($comment->comment_author_url)) {
            $author_url = esc_url($comment->comment_author_url, ['http', 'https']);
            if ($author_url) {
                $author['url'] = $author_url;
            }
        }
        
        // Check if comment is by a registered user
        if ($comment->user_id) {
            $user = get_userdata($comment->user_id);
            if ($user) {
                $author['@id'] = get_author_posts_url($comment->user_id);
                $author['name'] = $user->display_name;
                
                // Add author image if available (user id — not email-derived)
                $avatar_url = get_avatar_url($comment->user_id);
                if ($avatar_url) {
                    $author['image'] = $avatar_url;
                }
            }
        }
        // Guests: do not emit Gravatar from comment_author_email — the hash is
        // reversible enough to expose the address.
        
        return $author;
    }
    
    /**
     * Add interaction statistics
     */
    private function add_interaction_statistics(array &$data, int $post_id, int $comment_count): void
    {
        // Create interaction counter for user comments
        $interaction_counter = [
            '@type' => 'InteractionCounter',
            'interactionType' => [
                '@type' => 'CommentAction',
            ],
            'userInteractionCount' => $comment_count,
        ];
        
        // Add to existing interactionStatistic or create new
        if (isset($data['interactionStatistic']) && is_array($data['interactionStatistic'])) {
            // Check if it's already an array of statistics
            if (isset($data['interactionStatistic']['@type'])) {
                // Single statistic, convert to array
                $data['interactionStatistic'] = [$data['interactionStatistic'], $interaction_counter];
            } else {
                // Already an array, just add
                $data['interactionStatistic'][] = $interaction_counter;
            }
        } else {
            $data['interactionStatistic'] = $interaction_counter;
        }
        
        // Add potential action for commenting if comments are open
        if (comments_open($post_id)) {
            $comment_action = [
                '@type' => 'CommentAction',
                'name' => 'Comment',
                'target' => get_permalink($post_id) . '#respond',
            ];
            
            // Add to existing potentialAction or create new
            if (isset($data['potentialAction'])) {
                if (isset($data['potentialAction']['@type'])) {
                    // Single action, convert to array
                    $data['potentialAction'] = [$data['potentialAction'], $comment_action];
                } else {
                    // Already an array, just add
                    $data['potentialAction'][] = $comment_action;
                }
            } else {
                $data['potentialAction'] = $comment_action;
            }
        }
    }
}