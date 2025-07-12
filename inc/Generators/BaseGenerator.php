<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Generators;

/**
 * Base Generator Class
 * 
 * Abstract base class for all schema generators.
 * Provides common utilities for generating Schema.org compliant JSON-LD.
 * 
 * @since 2.0.0
 */
abstract class BaseGenerator
{
	/**
	 * Generate schema from data
	 *
	 * @param array<string, mixed> $data Extracted data
	 * @param array<string, mixed> $options Generation options
	 * @return array<string, mixed> JSON-LD schema data
	 */
	abstract public static function generate($data, $options = []);

	/**
	 * Validate required data fields
	 *
	 * @param array<string, mixed> $data Data to validate
	 * @param array<string> $required_fields Required field names
	 * @return bool True if valid, false otherwise
	 */
	protected static function validate_data(array $data, array $required_fields = []): bool
	{
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
	protected static function sanitize_text(string $text): string
	{
		return wp_strip_all_tags(trim($text));
	}

	/**
	 * Create JSON-LD schema data (returns array)
	 *
	 * @param array<string, mixed> $schema_data Schema data array
	 * @return array<string, mixed> JSON-LD schema data
	 */
	protected static function create_schema(array $schema_data): array
	{
		return $schema_data;
	}

	/**
	 * Add context to schema
	 *
	 * @param array<string, mixed> $schema_data Schema data
	 * @param string $type Schema type
	 * @return array<string, mixed> Schema with context
	 */
	protected static function add_context(array $schema_data, string $type): array
	{
		return array_merge([
			'@context' => 'https://schema.org',
			'@type' => ucfirst($type)
		], $schema_data);
	}

	/**
	 * Merge optional fields into schema
	 *
	 * @param array<string, mixed> $schema Schema array
	 * @param array<string, mixed> $data Data array
	 * @param array<string, string> $optional_fields Optional field mappings
	 * @return array<string, mixed> Schema with optional fields
	 */
	protected static function merge_optional_fields(array $schema, array $data, array $optional_fields = []): array
	{
		foreach ($optional_fields as $schema_field => $data_field) {
			if (isset($data[$data_field]) && !empty($data[$data_field])) {
				$value = $data[$data_field];
				$schema[$schema_field] = is_string($value) ? self::sanitize_text($value) : $value;
			}
		}
		
		return $schema;
	}

	/**
	 * Create nested schema object
	 *
	 * @param string $type Schema type
	 * @param array<string, mixed> $data Data for nested object
	 * @return array<string, mixed> Nested schema object
	 */
	protected static function create_nested_object(string $type, array $data): array
	{
		$object = ['@type' => ucfirst($type)];
		
		foreach ($data as $key => $value) {
			if (!empty($value)) {
				$object[$key] = is_string($value) ? self::sanitize_text($value) : $value;
			}
		}
		
		return $object;
	}
} 