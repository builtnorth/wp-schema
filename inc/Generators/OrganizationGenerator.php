<?php

namespace BuiltNorth\Schema\Generators;

/**
 * Organization Schema Generator
 * 
 * Generates Organization schema from various data structures
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
			'name' => $data['name'] ?? '',
			'url' => $data['url'] ?? get_site_url(),
			'logo' => $data['logo'] ?? ''
		];

		// Add optional fields
		$optional_fields = [
			'description' => 'description',
			'address' => 'address',
			'telephone' => 'telephone',
			'email' => 'email',
			'sameAs' => 'social_media'
		];

		$schema_data = self::merge_optional_fields($schema_data, $data, $optional_fields);

		return self::create_schema(self::add_context($schema_data, 'Organization'));
	}
} 