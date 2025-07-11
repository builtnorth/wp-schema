<?php

namespace BuiltNorth\Schema\Generators;

/**
 * Person Schema Generator
 * 
 * Generates Person schema from various data structures
 */
class PersonGenerator extends BaseGenerator
{
	/**
	 * Generate Person schema from data
	 *
	 * @param array $data Person data
	 * @param array $options Generation options
	 * @return string JSON-LD schema markup
	 */
	public static function generate($data, $options = [])
	{
		$schema_data = [
			'name' => $data['name'] ?? '',
			'jobTitle' => $data['job_title'] ?? '',
			'worksFor' => $data['works_for'] ?? ''
		];

		// Add optional fields
		$optional_fields = [
			'email' => 'email',
			'telephone' => 'telephone',
			'url' => 'url',
			'image' => 'image',
			'description' => 'description',
			'sameAs' => 'social_media'
		];

		$schema_data = self::merge_optional_fields($schema_data, $data, $optional_fields);

		return self::create_schema(self::add_context($schema_data, 'Person'));
	}
} 