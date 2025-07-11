<?php

namespace BuiltNorth\Schema\Generators\Generators;

/**
 * WebSite Schema Generator
 * 
 * Generates WebSite schema from various data structures
 * Supports logos, search functionality, and site information
 */
class WebSiteGenerator extends BaseGenerator
{
	/**
	 * Generate WebSite schema from data
	 *
	 * @param array $data WebSite data
	 * @param array $options Generation options
	 * @return string JSON-LD schema markup
	 */
	public static function generate($data, $options = [])
	{
		$schema_data = [
			'name' => self::sanitize_text($data['name'] ?? get_bloginfo('name')),
			'url' => $data['url'] ?? get_site_url(),
		];

		// Handle logo with comprehensive support
		$logo_data = self::process_logo($data, $options);
		if (!empty($logo_data)) {
			$schema_data['logo'] = $logo_data;
		}

		// Add description
		if (!empty($data['description'])) {
			$schema_data['description'] = self::sanitize_text($data['description']);
		} elseif (empty($data['description'])) {
			$schema_data['description'] = self::sanitize_text(get_bloginfo('description'));
		}

		// Add search functionality
		$search_data = self::process_search_functionality($data);
		if (!empty($search_data)) {
			$schema_data['potentialAction'] = $search_data;
		}

		// Add social media links
		$social_data = self::process_social_media($data);
		if (!empty($social_data)) {
			$schema_data['sameAs'] = $social_data;
		}

		// Add optional fields
		$optional_fields = [
			'inLanguage' => 'language',
			'inLanguage' => 'locale',
			'copyrightYear' => 'copyright_year',
			'copyrightYear' => 'copyright',
			'publisher' => 'publisher',
			'author' => 'author',
			'dateCreated' => 'date_created',
			'dateCreated' => 'launch_date',
			'dateModified' => 'date_modified',
			'dateModified' => 'last_updated',
			'keywords' => 'keywords',
			'keywords' => 'meta_keywords',
			'genre' => 'genre',
			'genre' => 'category',
			'audience' => 'audience',
			'audience' => 'target_audience',
			'isAccessibleForFree' => 'is_free',
			'isAccessibleForFree' => 'free_access',
			'hasPart' => 'subsites',
			'hasPart' => 'subdomains',
			'isPartOf' => 'parent_site',
			'isPartOf' => 'parent_website'
		];

		$schema_data = self::merge_optional_fields($schema_data, $data, $optional_fields);

		// Add publisher information
		$publisher_data = self::process_publisher($data);
		if (!empty($publisher_data)) {
			$schema_data['publisher'] = $publisher_data;
		}

		// Add author information
		$author_data = self::process_author($data);
		if (!empty($author_data)) {
			$schema_data['author'] = $author_data;
		}

		// Add breadcrumb navigation
		$breadcrumb_data = self::process_breadcrumbs($data);
		if (!empty($breadcrumb_data)) {
			$schema_data['breadcrumb'] = $breadcrumb_data;
		}

		// Add site navigation
		$navigation_data = self::process_navigation($data);
		if (!empty($navigation_data)) {
			$schema_data['mainEntity'] = $navigation_data;
		}

		return self::create_schema(self::add_context($schema_data, 'WebSite'));
	}

	/**
	 * Process logo data with comprehensive support
	 *
	 * @param array $data Website data
	 * @param array $options Generation options
	 * @return array|string Logo data
	 */
	private static function process_logo($data, $options = [])
	{
		// Handle single logo URL
		if (!empty($data['logo']) && is_string($data['logo'])) {
			return $data['logo'];
		}

		// Handle logo object with multiple formats
		if (!empty($data['logo']) && is_array($data['logo'])) {
			$logo_data = $data['logo'];
			
			// If it's already a structured logo object
			if (isset($logo_data['@type']) && $logo_data['@type'] === 'ImageObject') {
				return $logo_data;
			}

			// Create structured logo object
			$structured_logo = [
				'@type' => 'ImageObject',
				'url' => $logo_data['url'] ?? $logo_data['src'] ?? ''
			];

			// Add optional logo properties
			if (!empty($logo_data['width'])) {
				$structured_logo['width'] = (int) $logo_data['width'];
			}
			if (!empty($logo_data['height'])) {
				$structured_logo['height'] = (int) $logo_data['height'];
			}
			if (!empty($logo_data['alt'])) {
				$structured_logo['caption'] = self::sanitize_text($logo_data['alt']);
			}

			return $structured_logo;
		}

		// Handle multiple logo formats
		if (!empty($data['logos']) && is_array($data['logos'])) {
			$logos = [];
			foreach ($data['logos'] as $logo) {
				if (!empty($logo['url']) || !empty($logo['src'])) {
					$logos[] = self::process_logo(['logo' => $logo], $options);
				}
			}
			return $logos;
		}

		// Try to get logo from WordPress customizer or theme options
		if (empty($data['logo'])) {
			$custom_logo_id = get_theme_mod('custom_logo');
			if ($custom_logo_id) {
				$logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
				if ($logo_url) {
					return $logo_url;
				}
			}
		}

		return '';
	}

	/**
	 * Process search functionality
	 *
	 * @param array $data Website data
	 * @return array Search action
	 */
	private static function process_search_functionality($data)
	{
		// Check if search functionality is available
		if (!empty($data['search_url']) || !empty($data['search_enabled'])) {
			$search_url = $data['search_url'] ?? get_site_url() . '/?s={search_term_string}';
			
			return [
				'@type' => 'SearchAction',
				'target' => [
					'@type' => 'EntryPoint',
					'urlTemplate' => $search_url
				],
				'query-input' => 'required name=search_term_string'
			];
		}

		// Default search action for WordPress
		return [
			'@type' => 'SearchAction',
			'target' => [
				'@type' => 'EntryPoint',
				'urlTemplate' => get_site_url() . '/?s={search_term_string}'
			],
			'query-input' => 'required name=search_term_string'
		];
	}

	/**
	 * Process social media links
	 *
	 * @param array $data Website data
	 * @return array Social media URLs
	 */
	private static function process_social_media($data)
	{
		$social_media = [];

		// Handle array of social media URLs
		if (!empty($data['social_media']) && is_array($data['social_media'])) {
			foreach ($data['social_media'] as $platform => $url) {
				if (!empty($url)) {
					$social_media[] = $url;
				}
			}
		}

		// Handle individual social media fields
		$social_fields = [
			'facebook' => 'facebook_url',
			'twitter' => 'twitter_url',
			'linkedin' => 'linkedin_url',
			'instagram' => 'instagram_url',
			'youtube' => 'youtube_url',
			'github' => 'github_url',
			'pinterest' => 'pinterest_url',
			'tiktok' => 'tiktok_url'
		];

		foreach ($social_fields as $platform => $field) {
			if (!empty($data[$field]) || !empty($data[$platform])) {
				$url = $data[$field] ?? $data[$platform];
				if (!empty($url)) {
					$social_media[] = $url;
				}
			}
		}

		return $social_media;
	}

	/**
	 * Process publisher information
	 *
	 * @param array $data Website data
	 * @return array Publisher data
	 */
	private static function process_publisher($data)
	{
		if (!empty($data['publisher'])) {
			if (is_string($data['publisher'])) {
				return [
					'@type' => 'Organization',
					'name' => self::sanitize_text($data['publisher'])
				];
			}

			if (is_array($data['publisher'])) {
				return self::create_nested_object('Organization', $data['publisher']);
			}
		}

		// Default to site organization
		return [
			'@type' => 'Organization',
			'name' => self::sanitize_text(get_bloginfo('name')),
			'url' => get_site_url()
		];
	}

	/**
	 * Process author information
	 *
	 * @param array $data Website data
	 * @return array Author data
	 */
	private static function process_author($data)
	{
		if (!empty($data['author'])) {
			if (is_string($data['author'])) {
				return [
					'@type' => 'Person',
					'name' => self::sanitize_text($data['author'])
				];
			}

			if (is_array($data['author'])) {
				return self::create_nested_object('Person', $data['author']);
			}
		}

		// Default to site owner
		return [
			'@type' => 'Person',
			'name' => self::sanitize_text(get_bloginfo('name'))
		];
	}

	/**
	 * Process breadcrumb navigation
	 *
	 * @param array $data Website data
	 * @return array Breadcrumb data
	 */
	private static function process_breadcrumbs($data)
	{
		if (!empty($data['breadcrumbs'])) {
			if (is_array($data['breadcrumbs'])) {
				return [
					'@type' => 'BreadcrumbList',
					'itemListElement' => $data['breadcrumbs']
				];
			}
		}

		// Generate breadcrumbs from current page
		if (is_page() || is_single()) {
			$breadcrumbs = [];
			$position = 1;

			// Home page
			$breadcrumbs[] = [
				'@type' => 'ListItem',
				'position' => $position++,
				'name' => 'Home',
				'item' => get_site_url()
			];

			// Current page
			$breadcrumbs[] = [
				'@type' => 'ListItem',
				'position' => $position,
				'name' => get_the_title(),
				'item' => get_permalink()
			];

			return [
				'@type' => 'BreadcrumbList',
				'itemListElement' => $breadcrumbs
			];
		}

		return [];
	}

	/**
	 * Process site navigation
	 *
	 * @param array $data Website data
	 * @return array Navigation data
	 */
	private static function process_navigation($data)
	{
		if (!empty($data['navigation'])) {
			if (is_array($data['navigation'])) {
				return [
					'@type' => 'SiteNavigationElement',
					'name' => 'Main Navigation',
					'hasPart' => $data['navigation']
				];
			}
		}

		// Generate navigation from WordPress menu
		$nav_items = wp_get_nav_menu_items('primary');
		if ($nav_items) {
			$navigation = [];
			foreach ($nav_items as $item) {
				$navigation[] = [
					'@type' => 'WebPage',
					'name' => $item->title,
					'url' => $item->url
				];
			}

			return [
				'@type' => 'SiteNavigationElement',
				'name' => 'Main Navigation',
				'hasPart' => $navigation
			];
		}

		return [];
	}
} 