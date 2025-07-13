<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Integrations;

use BuiltNorth\Schema\Contracts\SchemaProviderInterface;
use DateTime;

/**
 * Polaris Organization Provider
 * 
 * Provides Organization schema from Polaris framework organization data.
 * Reads from polaris_organization option and converts to schema markup.
 * 
 * @since 3.0.0
 */
class PolarisOrganizationProvider implements SchemaProviderInterface
{
    public function can_provide(string $context, array $options = []): bool
    {
        // Only provide if Polaris organization data exists
        return $this->has_organization_data();
    }
    
    public function get_pieces(string $context, array $options = []): array
    {
        $org_data = $this->get_organization_data();
        
        if (empty($org_data)) {
            return [];
        }
        
        return [
            $this->build_organization_piece($org_data, $context)
        ];
    }
    
    public function get_priority(): int
    {
        return 5; // High priority - foundational organization data
    }
    
    /**
     * Check if Polaris organization data exists
     */
    private function has_organization_data(): bool
    {
        $org_data = get_option('polaris_organization', []);
        $info = $org_data['information'] ?? [];
        
        // Consider it valid if we have at least a name
        return !empty($info['name']);
    }
    
    /**
     * Get organization data from Polaris
     */
    private function get_organization_data(): array
    {
        return get_option('polaris_organization', []);
    }
    
    /**
     * Build Organization schema piece
     */
    private function build_organization_piece(array $org_data, string $context): array
    {
        $information = $org_data['information'] ?? [];
        $location = $org_data['location'] ?? [];
        $brand = $org_data['brand'] ?? [];
        $hours = $org_data['hours'] ?? [];
        $social_media = $org_data['social_media'] ?? [];
        
        $piece = [
            '@type' => $this->get_schema_type($information['business_type'] ?? 'Organization'),
            '@id' => home_url('/#organization'),
            'name' => $information['name'] ?? get_bloginfo('name'),
            'url' => home_url('/'),
        ];
        
        // Add description
        if (!empty($information['description'])) {
            $piece['description'] = $information['description'];
        }
        
        // Add contact information
        if (!empty($information['email'])) {
            $piece['email'] = $information['email'];
        }
        
        if (!empty($information['phone'])) {
            $piece['telephone'] = $information['phone'];
        }
        
        // Add address
        $address = $this->build_address($location);
        if (!empty($address)) {
            $piece['address'] = $address;
        }
        
        // Add geo coordinates
        $geo = $this->build_geo_coordinates($location);
        if (!empty($geo)) {
            $piece['geo'] = $geo;
        }
        
        // Add logo
        $logo = $this->get_logo_data($brand);
        if (!empty($logo)) {
            $piece['logo'] = $logo;
        }
        
        // Add business hours
        $opening_hours = $this->build_opening_hours($hours);
        if (!empty($opening_hours)) {
            $piece['openingHours'] = $opening_hours;
        }
        
        // Add social media
        $same_as = $this->build_same_as($social_media);
        if (!empty($same_as)) {
            $piece['sameAs'] = $same_as;
        }
        
        // Add mainEntityOfPage for homepage context
        if (($context && $context === 'home') || is_front_page()) {
            $piece['mainEntityOfPage'] = [
                '@type' => 'WebPage',
                '@id' => home_url('/#webpage')
            ];
        }
        
        return $piece;
    }
    
    /**
     * Get appropriate schema type based on business type
     */
    private function get_schema_type(string $business_type): string
    {
        $type_mapping = [
            'LocalBusiness' => 'LocalBusiness',
            'Restaurant' => 'Restaurant',
            'FoodEstablishment' => 'FoodEstablishment',
            'Store' => 'Store',
            'Corporation' => 'Corporation',
            'NGO' => 'NGO',
            'GovernmentOrganization' => 'GovernmentOrganization',
            'EducationalOrganization' => 'EducationalOrganization',
            'MedicalOrganization' => 'MedicalOrganization',
            'SportsOrganization' => 'SportsOrganization',
            'PerformingGroup' => 'PerformingGroup',
            'Dentist' => 'Dentist',
            'Hospital' => 'Hospital',
            'AutoDealer' => 'AutoDealer',
            'LodgingBusiness' => 'LodgingBusiness',
            'TravelAgency' => 'TravelAgency',
        ];
        
        return $type_mapping[$business_type] ?? 'Organization';
    }
    
    /**
     * Build address schema
     */
    private function build_address(array $location): array
    {
        if (empty($location['address_street']) && empty($location['address_city'])) {
            return [];
        }
        
        $address = [
            '@type' => 'PostalAddress'
        ];
        
        if (!empty($location['address_street'])) {
            $address['streetAddress'] = $location['address_street'];
        }
        
        if (!empty($location['address_city'])) {
            $address['addressLocality'] = $location['address_city'];
        }
        
        if (!empty($location['address_state'])) {
            $address['addressRegion'] = $location['address_state'];
        }
        
        if (!empty($location['address_zip'])) {
            $address['postalCode'] = $location['address_zip'];
        }
        
        if (!empty($location['address_country'])) {
            $address['addressCountry'] = $location['address_country'];
        }
        
        return $address;
    }
    
    /**
     * Build geo coordinates schema
     */
    private function build_geo_coordinates(array $location): array
    {
        $lat = $location['location_lat'] ?? null;
        $lng = $location['location_lng'] ?? null;
        
        if (empty($lat) || empty($lng)) {
            return [];
        }
        
        return [
            '@type' => 'GeoCoordinates',
            'latitude' => (float) $lat,
            'longitude' => (float) $lng
        ];
    }
    
    /**
     * Get logo data
     */
    private function get_logo_data(array $brand): array
    {
        $logo_id = $brand['logo'] ?? null;
        
        if (!$logo_id) {
            // Fallback to WordPress custom logo
            $logo_id = get_theme_mod('custom_logo');
        }
        
        if (!$logo_id) {
            return [];
        }
        
        $logo_url = wp_get_attachment_image_url($logo_id, 'full');
        if (!$logo_url) {
            return [];
        }
        
        return [
            '@type' => 'ImageObject',
            'url' => $logo_url
        ];
    }
    
    /**
     * Build opening hours specification
     */
    private function build_opening_hours(array $hours): array
    {
        if (empty($hours['enabled']) || !empty($hours['always_open'])) {
            return [];
        }
        
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $opening_hours = [];
        
        foreach ($days as $day) {
            $day_data = $hours[$day] ?? [];
            
            if (empty($day_data['closed']) && !empty($day_data['open_time']) && !empty($day_data['close_time'])) {
                $day_abbrev = $this->get_day_abbreviation($day);
                
                if (!empty($day_data['open_24_hours'])) {
                    $opening_hours[] = $day_abbrev . ' 00:00-23:59';
                } else {
                    $open_time = $this->format_time($day_data['open_time'], $hours['is_24_hour_format'] ?? false);
                    $close_time = $this->format_time($day_data['close_time'], $hours['is_24_hour_format'] ?? false);
                    $opening_hours[] = $day_abbrev . ' ' . $open_time . '-' . $close_time;
                }
            }
        }
        
        return $opening_hours;
    }
    
    /**
     * Get day abbreviation for schema
     */
    private function get_day_abbreviation(string $day): string
    {
        $mapping = [
            'monday' => 'Mo',
            'tuesday' => 'Tu', 
            'wednesday' => 'We',
            'thursday' => 'Th',
            'friday' => 'Fr',
            'saturday' => 'Sa',
            'sunday' => 'Su'
        ];
        
        return $mapping[$day] ?? '';
    }
    
    /**
     * Format time for schema
     */
    private function format_time(string $time, bool $is_24_hour = false): string
    {
        // Convert to 24-hour format for schema
        if (!$is_24_hour && strpos($time, ':') !== false) {
            // Assume format is like "9:00 AM" or "17:00"
            $time_parts = explode(' ', $time);
            if (count($time_parts) === 2) {
                list($time_part, $ampm) = $time_parts;
                $time_obj = DateTime::createFromFormat('g:i A', $time);
                if ($time_obj) {
                    return $time_obj->format('H:i');
                }
            }
        }
        
        // Ensure proper format (HH:MM)
        if (strlen($time) === 5 && strpos($time, ':') === 2) {
            return $time;
        }
        
        return $time;
    }
    
    /**
     * Build sameAs array from social media
     */
    private function build_same_as(array $social_media): array
    {
        if (empty($social_media)) {
            return [];
        }
        
        $same_as = [];
        
        foreach ($social_media as $social) {
            if (!empty($social['url']) && filter_var($social['url'], FILTER_VALIDATE_URL)) {
                $same_as[] = $social['url'];
            }
        }
        
        return $same_as;
    }
}