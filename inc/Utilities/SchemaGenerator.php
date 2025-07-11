<?php

namespace BuiltNorth\Utility\Utilities;

use BuiltNorth\Schema\SchemaGenerator as SchemaGeneratorCore;

/**
 * Schema Generator Utility
 * 
 * Provides easy access to schema generation functionality
 * Uses the dedicated wp-schema package for core functionality
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
        return SchemaGeneratorCore::render($content, $type, $options);
    }

    /**
     * Output JSON-LD schema script tag
     *
     * @param string $schema JSON-LD schema markup
     * @return void
     */
    public static function output_schema_script($schema)
    {
        SchemaGeneratorCore::output_schema_script($schema);
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
        return SchemaGeneratorCore::detect_patterns($content, $type);
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
        return SchemaGeneratorCore::get_best_pattern($schema_type, $detected_patterns);
    }

    /**
     * Quick organization schema
     *
     * @param array $data Organization data
     * @return string JSON-LD schema markup
     */
    public static function organization($data)
    {
        return SchemaGeneratorCore::render($data, 'organization');
    }

    /**
     * Quick local business schema
     *
     * @param array $data Business data
     * @return string JSON-LD schema markup
     */
    public static function local_business($data)
    {
        return SchemaGeneratorCore::render($data, 'local_business');
    }

    /**
     * Quick website schema
     *
     * @param array $data Website data
     * @return string JSON-LD schema markup
     */
    public static function website($data)
    {
        return SchemaGeneratorCore::render($data, 'website');
    }

    /**
     * Quick article schema
     *
     * @param mixed $content Article content
     * @return string JSON-LD schema markup
     */
    public static function article($content)
    {
        return SchemaGeneratorCore::render($content, 'article');
    }

    /**
     * Quick FAQ schema
     *
     * @param mixed $content FAQ content
     * @return string JSON-LD schema markup
     */
    public static function faq($content)
    {
        return SchemaGeneratorCore::render($content, 'faq');
    }

    /**
     * Quick product schema
     *
     * @param array $data Product data
     * @return string JSON-LD schema markup
     */
    public static function product($data)
    {
        return SchemaGeneratorCore::render($data, 'product');
    }

    /**
     * Quick person schema
     *
     * @param array $data Person data
     * @return string JSON-LD schema markup
     */
    public static function person($data)
    {
        return SchemaGeneratorCore::render($data, 'person');
    }
} 