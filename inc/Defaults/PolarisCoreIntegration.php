<?php

namespace BuiltNorth\Schema\Defaults;

/**
 * Polaris Core Integration
 * 
 * Provides automatic schema data generation by integrating with the Polaris framework.
 * This integration uses Polaris APIs and classes to gather organization data, theme settings,
 * and other framework-specific information for schema generation.
 */
class PolarisCoreIntegration extends BaseIntegration
{
    /**
     * Integration name
     *
     * @var string
     */
    protected static $integration_name = 'polaris_core';

    /**
     * Register WordPress hooks for Polaris Core integration
     *
     * @return void
     */
    protected static function register_hooks()
    {
        // Provide context-based schemas using Polaris framework
        add_filter('wp_schema_context_schemas', [self::class, 'provide_context_schemas'], 10, 3);
        
        // Provide schema data for organization types using Polaris framework
        add_filter('wp_schema_data_for_type', [self::class, 'provide_polaris_data'], 10, 3);
        
        // Enhance existing schemas with Polaris framework data
        add_filter('wp_schema_final_schema', [self::class, 'enhance_with_polaris_data'], 10, 4);
    }

    /**
     * Provide context-based schemas using Polaris framework
     *
     * @param array $schemas Existing schemas
     * @param string $context Current context
     * @param array $options Generation options
     * @return array Modified schemas
     */
    public static function provide_context_schemas($schemas, $context, $options)
    {
        $polaris_data = self::get_polaris_framework_data();
        if (empty($polaris_data)) {
            return $schemas;
        }

        // Create organization schema with Polaris data
        $org_schema = [
            '@context' => 'https://schema.org',
            '@type' => $polaris_data['@type'] ?? $polaris_data['business_type'] ?? 'Organization',
            'name' => $polaris_data['name'] ?? get_bloginfo('name'),
            'url' => home_url('/'),
        ];

        // Add description if available
        if (!empty($polaris_data['description'])) {
            $org_schema['description'] = $polaris_data['description'];
        }

        // Add logo if available
        if (!empty($polaris_data['logo'])) {
            $org_schema['logo'] = [
                '@type' => 'ImageObject',
                'url' => $polaris_data['logo']
            ];
        }

        // Add contact information
        if (!empty($polaris_data['contact'])) {
            $org_schema = array_merge($org_schema, $polaris_data['contact']);
        }

        // Add address
        if (!empty($polaris_data['address'])) {
            $org_schema['address'] = $polaris_data['address'];
        }

        // Add geo coordinates
        if (!empty($polaris_data['geo'])) {
            $org_schema['geo'] = $polaris_data['geo'];
        }

        // Add social media
        if (!empty($polaris_data['social_media'])) {
            $org_schema['sameAs'] = $polaris_data['social_media'];
        }

        // Add business hours
        if (!empty($polaris_data['business_hours'])) {
            $org_schema['openingHoursSpecification'] = $polaris_data['business_hours'];
        }

        // Add organization schema to the beginning of the array
        array_unshift($schemas, $org_schema);

        return $schemas;
    }

    /**
     * Provide schema data using Polaris framework
     *
     * @param array|null $custom_data Custom data
     * @param string $schema_type Schema type
     * @param array $options Generation options
     * @return array|null Schema data
     */
    public static function provide_polaris_data($custom_data, $schema_type, $options)
    {
        // Provide data for any schema type that needs organization information

        $polaris_data = self::get_polaris_framework_data();
        if (empty($polaris_data)) {
            return $custom_data;
        }

        $schema_data = [];

        // Basic organization information from Polaris
        if (!empty($polaris_data['name'])) {
            $schema_data['name'] = $polaris_data['name'];
        }
		else {
			$schema_data['name'] = get_bloginfo('name');
		}

        if (!empty($polaris_data['description'])) {
            $schema_data['description'] = $polaris_data['description'];
        }

        // Logo from Polaris theme settings
        if (!empty($polaris_data['logo'])) {
            $schema_data['logo'] = [
                '@type' => 'ImageObject',
                'url' => $polaris_data['logo']
            ];
        }

        // Business type from Polaris organization settings
        if (!empty($polaris_data['business_type'])) {
            $schema_data['@type'] = $polaris_data['business_type'];
        }

        // Contact information from Polaris
        if (!empty($polaris_data['contact'])) {
            $schema_data = array_merge($schema_data, $polaris_data['contact']);
        }

        // Address from Polaris
        if (!empty($polaris_data['address'])) {
            $schema_data['address'] = $polaris_data['address'];
        }

        // Geo coordinates from Polaris
        if (!empty($polaris_data['geo'])) {
            $schema_data['geo'] = $polaris_data['geo'];
        }

        // Social media from Polaris
        if (!empty($polaris_data['social_media'])) {
            $schema_data['sameAs'] = $polaris_data['social_media'];
        }

        // Business hours from Polaris
        if (!empty($polaris_data['business_hours'])) {
            $schema_data['openingHoursSpecification'] = $polaris_data['business_hours'];
        }

        // Merge with existing data
        if (!empty($custom_data)) {
            $schema_data = array_merge($custom_data, $schema_data);
        }

        // Debug: Log the final schema data
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('PolarisCoreIntegration - Final schema data: ' . json_encode($schema_data));
        }

        return $schema_data;
    }

    /**
     * Enhance existing schemas with Polaris framework data
     *
     * @param array $schema Final schema
     * @param mixed $content Content
     * @param string $type Schema type
     * @param array $options Generation options
     * @return array Modified schema
     */
    public static function enhance_with_polaris_data($schema, $content, $type, $options)
    {
        // Enhance any schema that could benefit from organization information

        $polaris_data = self::get_polaris_framework_data();
        if (empty($polaris_data)) {
            return $schema;
        }

        $enhanced_schema = $schema;

        // Add business type if available
        if (!empty($polaris_data['business_type'])) {
            $enhanced_schema['@type'] = $polaris_data['business_type'];
        }

        // Add contact information if available
        if (!empty($polaris_data['contact'])) {
            $enhanced_schema = array_merge($enhanced_schema, $polaris_data['contact']);
        }

        // Add address if available
        if (!empty($polaris_data['address'])) {
            $enhanced_schema['address'] = $polaris_data['address'];
        }

        // Add geo coordinates if available
        if (!empty($polaris_data['geo'])) {
            $enhanced_schema['geo'] = $polaris_data['geo'];
        }

        // Add social media if available
        if (!empty($polaris_data['social_media'])) {
            $enhanced_schema['sameAs'] = $polaris_data['social_media'];
        }

        // Add business hours if available
        if (!empty($polaris_data['business_hours'])) {
            $enhanced_schema['openingHoursSpecification'] = $polaris_data['business_hours'];
        }

        return $enhanced_schema;
    }

    /**
     * Get data from Polaris framework
     *
     * @return array Polaris framework data
     */
    private static function get_polaris_framework_data()
    {
        $data = [];

        // Get organization data from Polaris framework
        $organization = get_option('polaris_organization', []);
        
        // Debug: Log the organization data structure
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('PolarisCoreIntegration - Organization data: ' . json_encode($organization));
            error_log('PolarisCoreIntegration - Organization keys: ' . implode(', ', array_keys($organization)));
            if (!empty($organization['information'])) {
                error_log('PolarisCoreIntegration - Information keys: ' . implode(', ', array_keys($organization['information'])));
            }
        }
        
        if (!empty($organization)) {
            // Basic information
            if (!empty($organization['information']['name'])) {
                $data['name'] = $organization['information']['name'];
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('PolarisCoreIntegration - Found name in information: ' . $organization['information']['name']);
                }
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('PolarisCoreIntegration - No name found in information section');
                    error_log('PolarisCoreIntegration - Available information fields: ' . json_encode($organization['information'] ?? []));
                }
            }

            if (!empty($organization['information']['description'])) {
                $data['description'] = $organization['information']['description'];
            }

            // Business type - try multiple locations
            if (!empty($organization['information']['business_type'])) {
                $data['business_type'] = $organization['information']['business_type'];
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('PolarisCoreIntegration - Found business_type in information: ' . $organization['information']['business_type']);
                }
            } elseif (!empty($organization['brand']['business_type'])) {
                $data['business_type'] = $organization['brand']['business_type'];
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('PolarisCoreIntegration - Found business_type in brand: ' . $organization['brand']['business_type']);
                }
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('PolarisCoreIntegration - No business_type found in organization data');
                }
            }

            // Contact information
            $contact_data = [];
            if (!empty($organization['information']['phone'])) {
                $contact_data['telephone'] = $organization['information']['phone'];
            }
            if (!empty($organization['information']['email'])) {
                $contact_data['email'] = $organization['information']['email'];
            }
            if (!empty($contact_data)) {
                $data['contact'] = $contact_data;
            }

            // Address and Geo Coordinates
            if (!empty($organization['location'])) {
                $location = $organization['location'];
                
                // Process address
                if (!empty($location['address_street']) || !empty($location['address_city'])) {
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
                    
                    if (count($address) > 1) {
                        $data['address'] = $address;
                    }
                }
                
                // Process geo coordinates
                if (!empty($location['location_lat']) && !empty($location['location_lng'])) {
                    $data['geo'] = [
                        '@type' => 'GeoCoordinates',
                        'latitude' => (float) $location['location_lat'],
                        'longitude' => (float) $location['location_lng']
                    ];
                    
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('PolarisCoreIntegration - Found geo coordinates: lat=' . $location['location_lat'] . ', lng=' . $location['location_lng']);
                    }
                } else {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('PolarisCoreIntegration - No geo coordinates found in location data');
                    }
                }
            }

            // Social media from new structure
            if (!empty($organization['social_media']) && is_array($organization['social_media'])) {
                $social_urls = [];
                foreach ($organization['social_media'] as $social_item) {
                    if (!empty($social_item['url'])) {
                        $social_urls[] = $social_item['url'];
                    }
                }
                
                if (!empty($social_urls)) {
                    $data['social_media'] = $social_urls;
                }
            }

            // Business hours from new structure
            if (!empty($organization['hours']) && !empty($organization['hours']['enabled'])) {
                $hours_data = [];
                $days_of_week = [
                    'monday' => 'Monday',
                    'tuesday' => 'Tuesday',
                    'wednesday' => 'Wednesday',
                    'thursday' => 'Thursday',
                    'friday' => 'Friday',
                    'saturday' => 'Saturday',
                    'sunday' => 'Sunday'
                ];

                foreach ($days_of_week as $day_key => $day_name) {
                    $day_data = $organization['hours'][$day_key] ?? [];
                    
                    // Check if day is not closed
                    if (empty($day_data['closed'])) {
                        $opens = null;
                        $closes = null;
                        
                        if (!empty($day_data['open_24_hours'])) {
                            $opens = '00:00';
                            $closes = '23:59';
                        } elseif (!empty($day_data['open_time']) && !empty($day_data['close_time'])) {
                            $opens = $day_data['open_time'];
                            $closes = $day_data['close_time'];
                        }
                        
                        if ($opens && $closes) {
                            $hours_data[] = [
                                '@type' => 'OpeningHoursSpecification',
                                'dayOfWeek' => $day_name,
                                'opens' => $opens,
                                'closes' => $closes
                            ];
                        }
                    }
                }
                
                if (!empty($hours_data)) {
                    $data['business_hours'] = $hours_data;
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('PolarisCoreIntegration - Business hours: ' . json_encode($hours_data));
                    }
                }
            }
        }

        // Get logo from Polaris brand settings
        $logo_url = self::get_polaris_logo_url();
        if ($logo_url) {
            $data['logo'] = $logo_url;
        }

        // Debug: Log final data
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('PolarisCoreIntegration - Final data: ' . json_encode($data));
        }

        return $data;
    }

    /**
     * Get logo URL from Polaris brand settings
     *
     * @return string|null Logo URL
     */
    private static function get_polaris_logo_url()
    {
        // Try to get logo from Polaris brand settings
        $organization = get_option('polaris_organization', []);
        if (!empty($organization['brand']['logo'])) {
            $logo_id = $organization['brand']['logo'];
            if ($logo_id) {
                return wp_get_attachment_image_url($logo_id, 'full');
            }
        }

        // Fallback to theme customizer
        $logo_id = get_theme_mod('custom_logo');
        if ($logo_id) {
            return wp_get_attachment_image_url($logo_id, 'full');
        }

        return null;
    }

    /**
     * Check if Polaris Core is available
     *
     * @return bool
     */
    public static function is_available()
    {
        return class_exists('Polaris\App') || function_exists('polaris_init');
    }

    /**
     * Get integration description
     *
     * @return string
     */
    public static function get_description()
    {
        return 'Schema data from Polaris framework (organization settings, theme data, business information)';
    }

    /**
     * Get supported schema types
     *
     * @return array
     */
    public static function get_supported_schema_types()
    {
        return ['Organization', 'LocalBusiness', 'Person'];
    }
} 