<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Models;

/**
 * GeoCoordinates Value Object
 * 
 * Represents geographic coordinates for schema markup.
 * 
 * @since 1.0.0
 */
class GeoCoordinates
{
    public function __construct(
        private float $latitude,
        private float $longitude,
        private ?float $elevation = null
    ) {}
    
    /**
     * Create GeoCoordinates from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            latitude: (float) ($data['latitude'] ?? $data['lat'] ?? 0),
            longitude: (float) ($data['longitude'] ?? $data['lng'] ?? $data['lon'] ?? 0),
            elevation: isset($data['elevation']) ? (float) $data['elevation'] : null
        );
    }
    
    /**
     * Create from lat/lng pair
     */
    public static function fromLatLng(float $latitude, float $longitude): self
    {
        return new self($latitude, $longitude);
    }
    
    /**
     * Convert to Schema.org format
     */
    public function toArray(): array
    {
        $coordinates = [
            '@type' => 'GeoCoordinates',
            'latitude' => $this->latitude,
            'longitude' => $this->longitude
        ];
        
        if ($this->elevation !== null) {
            $coordinates['elevation'] = $this->elevation;
        }
        
        return $coordinates;
    }
    
    /**
     * Check if coordinates are valid
     */
    public function isValid(): bool
    {
        return $this->latitude >= -90 && $this->latitude <= 90 &&
               $this->longitude >= -180 && $this->longitude <= 180;
    }
    
    /**
     * Get distance to another point in kilometers
     */
    public function distanceTo(GeoCoordinates $other): float
    {
        $earthRadius = 6371; // km
        
        $latDiff = deg2rad($other->latitude - $this->latitude);
        $lngDiff = deg2rad($other->longitude - $this->longitude);
        
        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($other->latitude)) *
             sin($lngDiff / 2) * sin($lngDiff / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
    
    /**
     * Get formatted coordinates string
     */
    public function toString(): string
    {
        return "{$this->latitude},{$this->longitude}";
    }
    
    /**
     * Get Google Maps URL
     */
    public function getGoogleMapsUrl(): string
    {
        return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
    }
    
    // Getters
    public function getLatitude(): float { return $this->latitude; }
    public function getLongitude(): float { return $this->longitude; }
    public function getElevation(): ?float { return $this->elevation; }
}
