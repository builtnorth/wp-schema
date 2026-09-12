<?php

declare(strict_types=1);

/**
 * Public-facing static API for external consumers of wp-schema.
 *
 * Internal (same-monorepo) callers should use the real classes
 * (BuiltNorth\WPSchema\App, BuiltNorth\WPSchema\Services\SchemaTypeRegistry)
 * directly rather than this facade.
 *
 * @package BuiltNorth\WPSchema
 */

namespace BuiltNorth\WPSchema;

use BuiltNorth\WPSchema\Services\SchemaTypeRegistry;

defined('ABSPATH') || defined('WP_CLI') || exit;

class Schema
{
	/**
	 * Register a schema provider — self-defers to the right moment, no
	 * add_action() needed at the call site.
	 *
	 * @param string $name       Provider name.
	 * @param string $class_name Provider class name.
	 */
	public static function registerProvider(string $name, string $class_name): void
	{
		add_action('wp_schema_framework_register_providers', static function () use ($name, $class_name): void {
			App::register_provider($name, $class_name);
		});
	}

	/**
	 * Initialize wp-schema now.
	 */
	public static function initialize(): App
	{
		return App::initialize();
	}

	/**
	 * Defer initialize() to WordPress's `init` hook, once core is fully loaded.
	 *
	 * Call this once from your plugin/theme instead of hand-rolling
	 * add_action('init', ...) yourself.
	 */
	public static function boot(): void
	{
		add_action('init', [self::class, 'initialize']);
	}

	/**
	 * @return list<array{label: string, value: string, category?: string, subcategory?: string}>
	 */
	public static function getAvailableTypes(): array
	{
		return (new SchemaTypeRegistry())->get_available_types();
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function getPostTypeMappings(): array
	{
		return (new SchemaTypeRegistry())->get_post_type_mappings();
	}

	/**
	 * @return list<array{label: string, value: string}>
	 */
	public static function getOrganizationTypes(): array
	{
		return (new SchemaTypeRegistry())->get_organization_types();
	}

	public static function getSchemaTypeForPostType(string $post_type): string
	{
		return (new SchemaTypeRegistry())->get_schema_type_for_post_type($post_type);
	}

	public static function isValidType(string $type): bool
	{
		return (new SchemaTypeRegistry())->is_valid_type($type);
	}

	/**
	 * @return array<string, array<string, list<array{label: string, value: string}>>>
	 */
	public static function getCategorizedTypes(): array
	{
		return (new SchemaTypeRegistry())->get_categorized_types();
	}

	/**
	 * @return array<string, list<array{label: string, value: string}>>
	 */
	public static function getCategorizedOrganizationTypes(): array
	{
		return (new SchemaTypeRegistry())->get_categorized_organization_types();
	}

	/**
	 * @return list<array{label: string, value: string}>
	 */
	public static function getContentTypes(): array
	{
		return (new SchemaTypeRegistry())->get_content_types();
	}
}
