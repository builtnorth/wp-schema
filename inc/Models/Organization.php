<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Models;

use BuiltNorth\Schema\Contracts\SchemaModelInterface;

/**
 * Organization Schema Model
 * 
 * Type-safe representation of an Organization schema.
 * 
 * @since 1.0.0
 */
class Organization implements SchemaModelInterface
{
    /** @var array<string> */
    private array $sameAs = [];
    
    /** @var array<mixed> */
    private array $contactPoints = [];
    
    /** @var array<mixed> */
    private array $openingHours = [];
    
    public function __construct(
        private string $name,
        private string $type = 'Organization',
        private ?string $description = null,
        private ?string $url = null,
        private ?Address $address = null,
        private ?string $telephone = null,
        private ?string $email = null,
        private ?ImageObject $logo = null,
        private ?GeoCoordinates $geo = null
    ) {}
    
    /**
     * Create Organization from array data
     */
    public static function fromArray(array $data): self
    {
        $organization = new self(
            name: $data['name'] ?? '',
            type: $data['@type'] ?? $data['type'] ?? 'Organization',
            description: $data['description'] ?? null,
            url: $data['url'] ?? null,
            telephone: $data['telephone'] ?? $data['phone'] ?? null,
            email: $data['email'] ?? null
        );
        
        // Handle address
        if (!empty($data['address'])) {
            if ($data['address'] instanceof Address) {
                $organization->setAddress($data['address']);
            } elseif (is_array($data['address'])) {
                $organization->setAddress(Address::fromArray($data['address']));
            }
        }
        
        // Handle logo
        if (!empty($data['logo'])) {
            if ($data['logo'] instanceof ImageObject) {
                $organization->setLogo($data['logo']);
            } elseif (is_array($data['logo'])) {
                $organization->setLogo(ImageObject::fromArray($data['logo']));
            } elseif (is_string($data['logo'])) {
                $organization->setLogo(ImageObject::fromUrl($data['logo']));
            }
        }
        
        // Handle geo coordinates
        if (!empty($data['geo'])) {
            if ($data['geo'] instanceof GeoCoordinates) {
                $organization->setGeo($data['geo']);
            } elseif (is_array($data['geo'])) {
                $organization->setGeo(GeoCoordinates::fromArray($data['geo']));
            }
        }
        
        // Handle social media
        if (!empty($data['sameAs'])) {
            $organization->setSameAs($data['sameAs']);
        }
        
        // Handle contact points
        if (!empty($data['contactPoint'])) {
            $organization->setContactPoints($data['contactPoint']);
        }
        
        // Handle opening hours
        if (!empty($data['openingHoursSpecification'])) {
            $organization->setOpeningHours($data['openingHoursSpecification']);
        }
        
        return $organization;
    }
    
    /**
     * Convert to Schema.org array format
     */
    public function toArray(): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $this->type,
            'name' => $this->name
        ];
        
        if ($this->description) $schema['description'] = $this->description;
        if ($this->url) $schema['url'] = $this->url;
        if ($this->telephone) $schema['telephone'] = $this->telephone;
        if ($this->email) $schema['email'] = $this->email;
        
        if ($this->address && !$this->address->isEmpty()) {
            $schema['address'] = $this->address->toArray();
        }
        
        if ($this->logo) {
            $schema['logo'] = $this->logo->toArray();
        }
        
        if ($this->geo) {
            $schema['geo'] = $this->geo->toArray();
        }
        
        if (!empty($this->sameAs)) {
            $schema['sameAs'] = $this->sameAs;
        }
        
        if (!empty($this->contactPoints)) {
            $schema['contactPoint'] = $this->contactPoints;
        }
        
        if (!empty($this->openingHours)) {
            $schema['openingHoursSpecification'] = $this->openingHours;
        }
        
        return $schema;
    }
    
    /**
     * Validate the organization
     */
    public function isValid(): bool
    {
        return !empty($this->name) && !empty($this->type);
    }
    
    /**
     * Get validation errors
     */
    public function getValidationErrors(): array
    {
        $errors = [];
        
        if (empty($this->name)) {
            $errors[] = 'Organization name is required';
        }
        
        if (empty($this->type)) {
            $errors[] = 'Organization type is required';
        }
        
        if ($this->url && !filter_var($this->url, FILTER_VALIDATE_URL)) {
            $errors[] = 'Invalid URL format';
        }
        
        if ($this->email && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        if ($this->address && !$this->address->isValid()) {
            $errors[] = 'Invalid address';
        }
        
        return $errors;
    }
    
    // Setters with fluent interface
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }
    
    public function setUrl(?string $url): self
    {
        $this->url = $url;
        return $this;
    }
    
    public function setAddress(?Address $address): self
    {
        $this->address = $address;
        return $this;
    }
    
    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }
    
    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }
    
    public function setLogo(?ImageObject $logo): self
    {
        $this->logo = $logo;
        return $this;
    }
    
    public function setGeo(?GeoCoordinates $geo): self
    {
        $this->geo = $geo;
        return $this;
    }
    
    public function setSameAs(array $sameAs): self
    {
        $this->sameAs = array_filter($sameAs, 'is_string');
        return $this;
    }
    
    public function addSocialMedia(string $url): self
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $this->sameAs[] = $url;
            $this->sameAs = array_unique($this->sameAs);
        }
        return $this;
    }
    
    public function setContactPoints(array $contactPoints): self
    {
        $this->contactPoints = $contactPoints;
        return $this;
    }
    
    public function setOpeningHours(array $openingHours): self
    {
        $this->openingHours = $openingHours;
        return $this;
    }
    
    // Getters
    public function getName(): string { return $this->name; }
    public function getType(): string { return $this->type; }
    public function getDescription(): ?string { return $this->description; }
    public function getUrl(): ?string { return $this->url; }
    public function getAddress(): ?Address { return $this->address; }
    public function getTelephone(): ?string { return $this->telephone; }
    public function getEmail(): ?string { return $this->email; }
    public function getLogo(): ?ImageObject { return $this->logo; }
    public function getGeo(): ?GeoCoordinates { return $this->geo; }
    public function getSameAs(): array { return $this->sameAs; }
    public function getContactPoints(): array { return $this->contactPoints; }
    public function getOpeningHours(): array { return $this->openingHours; }
}
