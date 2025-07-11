<?php

namespace BuiltNorth\Schema\Generators;

/**
 * Base Generator Class
 * 
 * Abstract base class for all schema generators
 */
abstract class BaseGenerator
{
	/**
	 * Generate schema from data
	 *
	 * @param array $data Extracted data
	 * @param array $options Generation options
	 * @return string JSON-LD schema markup
	 */
	abstract public static function generate($data, $options = []);

	/**
	 * Validate required data fields
	 *
	 * @param array $data Data to validate
	 * @param array $required_fields Required field names
	 * @return bool True if valid, false otherwise
	 */
	protected static function validate_data($data, $required_fields = [])
	{
		if (!is_array($data)) {
			return false;
		}
		
		foreach ($required_fields as $field) {
			if (!isset($data[$field]) || empty($data[$field])) {
				return false;
			}
		}
		
		return true;
	}

	/**
	 * Sanitize text content
	 *
	 * @param string $text Text to sanitize
	 * @return string Sanitized text
	 */
	protected static function sanitize_text($text)
	{
		return wp_strip_all_tags(trim($text));
	}

	/**
	 * Create JSON-LD schema
	 *
	 * @param array $schema_data Schema data array
	 * @return string JSON-LD markup
	 */
	protected static function create_schema($schema_data)
	{
		return wp_json_encode($schema_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	/**
	 * Add context to schema
	 *
	 * @param array $schema_data Schema data
	 * @param string $type Schema type
	 * @return array Schema with context
	 */
	protected static function add_context($schema_data, $type)
	{
		return array_merge([
			'@context' => 'https://schema.org',
			'@type' => ucfirst($type)
		], $schema_data);
	}

	/**
	 * Merge optional fields into schema
	 *
	 * @param array $schema Schema array
	 * @param array $data Data array
	 * @param array $optional_fields Optional field mappings
	 * @return array Schema with optional fields
	 */
	protected static function merge_optional_fields($schema, $data, $optional_fields = [])
	{
		foreach ($optional_fields as $schema_field => $data_field) {
			if (isset($data[$data_field]) && !empty($data[$data_field])) {
				$schema[$schema_field] = self::sanitize_text($data[$data_field]);
			}
		}
		
		return $schema;
	}

	/**
	 * Create nested schema object
	 *
	 * @param string $type Schema type
	 * @param array $data Data for nested object
	 * @return array Nested schema object
	 */
	protected static function create_nested_object($type, $data)
	{
		$object = ['@type' => ucfirst($type)];
		
		foreach ($data as $key => $value) {
			if (!empty($value)) {
				$object[$key] = self::sanitize_text($value);
			}
		}
		
		return $object;
	}
} 