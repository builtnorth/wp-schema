<?php

namespace BuiltNorth\Schema;

use BuiltNorth\Schema\Detectors\PatternDetector;
use BuiltNorth\Schema\Extractors\ContentExtractor;
use BuiltNorth\Schema\Generators\BaseGenerator;

/**
 * Schema Generator Utility
 * 
 * Provides easy access to schema generation functionality
 */
class SchemaGenerator
{
    /**
     * Generate schema from various content sources
     *
     * @param mixed $content Content to extract schema from
     * @param string $type Schema type (faq, article, product, etc.)
     * @param array $options Generation options
     * @return string JSON-LD schema markup or empty string
     */
    public static function render($content = '', $type = 'faq', $options = [])
    {
        if (empty($content)) {
            return '';
        }

        // Detect patterns in content
        $patterns = self::detect_patterns($content, $type);
        
        // Extract data based on type
        $extractor = new ContentExtractor($content);
        $data = $extractor->extract($type, $options);
        
        // Generate schema based on type
        return self::generate_schema_by_type($type, $data, $options);
    }

    /**
     * Output JSON-LD schema script tag
     *
     * @param string $schema JSON-LD schema markup
     * @return void
     */
    public static function output_schema_script($schema)
    {
        if (!empty($schema)) {
            echo '<script type="application/ld+json">' . $schema . '</script>';
        }
    }

    /**
     * Detect patterns in content
     *
     * @param string $content Content to analyze
     * @param string $type Schema type
     * @return array Detected patterns
     */
    public static function detect_patterns($content, $type)
    {
        return PatternDetector::detect_patterns($content, $type);
    }

    /**
     * Get the best pattern for a schema type
     *
     * @param string $schema_type Schema type
     * @param array $detected_patterns Detected patterns
     * @return string Best pattern to use
     */
    public static function get_best_pattern($schema_type, $detected_patterns)
    {
        if (empty($detected_patterns)) {
            return '';
        }
        
        // Return the first pattern as default
        return $detected_patterns[0];
    }

    /**
     * Generate schema by type
     *
     * @param string $type Schema type
     * @param array $data Extracted data
     * @param array $options Generation options
     * @return string JSON-LD schema markup
     */
    private static function generate_schema_by_type($type, $data, $options = [])
    {
        $schema_data = [
            '@context' => 'https://schema.org',
            '@type' => ucfirst($type)
        ];

        // Merge data into schema
        foreach ($data as $key => $value) {
            if (!empty($value)) {
                $schema_data[$key] = $value;
            }
        }

        return wp_json_encode($schema_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Quick organization schema
     *
     * @param array $data Organization data
     * @return string JSON-LD schema markup
     */
    public static function organization($data)
    {
        return self::render($data, 'organization');
    }

    /**
     * Quick local business schema
     *
     * @param array $data Business data
     * @return string JSON-LD schema markup
     */
    public static function local_business($data)
    {
        return self::render($data, 'local_business');
    }

    /**
     * Quick website schema
     *
     * @param array $data Website data
     * @return string JSON-LD schema markup
     */
    public static function website($data)
    {
        return self::render($data, 'website');
    }

    /**
     * Quick article schema
     *
     * @param mixed $content Article content
     * @return string JSON-LD schema markup
     */
    public static function article($content)
    {
        return self::render($content, 'article');
    }

    /**
     * Quick FAQ schema
     *
     * @param mixed $content FAQ content
     * @return string JSON-LD schema markup
     */
    public static function faq($content)
    {
        return self::render($content, 'faq');
    }

    /**
     * Quick product schema
     *
     * @param array $data Product data
     * @return string JSON-LD schema markup
     */
    public static function product($data)
    {
        return self::render($data, 'product');
    }

    /**
     * Quick person schema
     *
     * @param array $data Person data
     * @return string JSON-LD schema markup
     */
    public static function person($data)
    {
        return self::render($data, 'person');
    }
} 