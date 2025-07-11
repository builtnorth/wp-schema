<?php

namespace BuiltNorth\Schema\Generators;

/**
 * Navigation Schema Generator
 * 
 * Generates navigation schema from WordPress navigation blocks
 * Only processes navigation blocks with navigationSchema: true attribute
 */
class NavigationGenerator extends BaseGenerator
{
	/**
	 * Generate navigation schema from data
	 *
	 * @param array $data Navigation data
	 * @param array $options Generation options
	 * @return array JSON-LD schema data
	 */
	public static function generate($data, $options = [])
	{
		$schema_data = [
			'@type' => 'SiteNavigationElement',
			'name' => self::sanitize_text($data['name'] ?? 'Main Navigation'),
		];

		// Get navigation items from marked navigation blocks
		$navigation_items = self::get_navigation_items();
		if (!empty($navigation_items)) {
			$schema_data['hasPart'] = $navigation_items;
		}

		// Add optional fields
		$optional_fields = [
			'description' => 'description',
			'url' => 'url',
			'url' => 'canonical_url',
		];

		$schema_data = self::merge_optional_fields($schema_data, $data, $optional_fields);

		return self::add_context($schema_data, 'SiteNavigationElement');
	}

	/**
	 * Get navigation items from marked navigation blocks
	 *
	 * @return array Navigation items
	 */
	private static function get_navigation_items()
	{
		$navigation_items = [];
		
		// Get header template content
		$header_file = get_template_directory() . '/parts/header.html';
		if (file_exists($header_file)) {
			$header_content = file_get_contents($header_file);
			$navigation_items = self::extract_navigation_from_blocks($header_content);
		}
		
		return $navigation_items;
	}

	/**
	 * Extract navigation from block content
	 *
	 * @param string $content Block content
	 * @return array Navigation items
	 */
	private static function extract_navigation_from_blocks($content)
	{
		$navigation_items = [];
		
		// Parse blocks from content
		$blocks = parse_blocks($content);
		
		// Recursively search for navigation blocks
		$navigation_items = self::find_navigation_blocks_recursive($blocks);
		
		return $navigation_items;
	}

	/**
	 * Recursively find navigation blocks in all nested blocks
	 *
	 * @param array $blocks Array of blocks
	 * @return array Navigation items
	 */
	private static function find_navigation_blocks_recursive($blocks)
	{
		$navigation_items = [];
		
		foreach ($blocks as $block) {
			if (isset($block['blockName']) && ($block['blockName'] === 'core/navigation' || $block['blockName'] === 'wp:navigation')) {
				// Try to get ref from attrs
				$ref = null;
				if (isset($block['attrs']['ref']) && is_numeric($block['attrs']['ref'])) {
					$ref = (int)$block['attrs']['ref'];
				} elseif (isset($block['innerHTML'])) {
					// Fallback: regex the ref from the raw block HTML
					if (preg_match('/"ref":\s*(\d+)/', $block['innerHTML'], $matches)) {
						$ref = (int)$matches[1];
					}
				}
				if ($ref) {
					$nav_post = get_post($ref);
					if ($nav_post && $nav_post->post_type === 'wp_navigation') {
						$nav_blocks = parse_blocks($nav_post->post_content);
						foreach ($nav_blocks as $nav_block) {
							if (isset($nav_block['blockName'])) {
								if ($nav_block['blockName'] === 'core/navigation-link') {
									$item = self::extract_navigation_link_item($nav_block);
									if ($item) {
										$navigation_items[] = $item;
									}
								} elseif ($nav_block['blockName'] === 'core/navigation-submenu') {
									// Extract parent submenu link if present
									if (isset($nav_block['attrs']['label']) && isset($nav_block['attrs']['url'])) {
										$submenu_item = [
											'@type' => 'WebPage',
											'name' => self::sanitize_text($nav_block['attrs']['label']),
											'url' => esc_url($nav_block['attrs']['url'])
										];
										$navigation_items[] = $submenu_item;
									}
									// Recursively extract submenu children
									$submenu_children = self::extract_navigation_block_items($nav_block);
									if (!empty($submenu_children)) {
										$navigation_items = array_merge($navigation_items, $submenu_children);
									}
								}
							}
						}
					}
				}
			}
			
			// Recursively check inner blocks
			if (isset($block['innerBlocks']) && is_array($block['innerBlocks'])) {
				$inner_items = self::find_navigation_blocks_recursive($block['innerBlocks']);
				$navigation_items = array_merge($navigation_items, $inner_items);
			}
		}
		
		return $navigation_items;
	}

	/**
	 * Extract items from navigation block
	 *
	 * @param array $nav_block Navigation block
	 * @return array Navigation items
	 */
	private static function extract_navigation_block_items($nav_block)
	{
		$items = [];
		
		if (isset($nav_block['innerBlocks']) && is_array($nav_block['innerBlocks'])) {
			foreach ($nav_block['innerBlocks'] as $inner_block) {
				if (isset($inner_block['blockName'])) {
					if ($inner_block['blockName'] === 'core/navigation-link') {
						$item = self::extract_navigation_link_item($inner_block);
						if ($item) {
							$items[] = $item;
						}
					} elseif ($inner_block['blockName'] === 'core/navigation-submenu') {
						// Extract parent submenu link if present
						if (isset($inner_block['attrs']['label']) && isset($inner_block['attrs']['url'])) {
							$submenu_item = [
								'@type' => 'WebPage',
								'name' => self::sanitize_text($inner_block['attrs']['label']),
								'url' => esc_url($inner_block['attrs']['url'])
							];
							$items[] = $submenu_item;
						}
						// Recursively extract submenu children
						$submenu_children = self::extract_navigation_block_items($inner_block);
						if (!empty($submenu_children)) {
							$items = array_merge($items, $submenu_children);
						}
					} else {
						// Recursively check any other inner blocks
						$child_items = self::extract_navigation_block_items($inner_block);
						if (!empty($child_items)) {
							$items = array_merge($items, $child_items);
						}
					}
				}
			}
		}
		
		return $items;
	}

	/**
	 * Extract navigation link item
	 *
	 * @param array $link_block Navigation link block
	 * @return array|null Navigation item
	 */
	private static function extract_navigation_link_item($link_block)
	{
		if (!isset($link_block['attrs']['label']) || !isset($link_block['attrs']['url'])) {
			return null;
		}
		
		return [
			'@type' => 'WebPage',
			'name' => self::sanitize_text($link_block['attrs']['label']),
			'url' => esc_url($link_block['attrs']['url'])
		];
	}
} 