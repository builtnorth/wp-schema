<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Models;

/**
 * Address Value Object
 * 
 * Represents a postal address for schema markup.
 * 
 * @since 1.0.0
 */
class Address
{
    public function __construct(
        private ?string $streetAddress = null,
        private ?string $addressLocality = null,
        private ?string $addressRegion = null,
        private ?string $postalCode = null,
        private ?string $addressCountry = null,
        private ?string $postOfficeBoxNumber = null
    ) {}
    
    /**
     * Create Address from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            streetAddress: $data['streetAddress'] ?? $data['street'] ?? null,
            addressLocality: $data['addressLocality'] ?? $data['city'] ?? null,
            addressRegion: $data['addressRegion'] ?? $data['state'] ?? $data['region'] ?? null,
            postalCode: $data['postalCode'] ?? $data['zip'] ?? $data['postal_code'] ?? null,
            addressCountry: $data['addressCountry'] ?? $data['country'] ?? null,
            postOfficeBoxNumber: $data['postOfficeBoxNumber'] ?? $data['po_box'] ?? null
        );
    }
    
    /**
     * Convert to Schema.org format
     */
    public function toArray(): array
    {
        if ($this->isEmpty()) {
            return [];
        }
        
        $address = ['@type' => 'PostalAddress'];
        
        if ($this->streetAddress) $address['streetAddress'] = $this->streetAddress;
        if ($this->addressLocality) $address['addressLocality'] = $this->addressLocality;
        if ($this->addressRegion) $address['addressRegion'] = $this->addressRegion;
        if ($this->postalCode) $address['postalCode'] = $this->postalCode;
        if ($this->addressCountry) $address['addressCountry'] = $this->addressCountry;
        if ($this->postOfficeBoxNumber) $address['postOfficeBoxNumber'] = $this->postOfficeBoxNumber;
        
        return $address;
    }
    
    /**
     * Check if address is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->streetAddress) && 
               empty($this->addressLocality) && 
               empty($this->addressRegion) && 
               empty($this->postalCode) &&
               empty($this->addressCountry) &&
               empty($this->postOfficeBoxNumber);
    }
    
    /**
     * Get formatted address string
     */
    public function toString(): string
    {
        $parts = array_filter([
            $this->streetAddress,
            $this->addressLocality,
            $this->addressRegion,
            $this->postalCode,
            $this->addressCountry
        ]);
        
        return implode(', ', $parts);
    }
    
    /**
     * Validate address
     */
    public function isValid(): bool
    {
        // At minimum, need city or postal code
        return !empty($this->addressLocality) || !empty($this->postalCode);
    }
    
    // Getters
    public function getStreetAddress(): ?string { return $this->streetAddress; }
    public function getAddressLocality(): ?string { return $this->addressLocality; }
    public function getAddressRegion(): ?string { return $this->addressRegion; }
    public function getPostalCode(): ?string { return $this->postalCode; }
    public function getAddressCountry(): ?string { return $this->addressCountry; }
    public function getPostOfficeBoxNumber(): ?string { return $this->postOfficeBoxNumber; }
}
