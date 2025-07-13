<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Providers;

use BuiltNorth\Schema\Contracts\SchemaProviderInterface;
use BuiltNorth\Schema\Graph\SchemaPiece;

/**
 * Media Provider
 * 
 * Provides MediaObject schema for WordPress attachment pages.
 * Automatically detects media type (image, video, audio) and uses appropriate schema.
 * 
 * @since 3.0.0
 */
class MediaProvider implements SchemaProviderInterface
{
    public function can_provide(string $context): bool
    {
        // Only provide for singular attachment pages
        return $context === 'singular' && is_attachment();
    }
    
    public function get_pieces(string $context): array
    {
        $attachment_id = get_the_ID();
        
        if (!$attachment_id) {
            return [];
        }
        
        // Get attachment metadata
        $mime_type = get_post_mime_type($attachment_id);
        $media_type = $this->get_media_type($mime_type);
        
        // Create appropriate media object
        $media = new SchemaPiece('#media-' . $attachment_id, $media_type);
        
        // Set common properties
        $media->set('url', wp_get_attachment_url($attachment_id));
        $media->set('name', get_the_title($attachment_id));
        
        // Add description if available
        $description = get_the_content();
        if ($description) {
            $media->set('description', wp_strip_all_tags($description));
        }
        
        // Add caption if available
        $caption = wp_get_attachment_caption($attachment_id);
        if ($caption) {
            $media->set('caption', $caption);
        }
        
        // Add upload date
        $media->set('uploadDate', get_the_date('c', $attachment_id));
        
        // Add content URL (direct file URL)
        $media->set('contentUrl', wp_get_attachment_url($attachment_id));
        
        // Add author/uploader
        $author_id = get_post_field('post_author', $attachment_id);
        if ($author_id) {
            $media->set('author', [
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', $author_id),
                'url' => get_author_posts_url($author_id),
            ]);
        }
        
        // Add media-type specific properties
        switch ($media_type) {
            case 'ImageObject':
                $this->add_image_properties($media, $attachment_id);
                break;
            case 'VideoObject':
                $this->add_video_properties($media, $attachment_id);
                break;
            case 'AudioObject':
                $this->add_audio_properties($media, $attachment_id);
                break;
        }
        
        // Add thumbnail if different from main image
        if ($media_type !== 'ImageObject') {
            $thumbnail_id = get_post_thumbnail_id($attachment_id);
            if ($thumbnail_id && $thumbnail_id !== $attachment_id) {
                $thumbnail_url = wp_get_attachment_image_url($thumbnail_id, 'full');
                if ($thumbnail_url) {
                    $media->set('thumbnailUrl', $thumbnail_url);
                }
            }
        }
        
        // Add license information if available
        $this->add_license_info($media, $attachment_id);
        
        // Add breadcrumb reference
        $media->add_reference('breadcrumb', '#breadcrumb');
        
        // Allow filtering of media data
        $data = apply_filters('wp_schema_media_data', $media->to_array(), $context, $attachment_id);
        $media->from_array($data);
        
        return [$media];
    }
    
    public function get_priority(): int
    {
        return 10; // Standard priority
    }
    
    /**
     * Determine media type from MIME type
     */
    private function get_media_type(string $mime_type): string
    {
        if (strpos($mime_type, 'image/') === 0) {
            return 'ImageObject';
        } elseif (strpos($mime_type, 'video/') === 0) {
            return 'VideoObject';
        } elseif (strpos($mime_type, 'audio/') === 0) {
            return 'AudioObject';
        }
        
        // Default to MediaObject for other types
        return 'MediaObject';
    }
    
    /**
     * Add image-specific properties
     */
    private function add_image_properties(SchemaPiece $media, int $attachment_id): void
    {
        $metadata = wp_get_attachment_metadata($attachment_id);
        
        if (!empty($metadata['width']) && !empty($metadata['height'])) {
            $media->set('width', $metadata['width']);
            $media->set('height', $metadata['height']);
        }
        
        // Add EXIF data if available
        if (!empty($metadata['image_meta'])) {
            $exif = $metadata['image_meta'];
            
            // Camera information
            if (!empty($exif['camera'])) {
                $media->set('exifData', [
                    '@type' => 'PropertyValue',
                    'name' => 'camera',
                    'value' => $exif['camera'],
                ]);
            }
            
            // ISO
            if (!empty($exif['iso'])) {
                $media->set('isoSpeedRating', $exif['iso']);
            }
            
            // Date taken
            if (!empty($exif['created_timestamp'])) {
                $media->set('dateCreated', date('c', $exif['created_timestamp']));
            }
            
            // Copyright
            if (!empty($exif['copyright'])) {
                $media->set('copyrightHolder', [
                    '@type' => 'Organization',
                    'name' => $exif['copyright'],
                ]);
            }
        }
        
        // Add file format
        $mime_type = get_post_mime_type($attachment_id);
        $media->set('encodingFormat', $mime_type);
        
        // Add file size if available
        $file_path = get_attached_file($attachment_id);
        if ($file_path && file_exists($file_path)) {
            $file_size = filesize($file_path);
            if ($file_size) {
                $media->set('contentSize', $this->format_bytes($file_size));
            }
        }
    }
    
    /**
     * Add video-specific properties
     */
    private function add_video_properties(SchemaPiece $media, int $attachment_id): void
    {
        $metadata = wp_get_attachment_metadata($attachment_id);
        
        // Video dimensions
        if (!empty($metadata['width']) && !empty($metadata['height'])) {
            $media->set('width', $metadata['width']);
            $media->set('height', $metadata['height']);
        }
        
        // Duration
        if (!empty($metadata['length'])) {
            $media->set('duration', $this->format_duration($metadata['length']));
        }
        
        // Bitrate
        if (!empty($metadata['bitrate'])) {
            $media->set('bitrate', $metadata['bitrate']);
        }
        
        // Video format
        $mime_type = get_post_mime_type($attachment_id);
        $media->set('encodingFormat', $mime_type);
        
        // Add file size
        $file_path = get_attached_file($attachment_id);
        if ($file_path && file_exists($file_path)) {
            $file_size = filesize($file_path);
            if ($file_size) {
                $media->set('contentSize', $this->format_bytes($file_size));
            }
        }
        
        // Embed URL if available
        $embed_url = wp_get_attachment_url($attachment_id);
        if ($embed_url) {
            $media->set('embedUrl', $embed_url);
        }
    }
    
    /**
     * Add audio-specific properties
     */
    private function add_audio_properties(SchemaPiece $media, int $attachment_id): void
    {
        $metadata = wp_get_attachment_metadata($attachment_id);
        
        // Duration
        if (!empty($metadata['length'])) {
            $media->set('duration', $this->format_duration($metadata['length']));
        }
        
        // Bitrate
        if (!empty($metadata['bitrate'])) {
            $media->set('bitrate', $metadata['bitrate']);
        }
        
        // Audio format
        $mime_type = get_post_mime_type($attachment_id);
        $media->set('encodingFormat', $mime_type);
        
        // Add file size
        $file_path = get_attached_file($attachment_id);
        if ($file_path && file_exists($file_path)) {
            $file_size = filesize($file_path);
            if ($file_size) {
                $media->set('contentSize', $this->format_bytes($file_size));
            }
        }
    }
    
    /**
     * Add license information
     */
    private function add_license_info(SchemaPiece $media, int $attachment_id): void
    {
        // Check for custom license field
        $license = get_post_meta($attachment_id, '_media_license', true);
        
        if ($license) {
            // Map common licenses to URLs
            $license_urls = [
                'cc-by' => 'https://creativecommons.org/licenses/by/4.0/',
                'cc-by-sa' => 'https://creativecommons.org/licenses/by-sa/4.0/',
                'cc-by-nc' => 'https://creativecommons.org/licenses/by-nc/4.0/',
                'cc-by-nc-sa' => 'https://creativecommons.org/licenses/by-nc-sa/4.0/',
                'cc0' => 'https://creativecommons.org/publicdomain/zero/1.0/',
                'public-domain' => 'https://creativecommons.org/publicdomain/mark/1.0/',
            ];
            
            if (isset($license_urls[$license])) {
                $media->set('license', $license_urls[$license]);
            } else {
                $media->set('license', $license);
            }
        }
        
        // Add copyright year if available
        $copyright_year = get_post_meta($attachment_id, '_copyright_year', true);
        if ($copyright_year) {
            $media->set('copyrightYear', $copyright_year);
        }
    }
    
    /**
     * Format bytes to human readable
     */
    private function format_bytes(int $bytes): string
    {
        return $bytes . ' bytes';
    }
    
    /**
     * Format duration to ISO 8601
     */
    private function format_duration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;
        
        $duration = 'PT';
        if ($hours > 0) {
            $duration .= $hours . 'H';
        }
        if ($minutes > 0) {
            $duration .= $minutes . 'M';
        }
        if ($seconds > 0) {
            $duration .= $seconds . 'S';
        }
        
        return $duration;
    }
}