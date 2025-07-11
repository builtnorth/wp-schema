<?php

namespace BuiltNorth\Schema\Detectors;

/**
 * Content Type Detector
 * 
 * Automatically detects the type of content provided to the SchemaGenerator
 */
class ContentTypeDetector
{
	/**
	 * Detect the content type automatically
	 *
	 * @param mixed $content Content to analyze
	 * @return string Content type (html, json, array, post_meta, mixed)
	 */
	public static function detect($content)
	{
		if (is_array($content)) {
			return 'array';
		}
		
		if (is_numeric($content)) {
			return 'post_meta';
		}
		
		if (is_string($content)) {
			// Try to decode as JSON
			$decoded = json_decode($content, true);
			if (json_last_error() === JSON_ERROR_NONE) {
				return 'json';
			}
			
			// Check if it looks like HTML
			if (strpos($content, '<') !== false && strpos($content, '>') !== false) {
				return 'html';
			}
		}
		
		return 'html'; // Default fallback
	}

	/**
	 * Check if content is HTML
	 *
	 * @param mixed $content Content to check
	 * @return bool
	 */
	public static function is_html($content)
	{
		return is_string($content) && 
			   strpos($content, '<') !== false && 
			   strpos($content, '>') !== false;
	}

	/**
	 * Check if content is JSON
	 *
	 * @param mixed $content Content to check
	 * @return bool
	 */
	public static function is_json($content)
	{
		if (!is_string($content)) {
			return false;
		}
		
		$decoded = json_decode($content, true);
		return json_last_error() === JSON_ERROR_NONE;
	}

	/**
	 * Check if content is an array
	 *
	 * @param mixed $content Content to check
	 * @return bool
	 */
	public static function is_array($content)
	{
		return is_array($content);
	}

	/**
	 * Check if content is a post ID
	 *
	 * @param mixed $content Content to check
	 * @return bool
	 */
	public static function is_post_id($content)
	{
		return is_numeric($content) && $content > 0;
	}
} 