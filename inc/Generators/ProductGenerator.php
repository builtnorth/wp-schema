<?php

namespace BuiltNorth\Schema\Generators;

/**
 * Product Schema Generator
 * 
 * Generates Product schema from various data structures
 * Handles products, services, software, digital tools, etc.
 */
class ProductGenerator extends BaseGenerator
{
	/**
	 * Generate Product schema from data
	 *
	 * @param array $data Product data
	 * @param array $options Generation options
	 * @return string JSON-LD schema markup
	 */
	public static function generate($data, $options = [])
	{
		// Determine the appropriate schema type based on data
		$schema_type = self::determine_schema_type($data);
		
		$schema_data = [
			'name' => self::sanitize_text($data['name'] ?? $data['title'] ?? ''),
			'description' => self::sanitize_text($data['description'] ?? $data['content'] ?? '')
		];

		// Add offers if pricing information is available
		if (!empty($data['price']) || !empty($data['offers'])) {
			$schema_data['offers'] = self::create_offer($data);
		}

		// Add optional fields
		$optional_fields = [
			'image' => 'image',
			'brand' => 'brand',
			'sku' => 'sku',
			'category' => 'category',
			'url' => 'url',
			'url' => 'website'
		];

		$schema_data = self::merge_optional_fields($schema_data, $data, $optional_fields);

		// Add service-specific fields
		if ($schema_type === 'Service') {
			$schema_data = self::add_service_fields($schema_data, $data);
		}

		// Add software-specific fields
		if ($schema_type === 'SoftwareApplication') {
			$schema_data = self::add_software_fields($schema_data, $data);
		}

		return self::create_schema(self::add_context($schema_data, $schema_type));
	}

	/**
	 * Determine the appropriate schema type
	 *
	 * @param array $data Product data
	 * @return string Schema type
	 */
	private static function determine_schema_type($data)
	{
		// Check for explicit type
		if (!empty($data['type'])) {
			$type = ucfirst($data['type']);
			if (in_array($type, ['Product', 'Service', 'SoftwareApplication'])) {
				return $type;
			}
		}

		// Check for service indicators
		if (!empty($data['service_type']) || 
			!empty($data['is_service']) || 
			!empty($data['service_category'])) {
			return 'Service';
		}

		// Check for software indicators
		if (!empty($data['software_type']) || 
			!empty($data['is_software']) || 
			!empty($data['application_category']) ||
			!empty($data['operating_system']) ||
			!empty($data['software_version'])) {
			return 'SoftwareApplication';
		}

		// Check for digital tool indicators
		if (!empty($data['digital_tool']) || 
			!empty($data['tool_type']) ||
			!empty($data['platform']) ||
			!empty($data['api_available'])) {
			return 'SoftwareApplication';
		}

		// Default to Product
		return 'Product';
	}

	/**
	 * Create offer object
	 *
	 * @param array $data Product data
	 * @return array Offer object
	 */
	private static function create_offer($data)
	{
		$offer_data = [
			'@type' => 'Offer'
		];

		// Handle different pricing structures
		if (!empty($data['price'])) {
			$offer_data['price'] = $data['price'];
			$offer_data['priceCurrency'] = $data['currency'] ?? 'USD';
		}

		if (!empty($data['price_range'])) {
			$offer_data['priceSpecification'] = [
				'@type' => 'PriceSpecification',
				'minPrice' => $data['price_range']['min'] ?? '',
				'maxPrice' => $data['price_range']['max'] ?? '',
				'priceCurrency' => $data['currency'] ?? 'USD'
			];
		}

		// Add availability
		if (!empty($data['availability'])) {
			$offer_data['availability'] = $data['availability'];
		}

		// Add URL
		if (!empty($data['url'])) {
			$offer_data['url'] = $data['url'];
		}

		return $offer_data;
	}

	/**
	 * Add service-specific fields
	 *
	 * @param array $schema_data Schema data
	 * @param array $data Product data
	 * @return array Updated schema data
	 */
	private static function add_service_fields($schema_data, $data)
	{
		// Add service type
		if (!empty($data['service_type'])) {
			$schema_data['serviceType'] = self::sanitize_text($data['service_type']);
		}

		// Add service category
		if (!empty($data['service_category'])) {
			$schema_data['serviceCategory'] = self::sanitize_text($data['service_category']);
		}

		// Add area served
		if (!empty($data['area_served'])) {
			$schema_data['areaServed'] = self::sanitize_text($data['area_served']);
		}

		// Add provider
		if (!empty($data['provider'])) {
			$schema_data['provider'] = [
				'@type' => 'Organization',
				'name' => self::sanitize_text($data['provider'])
			];
		}

		return $schema_data;
	}

	/**
	 * Add software-specific fields
	 *
	 * @param array $schema_data Schema data
	 * @param array $data Product data
	 * @return array Updated schema data
	 */
	private static function add_software_fields($schema_data, $data)
	{
		// Add application category
		if (!empty($data['application_category'])) {
			$schema_data['applicationCategory'] = self::sanitize_text($data['application_category']);
		}

		// Add operating system
		if (!empty($data['operating_system'])) {
			$schema_data['operatingSystem'] = self::sanitize_text($data['operating_system']);
		}

		// Add software version
		if (!empty($data['software_version'])) {
			$schema_data['softwareVersion'] = self::sanitize_text($data['software_version']);
		}

		// Add platform
		if (!empty($data['platform'])) {
			$schema_data['platform'] = self::sanitize_text($data['platform']);
		}

		// Add download URL
		if (!empty($data['download_url'])) {
			$schema_data['downloadUrl'] = $data['download_url'];
		}

		// Add install URL
		if (!empty($data['install_url'])) {
			$schema_data['installUrl'] = $data['install_url'];
		}

		// Add API availability
		if (!empty($data['api_available'])) {
			$schema_data['hasAPI'] = (bool) $data['api_available'];
		}

		return $schema_data;
	}
} 