<?php

namespace BuiltNorth\Utility\Utilities\SchemaGenerator\Detectors;

/**
 * Pattern Detector
 * 
 * Automatically detects patterns in content for schema generation
 */
class PatternDetector
{
	/**
	 * Detect FAQ patterns in HTML content
	 *
	 * @param string $content HTML content to analyze
	 * @return array Detected patterns
	 */
	public static function detect_faq_patterns($content)
	{
		$patterns = [];
		
		// Common FAQ class patterns
		if (strpos($content, 'faq') !== false || strpos($content, 'accordion') !== false) {
			$patterns[] = 'faq';
		}
		
		// Question/answer patterns
		if (preg_match('/<h[1-6][^>]*>.*\?.*<\/h[1-6]>/i', $content)) {
			$patterns[] = 'question_headers';
		}
		
		// List patterns
		if (strpos($content, '<dt>') !== false && strpos($content, '<dd>') !== false) {
			$patterns[] = 'definition_list';
		}
		
		// Accordion patterns
		if (strpos($content, 'accordion-item') !== false || strpos($content, 'collapse') !== false) {
			$patterns[] = 'accordion';
		}
		
		return $patterns;
	}

	/**
	 * Detect article patterns in HTML content
	 *
	 * @param string $content HTML content to analyze
	 * @return array Detected patterns
	 */
	public static function detect_article_patterns($content)
	{
		$patterns = [];
		
		// Article element patterns
		if (strpos($content, '<article') !== false) {
			$patterns[] = 'article_element';
		}
		
		// Main content patterns
		if (strpos($content, '<main') !== false || strpos($content, 'main-content') !== false) {
			$patterns[] = 'main_content';
		}
		
		// WordPress specific patterns
		if (strpos($content, 'entry-content') !== false || strpos($content, 'post-content') !== false) {
			$patterns[] = 'wordpress_content';
		}
		
		// Title patterns
		if (preg_match('/<h1[^>]*>.*<\/h1>/i', $content)) {
			$patterns[] = 'h1_title';
		}
		
		return $patterns;
	}

	/**
	 * Detect product patterns in HTML content
	 *
	 * @param string $content HTML content to analyze
	 * @return array Detected patterns
	 */
	public static function detect_product_patterns($content)
	{
		$patterns = [];
		
		// WooCommerce patterns
		if (strpos($content, 'woocommerce') !== false || strpos($content, 'product') !== false) {
			$patterns[] = 'woocommerce';
		}
		
		// Price patterns
		if (preg_match('/\$[\d,]+\.?\d*/', $content)) {
			$patterns[] = 'price';
		}
		
		// Product title patterns
		if (strpos($content, 'product-title') !== false || strpos($content, 'product_name') !== false) {
			$patterns[] = 'product_title';
		}
		
		// Add to cart patterns
		if (strpos($content, 'add-to-cart') !== false || strpos($content, 'buy-now') !== false) {
			$patterns[] = 'purchase_action';
		}
		
		return $patterns;
	}

	/**
	 * Detect organization patterns in HTML content
	 *
	 * @param string $content HTML content to analyze
	 * @return array Detected patterns
	 */
	public static function detect_organization_patterns($content)
	{
		$patterns = [];
		
		// Company name patterns
		if (strpos($content, 'company') !== false || strpos($content, 'organization') !== false) {
			$patterns[] = 'company_info';
		}
		
		// Contact patterns
		if (strpos($content, 'contact') !== false || strpos($content, 'address') !== false) {
			$patterns[] = 'contact_info';
		}
		
		// Logo patterns
		if (strpos($content, 'logo') !== false || strpos($content, 'brand') !== false) {
			$patterns[] = 'logo';
		}
		
		return $patterns;
	}

	/**
	 * Detect person patterns in HTML content
	 *
	 * @param string $content HTML content to analyze
	 * @return array Detected patterns
	 */
	public static function detect_person_patterns($content)
	{
		$patterns = [];
		
		// Team member patterns
		if (strpos($content, 'team') !== false || strpos($content, 'member') !== false) {
			$patterns[] = 'team_member';
		}
		
		// Author patterns
		if (strpos($content, 'author') !== false || strpos($content, 'byline') !== false) {
			$patterns[] = 'author';
		}
		
		// Job title patterns
		if (strpos($content, 'job') !== false || strpos($content, 'position') !== false) {
			$patterns[] = 'job_title';
		}
		
		return $patterns;
	}

	/**
	 * Detect local business patterns in HTML content
	 *
	 * @param string $content HTML content to analyze
	 * @return array Detected patterns
	 */
	public static function detect_local_business_patterns($content)
	{
		$patterns = [];
		
		// Business name patterns
		if (strpos($content, 'business') !== false || strpos($content, 'store') !== false || strpos($content, 'shop') !== false) {
			$patterns[] = 'business_info';
		}
		
		// Contact patterns
		if (strpos($content, 'contact') !== false || strpos($content, 'address') !== false || strpos($content, 'phone') !== false) {
			$patterns[] = 'contact_info';
		}
		
		// Location patterns
		if (strpos($content, 'location') !== false || strpos($content, 'map') !== false || strpos($content, 'coordinates') !== false) {
			$patterns[] = 'location_info';
		}
		
		// Hours patterns
		if (strpos($content, 'hours') !== false || strpos($content, 'opening') !== false || strpos($content, 'schedule') !== false) {
			$patterns[] = 'business_hours';
		}
		
		// Logo patterns
		if (strpos($content, 'logo') !== false || strpos($content, 'brand') !== false) {
			$patterns[] = 'logo';
		}
		
		// Restaurant patterns
		if (strpos($content, 'restaurant') !== false || strpos($content, 'cafe') !== false || strpos($content, 'menu') !== false) {
			$patterns[] = 'restaurant_info';
		}
		
		// Retail patterns
		if (strpos($content, 'retail') !== false || strpos($content, 'store') !== false || strpos($content, 'shop') !== false) {
			$patterns[] = 'retail_info';
		}
		
		return $patterns;
	}

	/**
	 * Detect website patterns in HTML content
	 *
	 * @param string $content HTML content to analyze
	 * @return array Detected patterns
	 */
	public static function detect_website_patterns($content)
	{
		$patterns = [];
		
		// Website name patterns
		if (strpos($content, 'site') !== false || strpos($content, 'website') !== false) {
			$patterns[] = 'website_info';
		}
		
		// Search patterns
		if (strpos($content, 'search') !== false || strpos($content, 'query') !== false) {
			$patterns[] = 'search_functionality';
		}
		
		// Navigation patterns
		if (strpos($content, 'nav') !== false || strpos($content, 'menu') !== false || strpos($content, 'breadcrumb') !== false) {
			$patterns[] = 'navigation';
		}
		
		// Logo patterns
		if (strpos($content, 'logo') !== false || strpos($content, 'brand') !== false) {
			$patterns[] = 'logo';
		}
		
		// Social media patterns
		if (strpos($content, 'facebook') !== false || strpos($content, 'twitter') !== false || strpos($content, 'social') !== false) {
			$patterns[] = 'social_media';
		}
		
		// Publisher patterns
		if (strpos($content, 'publisher') !== false || strpos($content, 'author') !== false) {
			$patterns[] = 'publisher_info';
		}
		
		return $patterns;
	}

	/**
	 * Get the best pattern for a given schema type
	 *
	 * @param string $schema_type Schema type (faq, article, product, etc.)
	 * @param array $detected_patterns Array of detected patterns
	 * @return string Best pattern to use
	 */
	public static function get_best_pattern($schema_type, $detected_patterns)
	{
		$pattern_priorities = [
			'faq' => ['accordion', 'definition_list', 'faq', 'question_headers'],
			'article' => ['article_element', 'wordpress_content', 'main_content', 'h1_title'],
			'product' => ['woocommerce', 'product_title', 'price', 'purchase_action'],
			'organization' => ['company_info', 'contact_info', 'logo'],
			'person' => ['team_member', 'author', 'job_title'],
			'local_business' => ['business_info', 'contact_info', 'location_info', 'business_hours', 'logo'],
			'website' => ['website_info', 'search_functionality', 'navigation', 'logo', 'social_media']
		];
		
		if (!isset($pattern_priorities[$schema_type])) {
			return 'generic';
		}
		
		$priorities = $pattern_priorities[$schema_type];
		
		foreach ($priorities as $priority) {
			if (in_array($priority, $detected_patterns)) {
				return $priority;
			}
		}
		
		return 'generic';
	}
} 