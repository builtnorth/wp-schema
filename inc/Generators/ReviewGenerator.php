<?php

namespace BuiltNorth\Schema\Generators;

/**
 * Review Schema Generator
 * 
 * Generates individual Review schema from review data
 */
class ReviewGenerator extends BaseGenerator
{
	/**
	 * Generate Review schema from data
	 *
	 * @param array $data Review data
	 * @param array $options Generation options
	 * @return string JSON-LD schema markup
	 */
	public static function generate($data, $options = [])
	{
		// Handle array of reviews
		if (isset($data[0])) {
			$review_schemas = [];
			foreach ($data as $review) {
				$schema = self::generate_single_review($review, $options);
				if (!empty($schema)) {
					$review_schemas[] = json_decode($schema, true);
				}
			}
			
			if (!empty($review_schemas)) {
				return self::create_schema($review_schemas);
			}
			return '';
		}
		
		// Handle single review
		return self::generate_single_review($data, $options);
	}

	/**
	 * Generate single review schema
	 *
	 * @param array $review Review data
	 * @param array $options Generation options
	 * @return string JSON-LD schema markup
	 */
	private static function generate_single_review($review, $options = [])
	{
		if (!self::validate_data($review, ['review_content', 'rating'])) {
			return '';
		}

		$schema_data = [
			'reviewRating' => [
				'@type' => 'Rating',
				'ratingValue' => (string) $review['rating'],
				'bestRating' => '5',
				'worstRating' => '1'
			],
			'reviewBody' => self::sanitize_text($review['review_content'])
		];

		// Add author
		if (!empty($review['username'])) {
			$schema_data['author'] = [
				'@type' => 'Person',
				'name' => self::sanitize_text($review['username'])
			];
		}

		// Add date
		if (!empty($review['date'])) {
			$schema_data['datePublished'] = date('Y-m-d', strtotime($review['date']));
		}

		// Add optional fields
		if (!empty($options['itemReviewed'])) {
			$schema_data['itemReviewed'] = $options['itemReviewed'];
		}

		if (!empty($options['url'])) {
			$schema_data['url'] = $options['url'];
		}

		return self::create_schema(self::add_context($schema_data, 'Review'));
	}
} 