<?php

namespace BuiltNorth\Utility\Utilities\SchemaGenerator\Generators;

/**
 * LocalBusiness Schema Generator
 * 
 * Generates LocalBusiness schema from various data structures
 * Supports logos, contact info, business hours, and location details
 */
class LocalBusinessGenerator extends BaseGenerator
{
	/**
	 * Generate LocalBusiness schema from data
	 *
	 * @param array $data LocalBusiness data
	 * @param array $options Generation options
	 * @return string JSON-LD schema markup
	 */
	public static function generate($data, $options = [])
	{
		// Determine the specific business type
		$business_type = self::determine_business_type($data);
		
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

		// Add geo coordinates if available
		$geo_data = self::process_geo_coordinates($data);
		if (!empty($geo_data)) {
			$schema_data['geo'] = $geo_data;
		}

		// Add business hours
		$hours_data = self::process_business_hours($data);
		if (!empty($hours_data)) {
			$schema_data['openingHours'] = $hours_data;
		}

		// Add social media links
		$social_data = self::process_social_media($data);
		if (!empty($social_data)) {
			$schema_data['sameAs'] = $social_data;
		}

		// Add optional fields
		$optional_fields = [
			'description' => 'description',
			'priceRange' => 'price_range',
			'priceRange' => 'pricing',
			'paymentAccepted' => 'payment_methods',
			'paymentAccepted' => 'payment_accepted',
			'currenciesAccepted' => 'currencies',
			'currenciesAccepted' => 'currencies_accepted',
			'areaServed' => 'area_served',
			'serviceArea' => 'service_area',
			'hasOfferCatalog' => 'services',
			'hasOfferCatalog' => 'products',
			'makesOffer' => 'offers',
			'hasMenu' => 'menu_url',
			'hasMenu' => 'menu',
			'acceptsReservations' => 'accepts_reservations',
			'acceptsReservations' => 'reservations',
			'servesCuisine' => 'cuisine',
			'servesCuisine' => 'cuisine_type',
			'hasDriveThrough' => 'drive_through',
			'hasDriveThrough' => 'drive_thru',
			'deliveryAvailable' => 'delivery',
			'takeoutAvailable' => 'takeout',
			'wheelchairAccessible' => 'wheelchair_accessible',
			'wheelchairAccessible' => 'accessible'
		];

		$schema_data = self::merge_optional_fields($schema_data, $data, $optional_fields);

		// Add specific business type fields
		$schema_data = self::add_business_specific_fields($schema_data, $data, $business_type);

		return self::create_schema(self::add_context($schema_data, $business_type));
	}

	/**
	 * Determine the specific business type
	 *
	 * @param array $data Business data
	 * @return string Business type
	 */
	private static function determine_business_type($data)
	{
		// Check for explicit business type
		if (!empty($data['business_type'])) {
			$type = ucfirst($data['business_type']);
			if (in_array($type, [
				'Restaurant', 'Cafe', 'Bar', 'FoodEstablishment',
				'Store', 'RetailStore', 'ClothingStore', 'ElectronicsStore',
				'AutomotiveBusiness', 'AutoRepair', 'AutoWash',
				'HealthAndBeautyBusiness', 'BeautySalon', 'HairSalon',
				'ProfessionalService', 'LegalService', 'AccountingService',
				'FinancialService', 'BankOrCreditUnion',
				'MedicalBusiness', 'Dentist', 'Physician',
				'RealEstateAgent', 'TravelAgency', 'TouristInformationCenter'
			])) {
				return $type;
			}
		}

		// Check for business category indicators
		if (!empty($data['category'])) {
			$category = strtolower($data['category']);
			switch ($category) {
				case 'restaurant':
				case 'food':
				case 'cafe':
				case 'bar':
					return 'Restaurant';
				case 'store':
				case 'retail':
				case 'shop':
					return 'Store';
				case 'auto':
				case 'automotive':
				case 'car':
					return 'AutomotiveBusiness';
				case 'beauty':
				case 'salon':
				case 'spa':
					return 'BeautySalon';
				case 'medical':
				case 'health':
				case 'dental':
					return 'MedicalBusiness';
				case 'legal':
				case 'law':
					return 'LegalService';
				case 'financial':
				case 'bank':
					return 'FinancialService';
				case 'real estate':
				case 'realestate':
					return 'RealEstateAgent';
			}
		}

		// Default to LocalBusiness
		return 'LocalBusiness';
	}

	/**
	 * Process logo data with comprehensive support
	 *
	 * @param array $data Business data
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
	 * @param array $data Business data
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
	 * @param array $data Business data
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
	 * Process geo coordinates
	 *
	 * @param array $data Business data
	 * @return array Geo coordinates
	 */
	private static function process_geo_coordinates($data)
	{
		if (!empty($data['latitude']) && !empty($data['longitude'])) {
			return [
				'@type' => 'GeoCoordinates',
				'latitude' => (float) $data['latitude'],
				'longitude' => (float) $data['longitude']
			];
		}

		if (!empty($data['geo']) && is_array($data['geo'])) {
			$geo_data = $data['geo'];
			if (!empty($geo_data['latitude']) && !empty($geo_data['longitude'])) {
				return [
					'@type' => 'GeoCoordinates',
					'latitude' => (float) $geo_data['latitude'],
					'longitude' => (float) $geo_data['longitude']
				];
			}
		}

		return [];
	}

	/**
	 * Process business hours
	 *
	 * @param array $data Business data
	 * @return array Business hours
	 */
	private static function process_business_hours($data)
	{
		$hours = $data['business_hours'] ?? $data['hours'] ?? $data['opening_hours'] ?? [];

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
	 * Process social media links
	 *
	 * @param array $data Business data
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
			'google' => 'google_plus_url'
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
	 * Add business-specific fields
	 *
	 * @param array $schema_data Schema data
	 * @param array $data Business data
	 * @param string $business_type Business type
	 * @return array Updated schema data
	 */
	private static function add_business_specific_fields($schema_data, $data, $business_type)
	{
		switch ($business_type) {
			case 'Restaurant':
			case 'Cafe':
			case 'Bar':
				return self::add_restaurant_fields($schema_data, $data);
			case 'Store':
			case 'RetailStore':
				return self::add_retail_fields($schema_data, $data);
			case 'AutomotiveBusiness':
			case 'AutoRepair':
			case 'AutoWash':
				return self::add_automotive_fields($schema_data, $data);
			case 'BeautySalon':
			case 'HairSalon':
				return self::add_beauty_fields($schema_data, $data);
			case 'MedicalBusiness':
			case 'Dentist':
			case 'Physician':
				return self::add_medical_fields($schema_data, $data);
			default:
				return $schema_data;
		}
	}

	/**
	 * Add restaurant-specific fields
	 *
	 * @param array $schema_data Schema data
	 * @param array $data Business data
	 * @return array Updated schema data
	 */
	private static function add_restaurant_fields($schema_data, $data)
	{
		$restaurant_fields = [
			'servesCuisine' => 'cuisine',
			'servesCuisine' => 'cuisine_type',
			'hasMenu' => 'menu_url',
			'acceptsReservations' => 'accepts_reservations',
			'hasDriveThrough' => 'drive_through',
			'deliveryAvailable' => 'delivery',
			'takeoutAvailable' => 'takeout'
		];

		return self::merge_optional_fields($schema_data, $data, $restaurant_fields);
	}

	/**
	 * Add retail-specific fields
	 *
	 * @param array $schema_data Schema data
	 * @param array $data Business data
	 * @return array Updated schema data
	 */
	private static function add_retail_fields($schema_data, $data)
	{
		$retail_fields = [
			'paymentAccepted' => 'payment_methods',
			'currenciesAccepted' => 'currencies',
			'hasOfferCatalog' => 'products'
		];

		return self::merge_optional_fields($schema_data, $data, $retail_fields);
	}

	/**
	 * Add automotive-specific fields
	 *
	 * @param array $schema_data Schema data
	 * @param array $data Business data
	 * @return array Updated schema data
	 */
	private static function add_automotive_fields($schema_data, $data)
	{
		$automotive_fields = [
			'paymentAccepted' => 'payment_methods',
			'hasOfferCatalog' => 'services'
		];

		return self::merge_optional_fields($schema_data, $data, $automotive_fields);
	}

	/**
	 * Add beauty-specific fields
	 *
	 * @param array $schema_data Schema data
	 * @param array $data Business data
	 * @return array Updated schema data
	 */
	private static function add_beauty_fields($schema_data, $data)
	{
		$beauty_fields = [
			'paymentAccepted' => 'payment_methods',
			'acceptsReservations' => 'accepts_reservations',
			'hasOfferCatalog' => 'services'
		];

		return self::merge_optional_fields($schema_data, $data, $beauty_fields);
	}

	/**
	 * Add medical-specific fields
	 *
	 * @param array $schema_data Schema data
	 * @param array $data Business data
	 * @return array Updated schema data
	 */
	private static function add_medical_fields($schema_data, $data)
	{
		$medical_fields = [
			'paymentAccepted' => 'payment_methods',
			'acceptsReservations' => 'accepts_reservations',
			'hasOfferCatalog' => 'services',
			'medicalSpecialty' => 'specialty',
			'medicalSpecialty' => 'specialties'
		];

		return self::merge_optional_fields($schema_data, $data, $medical_fields);
	}
} 