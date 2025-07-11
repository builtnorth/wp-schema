<?php

namespace BuiltNorth\Schema\Generators\Generators;

/**
 * Organization Schema Generator
 * 
 * Generates comprehensive Organization schema from various data structures
 * Supports logos, contact info, social media, and business details
 */
class OrganizationGenerator extends BaseGenerator
{
	/**
	 * Generate Organization schema from data
	 *
	 * @param array $data Organization data
	 * @param array $options Generation options
	 * @return string JSON-LD schema markup
	 */
	public static function generate($data, $options = [])
	{
		$schema_data = [
			'name' => self::sanitize_text($data['name'] ?? ''),
			'url' => $data['url'] ?? get_site_url(),
		];

		// Handle logo with comprehensive support
		$logo_data = self::process_logo($data, $options);
		if (!empty($logo_data)) {
			$schema_data['logo'] = $logo_data;
		}

		// Add contact information
		$contact_data = self::process_contact_info($data);
		if (!empty($contact_data)) {
			$schema_data = array_merge($schema_data, $contact_data);
		}

		// Add address information
		$address_data = self::process_address($data);
		if (!empty($address_data)) {
			$schema_data['address'] = $address_data;
		}

		// Add social media links
		$social_data = self::process_social_media($data);
		if (!empty($social_data)) {
			$schema_data['sameAs'] = $social_data;
		}

		// Add optional fields
		$optional_fields = [
			'description' => 'description',
			'foundingDate' => 'founding_date',
			'foundingDate' => 'established',
			'numberOfEmployees' => 'employee_count',
			'numberOfEmployees' => 'employees',
			'areaServed' => 'area_served',
			'serviceArea' => 'service_area',
			'knowsAbout' => 'expertise',
			'knowsAbout' => 'specialties',
			'award' => 'awards',
			'hasOfferCatalog' => 'services',
			'hasOfferCatalog' => 'products'
		];

		$schema_data = self::merge_optional_fields($schema_data, $data, $optional_fields);

		// Add business hours if available
		if (!empty($data['business_hours']) || !empty($data['hours'])) {
			$schema_data['openingHours'] = self::process_business_hours($data);
		}

		// Add founding location if available
		if (!empty($data['founding_location'])) {
			$schema_data['foundingLocation'] = self::create_nested_object('Place', [
				'name' => $data['founding_location']
			]);
		}

		// Add parent organization if available
		if (!empty($data['parent_organization'])) {
			$schema_data['parentOrganization'] = self::create_nested_object('Organization', [
				'name' => $data['parent_organization']
			]);
		}

		// Add subsidiaries if available
		if (!empty($data['subsidiaries'])) {
			$schema_data['subOrganization'] = self::process_subsidiaries($data['subsidiaries']);
		}

		return self::create_schema(self::add_context($schema_data, 'Organization'));
	}

	/**
	 * Process logo data with comprehensive support
	 *
	 * @param array $data Organization data
	 * @param array $options Generation options
	 * @return array|string Logo data
	 */
	private static function process_logo($data, $options = [])
	{
		// Handle single logo URL
		if (!empty($data['logo']) && is_string($data['logo'])) {
			return $data['logo'];
		}

		// Handle logo object with multiple formats
		if (!empty($data['logo']) && is_array($data['logo'])) {
			$logo_data = $data['logo'];
			
			// If it's already a structured logo object
			if (isset($logo_data['@type']) && $logo_data['@type'] === 'ImageObject') {
				return $logo_data;
			}

			// Create structured logo object
			$structured_logo = [
				'@type' => 'ImageObject',
				'url' => $logo_data['url'] ?? $logo_data['src'] ?? ''
			];

			// Add optional logo properties
			if (!empty($logo_data['width'])) {
				$structured_logo['width'] = (int) $logo_data['width'];
			}
			if (!empty($logo_data['height'])) {
				$structured_logo['height'] = (int) $logo_data['height'];
			}
			if (!empty($logo_data['alt'])) {
				$structured_logo['caption'] = self::sanitize_text($logo_data['alt']);
			}

			return $structured_logo;
		}

		// Handle multiple logo formats
		if (!empty($data['logos']) && is_array($data['logos'])) {
			$logos = [];
			foreach ($data['logos'] as $logo) {
				if (!empty($logo['url']) || !empty($logo['src'])) {
					$logos[] = self::process_logo(['logo' => $logo], $options);
				}
			}
			return $logos;
		}

		// Try to get logo from WordPress customizer or theme options
		if (empty($data['logo'])) {
			$custom_logo_id = get_theme_mod('custom_logo');
			if ($custom_logo_id) {
				$logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
				if ($logo_url) {
					return $logo_url;
				}
			}
		}

		return '';
	}

	/**
	 * Process contact information
	 *
	 * @param array $data Organization data
	 * @return array Contact data
	 */
	private static function process_contact_info($data)
	{
		$contact_data = [];

		// Handle telephone
		if (!empty($data['telephone'])) {
			$contact_data['telephone'] = self::sanitize_text($data['telephone']);
		}

		// Handle email
		if (!empty($data['email'])) {
			$contact_data['email'] = self::sanitize_text($data['email']);
		}

		// Handle contact point
		if (!empty($data['contact_point'])) {
			$contact_data['contactPoint'] = self::process_contact_point($data['contact_point']);
		}

		return $contact_data;
	}

	/**
	 * Process contact point data
	 *
	 * @param array $contact_point Contact point data
	 * @return array Contact point object
	 */
	private static function process_contact_point($contact_point)
	{
		if (is_string($contact_point)) {
			return [
				'@type' => 'ContactPoint',
				'telephone' => self::sanitize_text($contact_point)
			];
		}

		if (is_array($contact_point)) {
			$point_data = [
				'@type' => 'ContactPoint'
			];

			$optional_fields = [
				'telephone' => 'telephone',
				'email' => 'email',
				'contactType' => 'contact_type',
				'contactType' => 'type',
				'availableLanguage' => 'language',
				'availableLanguage' => 'languages'
			];

			return self::merge_optional_fields($point_data, $contact_point, $optional_fields);
		}

		return [];
	}

	/**
	 * Process address information
	 *
	 * @param array $data Organization data
	 * @return array Address data
	 */
	private static function process_address($data)
	{
		// Handle structured address
		if (!empty($data['address']) && is_array($data['address'])) {
			$address_data = [
				'@type' => 'PostalAddress'
			];

			$address_fields = [
				'streetAddress' => 'street',
				'streetAddress' => 'street_address',
				'addressLocality' => 'city',
				'addressLocality' => 'locality',
				'addressRegion' => 'state',
				'addressRegion' => 'region',
				'postalCode' => 'zip',
				'postalCode' => 'postal_code',
				'addressCountry' => 'country'
			];

			return self::merge_optional_fields($address_data, $data['address'], $address_fields);
		}

		// Handle string address
		if (!empty($data['address']) && is_string($data['address'])) {
			return [
				'@type' => 'PostalAddress',
				'streetAddress' => self::sanitize_text($data['address'])
			];
		}

		return [];
	}

	/**
	 * Process social media links
	 *
	 * @param array $data Organization data
	 * @return array Social media URLs
	 */
	private static function process_social_media($data)
	{
		$social_media = [];

		// Handle array of social media URLs
		if (!empty($data['social_media']) && is_array($data['social_media'])) {
			foreach ($data['social_media'] as $platform => $url) {
				if (!empty($url)) {
					$social_media[] = $url;
				}
			}
		}

		// Handle individual social media fields
		$social_fields = [
			'facebook' => 'facebook_url',
			'twitter' => 'twitter_url',
			'linkedin' => 'linkedin_url',
			'instagram' => 'instagram_url',
			'youtube' => 'youtube_url',
			'github' => 'github_url'
		];

		foreach ($social_fields as $platform => $field) {
			if (!empty($data[$field]) || !empty($data[$platform])) {
				$url = $data[$field] ?? $data[$platform];
				if (!empty($url)) {
					$social_media[] = $url;
				}
			}
		}

		return $social_media;
	}

	/**
	 * Process business hours
	 *
	 * @param array $data Organization data
	 * @return array Business hours
	 */
	private static function process_business_hours($data)
	{
		$hours = $data['business_hours'] ?? $data['hours'] ?? [];

		if (is_string($hours)) {
			return [$hours];
		}

		if (is_array($hours)) {
			$formatted_hours = [];
			foreach ($hours as $day => $time) {
				if (!empty($time)) {
					$formatted_hours[] = $time;
				}
			}
			return $formatted_hours;
		}

		return [];
	}

	/**
	 * Process subsidiaries
	 *
	 * @param array $subsidiaries Subsidiaries data
	 * @return array Subsidiaries array
	 */
	private static function process_subsidiaries($subsidiaries)
	{
		$processed = [];

		foreach ($subsidiaries as $subsidiary) {
			if (is_string($subsidiary)) {
				$processed[] = self::create_nested_object('Organization', [
					'name' => $subsidiary
				]);
			} elseif (is_array($subsidiary)) {
				$processed[] = self::create_nested_object('Organization', $subsidiary);
			}
		}

		return $processed;
	}
} 