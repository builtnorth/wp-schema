<?php

namespace BuiltNorth\Schema\Generators;

/**
 * FAQ Schema Generator
 * 
 * Generates FAQ schema from various data structures
 */
class FaqGenerator extends BaseGenerator
{
	/**
	 * Generate FAQ schema from data
	 *
	 * @param array $data FAQ data
	 * @param array $options Generation options
	 * @return string JSON-LD schema markup
	 */
	public static function generate($data, $options = [])
	{
		$faq_items = self::extract_faq_items($data);
		
		if (empty($faq_items)) {
			return '';
		}
		
		$schema_data = [
			'mainEntity' => $faq_items
		];
		
		return self::create_schema(self::add_context($schema_data, 'FAQPage'));
	}

	/**
	 * Extract FAQ items from various data structures
	 *
	 * @param array $data FAQ data
	 * @return array Array of FAQ items
	 */
	private static function extract_faq_items($data)
	{
		$faq_items = [];
		
		// Handle different data structures
		if (isset($data['items']) && is_array($data['items'])) {
			$faq_items = $data['items'];
		} elseif (isset($data['questions']) && isset($data['answers'])) {
			// Convert parallel arrays to FAQ items
			$questions = is_array($data['questions']) ? $data['questions'] : [$data['questions']];
			$answers = is_array($data['answers']) ? $data['answers'] : [$data['answers']];
			
			foreach ($questions as $index => $question) {
				if (isset($answers[$index])) {
					$faq_items[] = self::create_faq_item($question, $answers[$index]);
				}
			}
		} elseif (isset($data['faq']) && is_array($data['faq'])) {
			$faq_items = $data['faq'];
		} elseif (isset($data['questions']) && is_array($data['questions'])) {
			// Handle array of question objects
			foreach ($data['questions'] as $question_data) {
				if (isset($question_data['question']) && isset($question_data['answer'])) {
					$faq_items[] = self::create_faq_item($question_data['question'], $question_data['answer']);
				}
			}
		}
		
		return $faq_items;
	}

	/**
	 * Create a single FAQ item
	 *
	 * @param string $question Question text
	 * @param string $answer Answer text
	 * @return array FAQ item structure
	 */
	private static function create_faq_item($question, $answer)
	{
		return [
			'@type' => 'Question',
			'name' => self::sanitize_text($question),
			'acceptedAnswer' => [
				'@type' => 'Answer',
				'text' => self::sanitize_text($answer)
			]
		];
	}
} 