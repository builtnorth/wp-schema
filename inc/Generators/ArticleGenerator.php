<?php

namespace BuiltNorth\Schema\Generators;

/**
 * Article Schema Generator
 * 
 * Generates Article schema from various data structures
 */
class ArticleGenerator extends BaseGenerator
{
	/**
	 * Generate Article schema from data
	 *
	 * @param array $data Article data
	 * @param array $options Generation options
	 * @return string JSON-LD schema markup
	 */
	public static function generate($data, $options = [])
	{
		$schema_data = [
			'headline' => $data['title'] ?? $data['headline'] ?? '',
			'author' => self::create_nested_object('Person', [
				'name' => $data['author'] ?? ''
			]),
			'datePublished' => $data['date'] ?? $data['datePublished'] ?? '',
			'articleBody' => $data['content'] ?? $data['articleBody'] ?? ''
		];

		// Add optional fields
		$optional_fields = [
			'url' => 'url',
			'image' => 'image',
			'description' => 'description',
			'keywords' => 'keywords'
		];

		$schema_data = self::merge_optional_fields($schema_data, $data, $optional_fields);

		return self::create_schema(self::add_context($schema_data, 'Article'));
	}
} 