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
     * @return array JSON-LD schema data or empty array
     */
    public static function render($content = '', $type = 'faq', $options = [])
    {
        if (empty($content)) {
            return [];
        }

        // If content is already an array, use it directly
        if (is_array($content)) {
            return self::generate_schema_by_type($type, $content, $options);
        }

        // For string content, detect patterns and extract data
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
     * @param array $schema JSON-LD schema data
     * @return void
     */
    public static function output_schema_script($schema)
    {
        if (!empty($schema)) {
            echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
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
     * @return array JSON-LD schema data
     */
    private static function generate_schema_by_type($type, $data, $options = [])
    {
        // Use specific generators for known types
        switch ($type) {
            case 'organization':
                return \BuiltNorth\Schema\Generators\OrganizationGenerator::generate($data, $options);
            case 'local_business':
                return \BuiltNorth\Schema\Generators\LocalBusinessGenerator::generate($data, $options);
            case 'website':
                return \BuiltNorth\Schema\Generators\WebSiteGenerator::generate($data, $options);
            case 'article':
                return \BuiltNorth\Schema\Generators\ArticleGenerator::generate($data, $options);
            case 'product':
                return \BuiltNorth\Schema\Generators\ProductGenerator::generate($data, $options);
            case 'person':
                return \BuiltNorth\Schema\Generators\PersonGenerator::generate($data, $options);
            case 'faq':
                return \BuiltNorth\Schema\Generators\FaqGenerator::generate($data, $options);
            case 'review':
                return \BuiltNorth\Schema\Generators\ReviewGenerator::generate($data, $options);
            case 'aggregate_rating':
                return \BuiltNorth\Schema\Generators\AggregateRatingGenerator::generate($data, $options);
            case 'navigation':
                return \BuiltNorth\Schema\Generators\NavigationGenerator::generate($data, $options);
            default:
                // Fallback to basic schema generation
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

                return $schema_data;
        }
    }

    /**
     * Quick organization schema
     *
     * @param array $data Organization data
     * @return array JSON-LD schema data
     */
    public static function organization($data)
    {
        return self::render($data, 'organization');
    }

    /**
     * Quick local business schema
     *
     * @param array $data Business data
     * @return array JSON-LD schema data
     */
    public static function local_business($data)
    {
        return self::render($data, 'local_business');
    }

    /**
     * Quick website schema
     *
     * @param array $data Website data
     * @return array JSON-LD schema data
     */
    public static function website($data)
    {
        return self::render($data, 'website');
    }

    /**
     * Quick article schema
     *
     * @param mixed $content Article content
     * @return array JSON-LD schema data
     */
    public static function article($content)
    {
        return self::render($content, 'article');
    }

    /**
     * Quick FAQ schema
     *
     * @param mixed $content FAQ content
     * @return array JSON-LD schema data
     */
    public static function faq($content)
    {
        return self::render($content, 'faq');
    }

    /**
     * Quick product schema
     *
     * @param array $data Product data
     * @return array JSON-LD schema data
     */
    public static function product($data)
    {
        return self::render($data, 'product');
    }

    /**
     * Quick person schema
     *
     * @param array $data Person data
     * @return array JSON-LD schema data
     */
    public static function person($data)
    {
        return self::render($data, 'person');
    }
} 