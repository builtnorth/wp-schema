<?php

/**
 * WP Schema helper functions
 *
 * @package BuiltNorth\WPSchema
 */

use BuiltNorth\WPSchema\App;
use BuiltNorth\WPSchema\Services\SchemaTypeRegistry;

defined('ABSPATH') || defined('WP_CLI') || exit;

if (! function_exists('wp_schema_is_active')) {
	/**
	 * Whether WP Schema is available.
	 *
	 * @return bool
	 */
	function wp_schema_is_active(): bool {
		return class_exists(App::class);
	}
}

if (! function_exists('wp_schema_register_provider')) {
	/**
	 * Register a schema provider.
	 *
	 * @param string $name       Provider name.
	 * @param string $class_name Provider class name.
	 * @return bool
	 */
	function wp_schema_register_provider(string $name, string $class_name): bool {
		if (! wp_schema_is_active()) {
			return false;
		}

		return App::register_provider($name, $class_name);
	}
}

if (! function_exists('wp_schema_initialize')) {
	/**
	 * Initialize WP Schema.
	 *
	 * @return void
	 */
	function wp_schema_initialize(): void {
		if (! wp_schema_is_active()) {
			return;
		}

		App::initialize();
	}
}

if (! function_exists('wp_schema_get_registry')) {
	/**
	 * Shared per-request SchemaTypeRegistry instance.
	 *
	 * @return SchemaTypeRegistry|null
	 */
	function wp_schema_get_registry(): ?SchemaTypeRegistry {
		static $registry = null;

		if (! class_exists(SchemaTypeRegistry::class)) {
			return null;
		}

		$registry ??= new SchemaTypeRegistry();

		return $registry;
	}
}

if (! function_exists('wp_schema_get_available_types')) {
	/**
	 * @return list<array{label: string, value: string, category?: string, subcategory?: string}>
	 */
	function wp_schema_get_available_types(): array {
		return wp_schema_get_registry()?->get_available_types() ?? [];
	}
}

if (! function_exists('wp_schema_get_post_type_mappings')) {
	/**
	 * @return array<string, mixed>
	 */
	function wp_schema_get_post_type_mappings(): array {
		return wp_schema_get_registry()?->get_post_type_mappings() ?? [];
	}
}

if (! function_exists('wp_schema_get_organization_types')) {
	/**
	 * @return list<array{label: string, value: string}>
	 */
	function wp_schema_get_organization_types(): array {
		return wp_schema_get_registry()?->get_organization_types() ?? [];
	}
}

if (! function_exists('wp_schema_get_schema_type_for_post_type')) {
	/**
	 * @param string $post_type Post type slug.
	 * @return string
	 */
	function wp_schema_get_schema_type_for_post_type(string $post_type): string {
		return wp_schema_get_registry()?->get_schema_type_for_post_type($post_type) ?? '';
	}
}

if (! function_exists('wp_schema_is_valid_type')) {
	/**
	 * @param string $type Schema.org type name.
	 * @return bool
	 */
	function wp_schema_is_valid_type(string $type): bool {
		return wp_schema_get_registry()?->is_valid_type($type) ?? false;
	}
}
