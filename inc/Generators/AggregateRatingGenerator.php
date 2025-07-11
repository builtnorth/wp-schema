<?php

namespace BuiltNorth\Schema\Generators;

/**
 * Aggregate Rating Schema Generator
 * 
 * Generates AggregateRating schema from review data
 */
class AggregateRatingGenerator extends BaseGenerator
{
	/**
	 * Generate AggregateRating schema from data
	 *
	 * @param array $data Rating data
	 * @param array $options Generation options
	 * @return string JSON-LD schema markup
	 */
	public static function generate($data, $options = [])
	{
		if (!self::validate_data($data, ['average', 'count'])) {
			return '';
		}

		$schema_data = [
			'ratingValue' => (string) $data['average'],
			'reviewCount' => (string) $data['count'],
			'bestRating' => '5',
			'worstRating' => '1'
		];

		// Add optional fields
		if (!empty($options['itemReviewed'])) {
			$schema_data['itemReviewed'] = $options['itemReviewed'];
		}

		if (!empty($options['url'])) {
			$schema_data['url'] = $options['url'];
		}

		return self::create_schema(self::add_context($schema_data, 'AggregateRating'));
	}
} 