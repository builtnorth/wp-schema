<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Models;

/**
 * ImageObject Value Object
 * 
 * Represents an image for schema markup.
 * 
 * @since 1.0.0
 */
class ImageObject
{
    public function __construct(
        private string $url,
        private ?int $width = null,
        private ?int $height = null,
        private ?string $caption = null,
        private ?string $description = null,
        private ?string $name = null
    ) {}
    
    /**
     * Create ImageObject from URL
     */
    public static function fromUrl(string $url): self
    {
        return new self($url);
    }
    
    /**
     * Create ImageObject from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            url: $data['url'] ?? $data['src'] ?? '',
            width: isset($data['width']) ? (int) $data['width'] : null,
            height: isset($data['height']) ? (int) $data['height'] : null,
            caption: $data['caption'] ?? null,
            description: $data['description'] ?? null,
            name: $data['name'] ?? $data['alt'] ?? null
        );
    }
    
    /**
     * Create ImageObject from WordPress attachment
     */
    public static function fromAttachment(int $attachmentId, string $size = 'full'): ?self
    {
        $url = wp_get_attachment_image_url($attachmentId, $size);
        if (!$url) {
            return null;
        }
        
        $metadata = wp_get_attachment_metadata($attachmentId);
        $alt = get_post_meta($attachmentId, '_wp_attachment_image_alt', true);
        $attachment = get_post($attachmentId);
        
        $width = null;
        $height = null;
        
        if ($size === 'full' && !empty($metadata['width']) && !empty($metadata['height'])) {
            $width = (int) $metadata['width'];
            $height = (int) $metadata['height'];
        } elseif (!empty($metadata['sizes'][$size])) {
            $sizeData = $metadata['sizes'][$size];
            $width = (int) $sizeData['width'];
            $height = (int) $sizeData['height'];
        }
        
        return new self(
            url: $url,
            width: $width,
            height: $height,
            caption: $attachment ? $attachment->post_excerpt : null,
            description: $attachment ? $attachment->post_content : null,
            name: $alt ?: ($attachment ? $attachment->post_title : null)
        );
    }
    
    /**
     * Convert to Schema.org format
     */
    public function toArray(): array
    {
        $image = [
            '@type' => 'ImageObject',
            'url' => $this->url
        ];
        
        if ($this->width) $image['width'] = $this->width;
        if ($this->height) $image['height'] = $this->height;
        if ($this->caption) $image['caption'] = $this->caption;
        if ($this->description) $image['description'] = $this->description;
        if ($this->name) $image['name'] = $this->name;
        
        return $image;
    }
    
    /**
     * Get just the URL (for simple use cases)
     */
    public function getUrl(): string
    {
        return $this->url;
    }
    
    /**
     * Check if image is valid
     */
    public function isValid(): bool
    {
        return !empty($this->url) && filter_var($this->url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Get image dimensions as string
     */
    public function getDimensions(): ?string
    {
        if ($this->width && $this->height) {
            return "{$this->width}x{$this->height}";
        }
        return null;
    }
    
    // Getters
    public function getWidth(): ?int { return $this->width; }
    public function getHeight(): ?int { return $this->height; }
    public function getCaption(): ?string { return $this->caption; }
    public function getDescription(): ?string { return $this->description; }
    public function getName(): ?string { return $this->name; }
}
