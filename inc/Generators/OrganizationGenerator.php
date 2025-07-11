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
	 * @return array JSON-LD schema data
	 */
	public static function generate($data, $options = [])
	{
		$schema_data = [
			'name' => $data['name'] ?? '',
			'url' => $data['url'] ?? get_site_url(),
		];

		// Handle logo with WordPress site logo support
		$logo_data = self::process_logo($data, $options);
		if (!empty($logo_data)) {
			$schema_data['logo'] = $logo_data;
		}

		// Add optional fields
		$optional_fields = [
			'description' => 'description',
			'address' => 'address',
			'telephone' => 'telephone',
			'email' => 'email',
			'sameAs' => 'social_media'
		];

		$schema_data = self::merge_optional_fields($schema_data, $data, $optional_fields);

		return self::add_context($schema_data, 'Organization');
	}

	/**
	 * Process logo data with WordPress site logo support
	 *
	 * @param array $data Organization data
	 * @param array $options Generation options
	 * @return array|string Logo data
	 */
	private static function process_logo($data, $options = [])
	{
		// Handle explicit logo URL
		if (!empty($data['logo']) && is_string($data['logo'])) {
			return self::create_logo_object($data['logo']);
		}

		// Handle logo object with multiple formats
		if (!empty($data['logo']) && is_array($data['logo'])) {
			$logo_data = $data['logo'];
			
			// If it's already a structured logo object
			if (isset($logo_data['@type']) && $logo_data['@type'] === 'ImageObject') {
				return $logo_data;
			}

			// Create structured logo object
			return self::create_logo_object(
				$logo_data['url'] ?? $logo_data['src'] ?? '',
				$logo_data['width'] ?? null,
				$logo_data['height'] ?? null
			);
		}

		// Fallback to WordPress site logo
		$site_logo = self::get_wordpress_site_logo();
		if (!empty($site_logo)) {
			return self::create_logo_object($site_logo);
		}

		return null;
	}

	/**
	 * Get WordPress site logo URL
	 *
	 * @return string|null Logo URL or null
	 */
	private static function get_wordpress_site_logo()
	{
		// Check if we're in WordPress context
		if (!function_exists('get_theme_mod')) {
			return null;
		}

		// Get custom logo from theme mods
		$logo_id = get_theme_mod('custom_logo');
		if ($logo_id) {
			$logo_url = wp_get_attachment_image_url($logo_id, 'full');
			if ($logo_url) {
				return $logo_url;
			}
		}

		// Fallback to site icon
		$site_icon_id = get_option('site_icon');
		if ($site_icon_id) {
			$site_icon_url = wp_get_attachment_image_url($site_icon_id, 'full');
			if ($site_icon_url) {
				return $site_icon_url;
			}
		}

		return null;
	}

	/**
	 * Create a structured logo object
	 *
	 * @param string $url Logo URL
	 * @param int|null $width Logo width
	 * @param int|null $height Logo height
	 * @return array Logo object
	 */
	private static function create_logo_object($url, $width = null, $height = null)
	{
		$logo_object = [
			'@type' => 'ImageObject',
			'url' => $url
		];

		// Add dimensions if available
		if ($width) {
			$logo_object['width'] = (int) $width;
		}
		if ($height) {
			$logo_object['height'] = (int) $height;
		}

		return $logo_object;
	}
} 