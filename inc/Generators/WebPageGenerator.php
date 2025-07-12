<?php

namespace BuiltNorth\Schema\Generators;

/**
 * WebPage Schema Generator
 * 
 * Generates WebPage schema from various data structures
 * Supports page information, breadcrumbs, and metadata
 */
class WebPageGenerator extends BaseGenerator
{
    /**
     * Generate WebPage schema from data
     *
     * @param array $data WebPage data
     * @param array $options Generation options
     * @return array JSON-LD schema data
     */
    public static function generate($data, $options = [])
    {
        $schema_data = [
            'name' => self::sanitize_text($data['name'] ?? get_bloginfo('name')),
            'url' => $data['url'] ?? get_site_url(),
        ];

        // Add description
        if (!empty($data['description'])) {
            $schema_data['description'] = self::sanitize_text($data['description']);
        }

        // Add date published and modified
        if (!empty($data['datePublished'])) {
            $schema_data['datePublished'] = $data['datePublished'];
        }
        if (!empty($data['dateModified'])) {
            $schema_data['dateModified'] = $data['dateModified'];
        }

        // Add author information
        if (!empty($data['author'])) {
            $schema_data['author'] = [
                '@type' => 'Person',
                'name' => self::sanitize_text($data['author'])
            ];
        }

        // Add publisher information
        if (!empty($data['publisher'])) {
            $schema_data['publisher'] = [
                '@type' => 'Organization',
                'name' => self::sanitize_text($data['publisher'])
            ];
        }

        // Add breadcrumb navigation
        if (!empty($data['breadcrumb'])) {
            $schema_data['breadcrumb'] = $data['breadcrumb'];
        }

        // Add main entity
        if (!empty($data['mainEntity'])) {
            $schema_data['mainEntity'] = $data['mainEntity'];
        }

        // Add potential actions (search, etc.)
        if (!empty($data['potentialAction'])) {
            $schema_data['potentialAction'] = $data['potentialAction'];
        }

        // Add optional fields
        $optional_fields = [
            'inLanguage' => 'language',
            'inLanguage' => 'locale',
            'keywords' => 'keywords',
            'keywords' => 'meta_keywords',
            'genre' => 'genre',
            'genre' => 'category',
            'audience' => 'audience',
            'audience' => 'target_audience',
            'isAccessibleForFree' => 'is_free',
            'isAccessibleForFree' => 'free_access',
            'isPartOf' => 'is_part_of',
            'isPartOf' => 'parent_page',
            'hasPart' => 'has_part',
            'hasPart' => 'sub_pages'
        ];

        $schema_data = self::merge_optional_fields($schema_data, $data, $optional_fields);

        return self::add_context($schema_data, 'WebPage');
    }
} 