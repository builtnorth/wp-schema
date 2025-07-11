<?php

namespace BuiltNorth\Schema\Extractors\Extractors;

/**
 * Content Extractor
 * 
 * Handles extraction of structured data from HTML content
 */
class ContentExtractor
{
	private $content;
	private $dom;
	private $xpath;

	/**
	 * Constructor
	 *
	 * @param string $content HTML content
	 */
	public function __construct($content)
	{
		$this->content = $content;
		$this->parse_html();
	}

	/**
	 * Parse HTML content
	 */
	private function parse_html()
	{
		$this->dom = new \DOMDocument();
		libxml_use_internal_errors(true);
		$this->dom->loadHTML('<?xml encoding="UTF-8">' . $this->content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		libxml_clear_errors();
		$this->xpath = new \DOMXPath($this->dom);
	}

	/**
	 * Extract data based on schema type
	 *
	 * @param string $type Schema type
	 * @param array $options Extraction options
	 * @return array Extracted data
	 */
	public function extract($type, $options = [])
	{
		switch ($type) {
			case 'faq':
				return $this->extract_faq_data($options);
			case 'article':
				return $this->extract_article_data($options);
			case 'product':
				return $this->extract_product_data($options);
			case 'organization':
				return $this->extract_organization_data($options);
			case 'person':
				return $this->extract_person_data($options);
			case 'local_business':
			case 'localbusiness':
				return $this->extract_local_business_data($options);
			case 'website':
			case 'web_site':
				return $this->extract_website_data($options);
			default:
				return [];
		}
	}

	/**
	 * Extract FAQ data with pattern recognition
	 *
	 * @param array $options Extraction options
	 * @return array FAQ data
	 */
	private function extract_faq_data($options = [])
	{
		$faq_items = [];
		
		// Try multiple patterns to find FAQ content
		$patterns = [
			// Accordion pattern
			'accordion' => [
				'container' => '//div[contains(@class, "accordion-item") or contains(@class, "faq-item") or contains(@class, "wp-block-polaris-accordion-item")]',
				'question' => './/button//span | .//h3 | .//h4 | .//dt | .//span[contains(@class, "title")]',
				'answer' => './/div[contains(@class, "content")] | .//dd | .//p | .//div[contains(@class, "accordion-item__content")]'
			],
			// List pattern
			'list' => [
				'container' => '//li[contains(@class, "faq")] | //dt',
				'question' => './/h3 | .//h4 | .',
				'answer' => './/dd | .//p | .//div'
			],
			// Generic pattern
			'generic' => [
				'container' => '//div[contains(@class, "faq")] | //section[contains(@class, "faq")]',
				'question' => './/h3 | .//h4 | .//h5',
				'answer' => './/p | .//div'
			]
		];

		foreach ($patterns as $pattern_name => $pattern) {
			$containers = $this->xpath->query($pattern['container']);
			
			if ($containers->length > 0) {
				foreach ($containers as $container) {
					$question_element = $this->xpath->query($pattern['question'], $container)->item(0);
					$answer_element = $this->xpath->query($pattern['answer'], $container)->item(0);
					
					if ($question_element && $answer_element) {
						$question = trim($question_element->textContent);
						$answer = trim($answer_element->textContent);
						
						if (!empty($question) && !empty($answer)) {
							$faq_items[] = [
								'@type' => 'Question',
								'name' => wp_strip_all_tags($question),
								'acceptedAnswer' => [
									'@type' => 'Answer',
									'text' => wp_strip_all_tags($answer)
								]
							];
						}
					}
				}
				
				// If we found items with this pattern, break
				if (!empty($faq_items)) {
					break;
				}
			}
		}

		return ['items' => $faq_items];
	}

	/**
	 * Extract article data
	 *
	 * @param array $options Extraction options
	 * @return array Article data
	 */
	private function extract_article_data($options = [])
	{
		$data = [];
		
		// Extract title
		$title_selectors = ['//h1', '//h2', '//title'];
		foreach ($title_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				$data['title'] = wp_strip_all_tags(trim($element->textContent));
				break;
			}
		}
		
		// Extract content
		$content_selectors = ['//article', '//main', '//div[contains(@class, "content")]', '//div[contains(@class, "entry-content")]'];
		foreach ($content_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				$data['content'] = wp_strip_all_tags(trim($element->textContent));
				break;
			}
		}
		
		// Extract author
		$author_selectors = ['//span[contains(@class, "author")]', '//span[contains(@class, "byline")]'];
		foreach ($author_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				$data['author'] = wp_strip_all_tags(trim($element->textContent));
				break;
			}
		}
		
		// Extract date
		$date_selectors = ['//time[contains(@class, "published")]', '//time', '//span[contains(@class, "date")]'];
		foreach ($date_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				$data['date'] = wp_strip_all_tags(trim($element->textContent));
				break;
			}
		}
		
		return $data;
	}

	/**
	 * Extract product data
	 *
	 * @param array $options Extraction options
	 * @return array Product data
	 */
	private function extract_product_data($options = [])
	{
		$data = [];
		
		// Extract product name
		$name_selectors = ['//h1[contains(@class, "product")]', '//h1', '//h2'];
		foreach ($name_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				$data['name'] = wp_strip_all_tags(trim($element->textContent));
				break;
			}
		}
		
		// Extract price
		$price_selectors = ['//span[contains(@class, "price")]', '//span[contains(text(), "$")]'];
		foreach ($price_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				$data['price'] = wp_strip_all_tags(trim($element->textContent));
				break;
			}
		}
		
		// Extract description
		$description_selectors = ['//div[contains(@class, "description")]', '//p[contains(@class, "description")]'];
		foreach ($description_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				$data['description'] = wp_strip_all_tags(trim($element->textContent));
				break;
			}
		}
		
		return $data;
	}

	/**
	 * Extract organization data
	 *
	 * @param array $options Extraction options
	 * @return array Organization data
	 */
	private function extract_organization_data($options = [])
	{
		$data = [];
		
		// Extract organization name
		$name_selectors = ['//h1', '//h2', '//span[contains(@class, "company")]', '//span[contains(@class, "organization")]'];
		foreach ($name_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				$data['name'] = wp_strip_all_tags(trim($element->textContent));
				break;
			}
		}
		
		// Extract logo with comprehensive support
		$logo_data = $this->extract_logo_data($options);
		if (!empty($logo_data)) {
			$data['logo'] = $logo_data;
		}

		// Extract contact information
		$contact_data = $this->extract_contact_data($options);
		if (!empty($contact_data)) {
			$data = array_merge($data, $contact_data);
		}

		// Extract address information
		$address_data = $this->extract_address_data($options);
		if (!empty($address_data)) {
			$data['address'] = $address_data;
		}

		// Extract social media links
		$social_data = $this->extract_social_media_data($options);
		if (!empty($social_data)) {
			$data['social_media'] = $social_data;
		}

		// Extract description
		$description_selectors = ['//div[contains(@class, "description")]', '//p[contains(@class, "description")]', '//meta[@name="description"]'];
		foreach ($description_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				if ($element->tagName === 'meta') {
					$data['description'] = $element->getAttribute('content');
				} else {
					$data['description'] = wp_strip_all_tags(trim($element->textContent));
				}
				break;
			}
		}
		
		return $data;
	}

	/**
	 * Extract local business data
	 *
	 * @param array $options Extraction options
	 * @return array Local business data
	 */
	private function extract_local_business_data($options = [])
	{
		$data = [];
		
		// Extract business name
		$name_selectors = ['//h1', '//h2', '//span[contains(@class, "business")]', '//span[contains(@class, "company")]'];
		foreach ($name_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				$data['name'] = wp_strip_all_tags(trim($element->textContent));
				break;
			}
		}

		// Extract business type/category
		$category_selectors = ['//span[contains(@class, "category")]', '//span[contains(@class, "type")]', '//meta[@property="business:category"]'];
		foreach ($category_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				if ($element->tagName === 'meta') {
					$data['category'] = $element->getAttribute('content');
				} else {
					$data['category'] = wp_strip_all_tags(trim($element->textContent));
				}
				break;
			}
		}
		
		// Extract logo with comprehensive support
		$logo_data = $this->extract_logo_data($options);
		if (!empty($logo_data)) {
			$data['logo'] = $logo_data;
		}

		// Extract contact information
		$contact_data = $this->extract_contact_data($options);
		if (!empty($contact_data)) {
			$data = array_merge($data, $contact_data);
		}

		// Extract address information
		$address_data = $this->extract_address_data($options);
		if (!empty($address_data)) {
			$data['address'] = $address_data;
		}

		// Extract geo coordinates
		$geo_data = $this->extract_geo_data($options);
		if (!empty($geo_data)) {
			$data['geo'] = $geo_data;
		}

		// Extract business hours
		$hours_data = $this->extract_business_hours_data($options);
		if (!empty($hours_data)) {
			$data['business_hours'] = $hours_data;
		}

		// Extract social media links
		$social_data = $this->extract_social_media_data($options);
		if (!empty($social_data)) {
			$data['social_media'] = $social_data;
		}

		// Extract description
		$description_selectors = ['//div[contains(@class, "description")]', '//p[contains(@class, "description")]', '//meta[@name="description"]'];
		foreach ($description_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				if ($element->tagName === 'meta') {
					$data['description'] = $element->getAttribute('content');
				} else {
					$data['description'] = wp_strip_all_tags(trim($element->textContent));
				}
				break;
			}
		}
		
		return $data;
	}

	/**
	 * Extract website data
	 *
	 * @param array $options Extraction options
	 * @return array Website data
	 */
	private function extract_website_data($options = [])
	{
		$data = [];
		
		// Extract website name
		$name_selectors = ['//title', '//h1', '//meta[@property="og:site_name"]'];
		foreach ($name_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				if ($element->tagName === 'meta') {
					$data['name'] = $element->getAttribute('content');
				} else {
					$data['name'] = wp_strip_all_tags(trim($element->textContent));
				}
				break;
			}
		}
		
		// Extract logo with comprehensive support
		$logo_data = $this->extract_logo_data($options);
		if (!empty($logo_data)) {
			$data['logo'] = $logo_data;
		}

		// Extract description
		$description_selectors = ['//meta[@name="description"]', '//meta[@property="og:description"]', '//div[contains(@class, "description")]'];
		foreach ($description_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				if ($element->tagName === 'meta') {
					$data['description'] = $element->getAttribute('content');
				} else {
					$data['description'] = wp_strip_all_tags(trim($element->textContent));
				}
				break;
			}
		}

		// Extract social media links
		$social_data = $this->extract_social_media_data($options);
		if (!empty($social_data)) {
			$data['social_media'] = $social_data;
		}

		// Extract language/locale
		$language_selectors = ['//html[@lang]', '//meta[@http-equiv="content-language"]'];
		foreach ($language_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				if ($element->tagName === 'html') {
					$data['language'] = $element->getAttribute('lang');
				} else {
					$data['language'] = $element->getAttribute('content');
				}
				break;
			}
		}
		
		return $data;
	}

	/**
	 * Extract logo data with comprehensive support
	 *
	 * @param array $options Extraction options
	 * @return array|string Logo data
	 */
	private function extract_logo_data($options = [])
	{
		// Multiple logo selectors for comprehensive coverage
		$logo_selectors = [
			'//img[contains(@class, "logo")]',
			'//img[contains(@alt, "logo")]',
			'//img[contains(@alt, "Logo")]',
			'//img[contains(@src, "logo")]',
			'//div[contains(@class, "logo")]//img',
			'//header//img[contains(@class, "logo")]',
			'//meta[@property="og:logo"]',
			'//meta[@name="logo"]'
		];

		foreach ($logo_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				if ($element->tagName === 'meta') {
					return $element->getAttribute('content');
				} else {
					$logo_data = [
						'url' => $element->getAttribute('src'),
						'alt' => $element->getAttribute('alt')
					];

					// Add dimensions if available
					$width = $element->getAttribute('width');
					$height = $element->getAttribute('height');
					if ($width) {
						$logo_data['width'] = (int) $width;
					}
					if ($height) {
						$logo_data['height'] = (int) $height;
					}

					return $logo_data;
				}
			}
		}

		return '';
	}

	/**
	 * Extract contact data
	 *
	 * @param array $options Extraction options
	 * @return array Contact data
	 */
	private function extract_contact_data($options = [])
	{
		$contact_data = [];

		// Extract telephone
		$phone_selectors = [
			'//a[contains(@href, "tel:")]',
			'//span[contains(@class, "phone")]',
			'//span[contains(@class, "telephone")]',
			'//div[contains(@class, "contact")]//span[contains(text(), "+")]'
		];

		foreach ($phone_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				if ($element->tagName === 'a') {
					$contact_data['telephone'] = str_replace('tel:', '', $element->getAttribute('href'));
				} else {
					$contact_data['telephone'] = wp_strip_all_tags(trim($element->textContent));
				}
				break;
			}
		}

		// Extract email
		$email_selectors = [
			'//a[contains(@href, "mailto:")]',
			'//span[contains(@class, "email")]',
			'//div[contains(@class, "contact")]//a[contains(@href, "mailto:")]'
		];

		foreach ($email_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				$contact_data['email'] = str_replace('mailto:', '', $element->getAttribute('href'));
				break;
			}
		}

		return $contact_data;
	}

	/**
	 * Extract address data
	 *
	 * @param array $options Extraction options
	 * @return array Address data
	 */
	private function extract_address_data($options = [])
	{
		$address_data = [];

		// Extract address elements
		$address_selectors = [
			'//address',
			'//div[contains(@class, "address")]',
			'//div[contains(@class, "location")]',
			'//span[contains(@class, "address")]'
		];

		foreach ($address_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				$address_text = wp_strip_all_tags(trim($element->textContent));
				if (!empty($address_text)) {
					$address_data['streetAddress'] = $address_text;
				}
				break;
			}
		}

		// Extract city
		$city_selectors = [
			'//span[contains(@class, "city")]',
			'//span[contains(@class, "locality")]'
		];

		foreach ($city_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				$address_data['addressLocality'] = wp_strip_all_tags(trim($element->textContent));
				break;
			}
		}

		// Extract state/region
		$state_selectors = [
			'//span[contains(@class, "state")]',
			'//span[contains(@class, "region")]'
		];

		foreach ($state_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				$address_data['addressRegion'] = wp_strip_all_tags(trim($element->textContent));
				break;
			}
		}

		// Extract postal code
		$postal_selectors = [
			'//span[contains(@class, "postal")]',
			'//span[contains(@class, "zip")]'
		];

		foreach ($postal_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				$address_data['postalCode'] = wp_strip_all_tags(trim($element->textContent));
				break;
			}
		}

		return $address_data;
	}

	/**
	 * Extract geo coordinates
	 *
	 * @param array $options Extraction options
	 * @return array Geo data
	 */
	private function extract_geo_data($options = [])
	{
		$geo_data = [];

		// Extract latitude
		$lat_selectors = [
			'//meta[@name="geo.position"]',
			'//meta[@property="place:location:latitude"]',
			'//span[contains(@class, "latitude")]'
		];

		foreach ($lat_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				if ($element->tagName === 'meta') {
					$content = $element->getAttribute('content');
					$parts = explode(';', $content);
					if (isset($parts[0])) {
						$geo_data['latitude'] = (float) trim($parts[0]);
					}
				} else {
					$geo_data['latitude'] = (float) wp_strip_all_tags(trim($element->textContent));
				}
				break;
			}
		}

		// Extract longitude
		$lng_selectors = [
			'//meta[@property="place:location:longitude"]',
			'//span[contains(@class, "longitude")]'
		];

		foreach ($lng_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				if ($element->tagName === 'meta') {
					$content = $element->getAttribute('content');
					$parts = explode(';', $content);
					if (isset($parts[1])) {
						$geo_data['longitude'] = (float) trim($parts[1]);
					}
				} else {
					$geo_data['longitude'] = (float) wp_strip_all_tags(trim($element->textContent));
				}
				break;
			}
		}

		return $geo_data;
	}

	/**
	 * Extract business hours data
	 *
	 * @param array $options Extraction options
	 * @return array Business hours data
	 */
	private function extract_business_hours_data($options = [])
	{
		$hours_data = [];

		// Extract business hours
		$hours_selectors = [
			'//div[contains(@class, "hours")]',
			'//div[contains(@class, "business-hours")]',
			'//div[contains(@class, "opening-hours")]',
			'//time[contains(@class, "hours")]'
		];

		foreach ($hours_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				$hours_text = wp_strip_all_tags(trim($element->textContent));
				if (!empty($hours_text)) {
					$hours_data[] = $hours_text;
				}
			}
		}

		return $hours_data;
	}

	/**
	 * Extract social media data
	 *
	 * @param array $options Extraction options
	 * @return array Social media data
	 */
	private function extract_social_media_data($options = [])
	{
		$social_data = [];

		// Extract social media links
		$social_selectors = [
			'//a[contains(@href, "facebook.com")]',
			'//a[contains(@href, "twitter.com")]',
			'//a[contains(@href, "linkedin.com")]',
			'//a[contains(@href, "instagram.com")]',
			'//a[contains(@href, "youtube.com")]',
			'//a[contains(@href, "github.com")]'
		];

		foreach ($social_selectors as $selector) {
			$elements = $this->xpath->query($selector);
			foreach ($elements as $element) {
				$url = $element->getAttribute('href');
				if (!empty($url)) {
					$social_data[] = $url;
				}
			}
		}

		return $social_data;
	}

	/**
	 * Extract person data
	 *
	 * @param array $options Extraction options
	 * @return array Person data
	 */
	private function extract_person_data($options = [])
	{
		$data = [];
		
		// Extract person name
		$name_selectors = ['//h1', '//h2', '//span[contains(@class, "name")]'];
		foreach ($name_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				$data['name'] = wp_strip_all_tags(trim($element->textContent));
				break;
			}
		}
		
		// Extract job title
		$job_selectors = ['//span[contains(@class, "job")]', '//span[contains(@class, "title")]'];
		foreach ($job_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				$data['job_title'] = wp_strip_all_tags(trim($element->textContent));
				break;
			}
		}

		// Extract contact information
		$contact_data = $this->extract_contact_data($options);
		if (!empty($contact_data)) {
			$data = array_merge($data, $contact_data);
		}

		// Extract social media links
		$social_data = $this->extract_social_media_data($options);
		if (!empty($social_data)) {
			$data['social_media'] = $social_data;
		}

		// Extract image/photo
		$image_selectors = ['//img[contains(@class, "photo")]', '//img[contains(@class, "avatar")]', '//img[contains(@alt, "photo")]'];
		foreach ($image_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				$data['image'] = $element->getAttribute('src');
				break;
			}
		}

		// Extract description
		$description_selectors = ['//div[contains(@class, "description")]', '//p[contains(@class, "description")]', '//meta[@name="description"]'];
		foreach ($description_selectors as $selector) {
			$element = $this->xpath->query($selector)->item(0);
			if ($element) {
				if ($element->tagName === 'meta') {
					$data['description'] = $element->getAttribute('content');
				} else {
					$data['description'] = wp_strip_all_tags(trim($element->textContent));
				}
				break;
			}
		}
		
		return $data;
	}
} 