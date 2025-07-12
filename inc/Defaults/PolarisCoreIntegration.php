<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Defaults;

use BuiltNorth\Schema\Contracts\DataProviderInterface;
use BuiltNorth\Schema\Models\Organization;
use BuiltNorth\Schema\Models\Address;
use BuiltNorth\Schema\Models\ImageObject;
use BuiltNorth\Schema\Models\GeoCoordinates;
use BuiltNorth\Schema\API\WP_Schema;

/**
 * Polaris Core Data Provider
 * 
 * Clean, extensible integration with the Polaris framework using the new architecture.
 * Provides organization data from Polaris settings through the data provider pattern.
 * 
 * @since 2.0.0
 */
class PolarisCoreIntegration implements DataProviderInterface
{
    private string $providerId = 'polaris_core';
    private int $priority = 5;
    
    /**
     * Initialize the integration
     */
    public static function init(): void
    {
        $instance = new self();
        if (!$instance->isAvailable()) {
            return;
        }
        
        // Register with the new architecture
        WP_Schema::registerProvider($instance);
    }
    
    /**
     * Get provider ID
     */
    public function getProviderId(): string
    {
        return $this->providerId;
    }
    
    /**
     * Get provider priority
     */
    public function getPriority(): int
    {
        return $this->priority;
    }
    
    /**
     * Check if provider is available
     */
    public function isAvailable(): bool
    {
        return class_exists('Polaris\App') || function_exists('polaris_init');
    }
    
    /**
     * Check if we can provide data for this context
     */
    public function canProvide(string $context, array $options = []): bool
    {
        // Provide organization data for all contexts
        return $this->isAvailable() && !empty(self::getPolarisOrganizationData());
    }
    
    /**
     * Provide schema data
     */
    public function provide(string $context, array $options = []): array
    {
        $organizationData = self::getPolarisOrganizationData();
        if (empty($organizationData)) {
            return [];
        }
        
        try {
            $organization = $this->buildOrganizationFromPolarisData($organizationData);
            
            if ($organization && $organization->isValid()) {
                return $organization->toArray();
            }
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('PolarisCoreIntegration error: ' . $e->getMessage());
            }
        }
        
        return [];
    }
    
    /**
     * Get cache key
     */
    public function getCacheKey(string $context, array $options = []): string
    {
        $orgHash = md5(wp_json_encode(get_option('polaris_organization', [])));
        return "polaris_core_{$context}_{$orgHash}";
    }
    
    /**
     * Get supported schema types
     * 
     * Polaris Core provides organization data that can be used across ALL schema types,
     * so we don't restrict to specific types. The canProvide() method determines
     * whether we can provide data for any given context.
     */
    public function getSupportedSchemaTypes(): array
    {
        return []; // Supports all schema types - no restrictions
    }

    /**
     * Build Organization object from Polaris data using value objects
     */
    private function buildOrganizationFromPolarisData(array $data): ?Organization
    {
        $organizationData = [
            'name' => $data['information']['name'] ?? get_bloginfo('name'),
            'description' => $data['information']['description'] ?? null,
            'url' => home_url('/'),
            'email' => $data['information']['email'] ?? null,
            'telephone' => $data['information']['phone'] ?? null
        ];
        
        // Determine business type
        $businessType = $data['information']['business_type'] ?? $data['brand']['business_type'] ?? 'Organization';
        $organizationData['type'] = $businessType;
        
        $organization = Organization::fromArray($organizationData);
        
        // Add logo using ImageObject
        $logoUrl = $this->getPolarisLogoUrl($data);
        if ($logoUrl) {
            $logo = ImageObject::fromUrl($logoUrl);
            if ($logo->isValid()) {
                $organization->setLogo($logo);
            }
        }
        
        // Add address using Address value object
        if (!empty($data['location'])) {
            $address = $this->buildAddressFromLocation($data['location']);
            if ($address && $address->isValid()) {
                $organization->setAddress($address);
                
                // Add geo coordinates
                $coordinates = $this->buildGeoCoordinatesFromLocation($data['location']);
                if ($coordinates && $coordinates->isValid()) {
                    $organization->setGeo($coordinates);
                }
            }
        }
        
        // Add social media
        if (!empty($data['social_media']) && is_array($data['social_media'])) {
            foreach ($data['social_media'] as $social) {
                if (!empty($social['url'])) {
                    $organization->addSocialMedia($social['url']);
                }
            }
        }
        
        // Add business hours
        if (!empty($data['hours']['enabled'])) {
            $hours = $this->buildBusinessHours($data['hours']);
            if (!empty($hours)) {
                $organization->setOpeningHours($hours);
            }
        }
        
        return $organization;
    }

    /**
     * Build Address from Polaris location data
     */
    private function buildAddressFromLocation(array $location): ?Address
    {
        $addressData = [];
        
        if (!empty($location['address_street'])) {
            $addressData['street'] = $location['address_street'];
        }
        if (!empty($location['address_city'])) {
            $addressData['city'] = $location['address_city'];
        }
        if (!empty($location['address_state'])) {
            $addressData['state'] = $location['address_state'];
        }
        if (!empty($location['address_zip'])) {
            $addressData['zip'] = $location['address_zip'];
        }
        if (!empty($location['address_country'])) {
            $addressData['country'] = $location['address_country'];
        }
        
        return !empty($addressData) ? Address::fromArray($addressData) : null;
    }
    
    /**
     * Build GeoCoordinates from Polaris location data
     */
    private function buildGeoCoordinatesFromLocation(array $location): ?GeoCoordinates
    {
        if (empty($location['location_lat']) || empty($location['location_lng'])) {
            return null;
        }
        
        return GeoCoordinates::fromLatLng(
            (float) $location['location_lat'],
            (float) $location['location_lng']
        );
    }

    /**
     * Build business hours from Polaris hours data
     */
    private function buildBusinessHours(array $hours): array
    {
        $hoursData = [];
        $daysOfWeek = [
            'monday' => 'Monday',
            'tuesday' => 'Tuesday', 
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday'
        ];
        
        foreach ($daysOfWeek as $dayKey => $dayName) {
            $dayData = $hours[$dayKey] ?? [];
            
            // Skip if day is closed
            if (!empty($dayData['closed'])) {
                continue;
            }
            
            $opens = null;
            $closes = null;
            
            if (!empty($dayData['open_24_hours'])) {
                $opens = '00:00';
                $closes = '23:59';
            } elseif (!empty($dayData['open_time']) && !empty($dayData['close_time'])) {
                $opens = $dayData['open_time'];
                $closes = $dayData['close_time'];
            }
            
            if ($opens && $closes) {
                $hoursData[] = [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => $dayName,
                    'opens' => $opens,
                    'closes' => $closes
                ];
            }
        }
        
        return $hoursData;
    }

    /**
     * Get Polaris organization data
     */
    private static function getPolarisOrganizationData(): array
    {
        return get_option('polaris_organization', []);
    }
    
    /**
     * Get logo URL from Polaris data
     */
    private function getPolarisLogoUrl(array $data): ?string
    {
        // Try Polaris brand logo first
        if (!empty($data['brand']['logo'])) {
            $logoId = $data['brand']['logo'];
            if ($logoId) {
                $logoUrl = wp_get_attachment_image_url($logoId, 'full');
                if ($logoUrl) {
                    return $logoUrl;
                }
            }
        }
        
        // Fallback to theme customizer
        $logoId = get_theme_mod('custom_logo');
        if ($logoId) {
            return wp_get_attachment_image_url($logoId, 'full') ?: null;
        }
        
        return null;
    }

    /**
     * Get integration description
     */
    public static function getDescription(): string
    {
        return 'Schema data from Polaris framework (organization settings, theme data, business information)';
    }
} 