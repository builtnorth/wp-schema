# WP Schema Hook System

This document explains how to use the hook-based schema generation system in the wp-schema package. This system provides flexible and powerful schema generation through WordPress hooks.

## Overview

The new hook system allows plugins and themes to:

1. **Override schema types** for posts (post type determines schema type)
2. **Provide custom data** for schema generation
3. **Modify detected patterns** and extracted data
4. **Handle custom schema types** not built into the system
5. **Provide block-level data** (blocks contribute data, not schema type)
6. **Collect schemas from multiple sources** within a single post

**Important:** Schema types are determined by post type, not by individual blocks. Blocks only contribute data to the schema, they do not determine the overall schema type.

## Core Hooks

### 1. Schema Type Overrides

#### `wp_schema_type_for_post`

Override the schema type for a specific post.

```php
add_filter('wp_schema_type_for_post', function($schema_type, $post_id, $options) {
    // Check if this is a review post
    if (get_post_meta($post_id, '_is_review', true)) {
        return 'Review';
    }

    // Check if this is a product post
    if (get_post_meta($post_id, '_product_data', true)) {
        return 'Product';
    }

    return $schema_type; // Return null to use default detection
}, 10, 3);
```

#### `wp_schema_data_for_block`

Provide schema data for a specific block. Note that schema types are determined by post type, not by individual blocks.

```php
add_filter('wp_schema_data_for_block', function($custom_data, $block, $schema_type, $options) {
    $block_name = $block['blockName'] ?? '';
    $block_attrs = $block['attrs'] ?? [];

    // Provide data for review blocks when schema type supports it
    if ($block_name === 'my-plugin/review-block' && $schema_type === 'Review') {
        return [
            'name' => $block_attrs['title'] ?? '',
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => $block_attrs['rating'] ?? 5,
                'bestRating' => 5
            ],
            'author' => [
                '@type' => 'Person',
                'name' => $block_attrs['author'] ?? ''
            ],
            'itemReviewed' => [
                '@type' => 'Product',
                'name' => $block_attrs['product'] ?? ''
            ]
        ];
    }

    // Provide data for FAQ blocks when schema type supports it
    if ($block_name === 'my-plugin/faq-block' && ($schema_type === 'FAQPage' || $schema_type === 'WebPage' || $schema_type === 'Article')) {
        return [
            'mainEntity' => [
                [
                    '@type' => 'Question',
                    'name' => $block_attrs['question'] ?? '',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $block_attrs['answer'] ?? ''
                    ]
                ]
            ]
        ];
    }

    return null; // Return null to use default extraction
}, 10, 4);
```

#### `wp_schema_type_override`

Override the schema type during generation.

```php
add_filter('wp_schema_type_override', function($type, $content, $options) {
    // Check content for specific patterns
    if (strpos($content, 'review-rating') !== false) {
        return 'Review';
    }

    return $type;
}, 10, 3);
```

### 2. Custom Data Provision

#### `wp_schema_data_for_post`

Provide custom data for a specific post.

```php
add_filter('wp_schema_data_for_post', function($custom_data, $post_id, $schema_type, $options) {
    if ($schema_type === 'Review') {
        $rating = get_post_meta($post_id, '_review_rating', true);
        $author = get_post_meta($post_id, '_review_author', true);
        $item_reviewed = get_post_meta($post_id, '_item_reviewed', true);

        return [
            'name' => get_the_title($post_id),
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => $rating,
                'bestRating' => 5
            ],
            'author' => [
                '@type' => 'Person',
                'name' => $author
            ],
            'itemReviewed' => [
                '@type' => 'Product',
                'name' => $item_reviewed
            ]
        ];
    }

    return null; // Return null to use default extraction
}, 10, 4);
```

### 3. Content and Data Modification

#### `wp_schema_content_before_processing`

Modify content before processing.

```php
add_filter('wp_schema_content_before_processing', function($content, $type, $options) {
    // Clean up content for better pattern detection
    $content = wp_strip_all_tags($content);
    $content = preg_replace('/\s+/', ' ', $content);

    return $content;
}, 10, 3);
```

#### `wp_schema_extracted_data`

Modify extracted data before schema generation.

```php
add_filter('wp_schema_extracted_data', function($data, $content, $type, $options) {
    if ($type === 'Article') {
        // Add author information
        $data['author'] = [
            '@type' => 'Person',
            'name' => get_the_author_meta('display_name', get_post_field('post_author'))
        ];

        // Add publisher information
        $data['publisher'] = [
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'logo' => [
                '@type' => 'ImageObject',
                'url' => get_site_icon_url()
            ]
        ];
    }

    return $data;
}, 10, 4);
```

### 4. Pattern Detection

#### `wp_schema_detected_patterns`

Modify detected patterns.

```php
add_filter('wp_schema_detected_patterns', function($patterns, $content, $type, $options) {
    // Add custom pattern detection
    if (strpos($content, 'my-custom-pattern') !== false) {
        $patterns[] = 'custom_pattern';
    }

    return $patterns;
}, 10, 4);
```

### 5. Schema Generation Control

#### `wp_schema_override_generation`

Completely override the generation process.

```php
add_filter('wp_schema_override_generation', function($override, $content, $type, $options) {
    // Handle special cases
    if ($type === 'CustomSchema') {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'CustomType',
            'customProperty' => 'custom value'
        ];
    }

    return null; // Return null to use default generation
}, 10, 4);
```

#### `wp_schema_generate_for_type`

Override generation for specific types.

```php
add_filter('wp_schema_generate_for_type', function($schema, $type, $data, $options) {
    if ($type === 'Review') {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Review',
            'name' => $data['name'] ?? '',
            'reviewRating' => $data['reviewRating'] ?? '',
            'author' => $data['author'] ?? '',
            'itemReviewed' => $data['itemReviewed'] ?? ''
        ];
    }

    return null; // Return null to use default generation
}, 10, 4);
```

#### `wp_schema_generate_custom_type`

Handle unknown schema types.

```php
add_filter('wp_schema_generate_custom_type', function($schema, $type, $data, $options) {
    // Handle custom schema types
    if ($type === 'MyCustomType') {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'MyCustomType',
            'customField' => $data['customField'] ?? ''
        ];
    }

    return null; // Return null to use fallback generation
}, 10, 4);
```

### 6. Block-Level Control

#### `wp_schema_should_process_block`

Control which blocks should be processed.

```php
add_filter('wp_schema_should_process_block', function($should_process, $block, $post_id, $options) {
    $block_name = $block['blockName'] ?? '';

    // Skip certain blocks
    if (in_array($block_name, ['core/spacer', 'core/separator'])) {
        return false;
    }

    // Only process blocks with specific attributes
    if ($block_name === 'core/paragraph') {
        $attrs = $block['attrs'] ?? [];
        return !empty($attrs['hasSchema']);
    }

    return $should_process;
}, 10, 4);
```

#### `wp_schema_block_content`

Modify block content before processing.

```php
add_filter('wp_schema_block_content', function($content, $block, $options) {
    // Clean up block content
    $content = wp_strip_all_tags($content);
    $content = trim($content);

    return $content;
}, 10, 3);
```

#### `wp_schema_block_attributes`

Modify block attributes before processing.

```php
add_filter('wp_schema_block_attributes', function($attrs, $block, $options) {
    // Add default attributes
    if (empty($attrs['schemaType'])) {
        $attrs['schemaType'] = 'Article';
    }

    return $attrs;
}, 10, 3);
```

### 7. Final Schema Modification

#### `wp_schema_final_schema`

Modify the final schema before output.

### 8. Configuration

The system is designed to be hook-only by default. No additional configuration is needed.

```php
add_filter('wp_schema_final_schema', function($schema, $content, $type, $options) {
    // Add common properties to all schemas
    $schema['@context'] = 'https://schema.org';

    // Add site information
    $schema['publisher'] = [
        '@type' => 'Organization',
        'name' => get_bloginfo('name'),
        'url' => home_url('/')
    ];

    return $schema;
}, 10, 4);
```

#### `wp_schema_collected_block_schemas`

Modify collected block schemas.

```php
add_filter('wp_schema_collected_block_schemas', function($schemas, $post_id, $options) {
    // Remove duplicate schemas
    $schemas = array_unique($schemas, SORT_REGULAR);

    // Add post-level schema
    $post_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'name' => get_the_title($post_id),
        'url' => get_permalink($post_id)
    ];

    array_unshift($schemas, $post_schema);

    return $schemas;
}, 10, 3);
```

## Usage Examples

### Example 1: Custom Review Block

```php
// Register a custom review block
function my_plugin_register_review_block() {
    register_block_type('my-plugin/review-block', [
        'attributes' => [
            'rating' => ['type' => 'number', 'default' => 5],
            'author' => ['type' => 'string', 'default' => ''],
            'product' => ['type' => 'string', 'default' => ''],
            'title' => ['type' => 'string', 'default' => '']
        ],
        'render_callback' => 'my_plugin_render_review_block'
    ]);
}
add_action('init', 'my_plugin_register_review_block');

// Provide schema data for the review block
add_filter('wp_schema_data_for_block', function($custom_data, $block, $schema_type, $options) {
    if ($block['blockName'] === 'my-plugin/review-block') {
        $attrs = $block['attrs'] ?? [];

        return [
            'name' => $attrs['title'] ?? '',
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => $attrs['rating'] ?? 5,
                'bestRating' => 5
            ],
            'author' => [
                '@type' => 'Person',
                'name' => $attrs['author'] ?? ''
            ],
            'itemReviewed' => [
                '@type' => 'Product',
                'name' => $attrs['product'] ?? ''
            ]
        ];
    }

    return null;
}, 10, 4);
```

### Example 2: Custom Post Type Schema

```php
// Override schema type for custom post type
add_filter('wp_schema_type_for_post', function($schema_type, $post_id, $options) {
    $post_type = get_post_type($post_id);

    if ($post_type === 'my_custom_type') {
        // Check post meta for specific schema type
        $custom_schema = get_post_meta($post_id, '_custom_schema_type', true);
        if ($custom_schema) {
            return $custom_schema;
        }

        return 'CustomType'; // Default for this post type
    }

    return $schema_type;
}, 10, 3);

// Provide custom data for the custom post type
add_filter('wp_schema_data_for_post', function($custom_data, $post_id, $schema_type, $options) {
    if ($schema_type === 'CustomType') {
        $custom_field = get_post_meta($post_id, '_custom_field', true);

        return [
            'name' => get_the_title($post_id),
            'customProperty' => $custom_field,
            'url' => get_permalink($post_id)
        ];
    }

    return null;
}, 10, 4);
```

### Example 3: Block Collection

```php
// Collect schemas from all blocks in a post
function my_plugin_output_block_schemas($post_id) {
    $schemas = \BuiltNorth\Schema\SchemaGenerator::collect_schemas_from_blocks($post_id);

    foreach ($schemas as $schema) {
        \BuiltNorth\Schema\SchemaGenerator::output_schema_script($schema);
    }
}

// Hook into wp_head
add_action('wp_head', function() {
    if (is_singular()) {
        my_plugin_output_block_schemas(get_the_ID());
    }
});
```

## Best Practices

1. **Always return null** when you don't want to override the default behavior
2. **Use specific hooks** rather than broad overrides when possible
3. **Validate data** before returning it from hooks
4. **Consider performance** - avoid expensive operations in hooks
5. **Document your hooks** for other developers
6. **Test thoroughly** - hooks can affect the entire schema generation process

## How It Works

The wp-schema package uses a **hook-only approach** for schema generation. This means:

1. **No autodetection** - All schema data must be provided through hooks
2. **Explicit control** - You have complete control over what schema is generated
3. **Performance focused** - No expensive pattern matching or content parsing
4. **Plugin/theme friendly** - Easy integration with custom blocks and post types

### Key Benefits

- **Performance** - No content parsing or pattern matching
- **Flexibility** - Complete control over schema generation
- **Reliability** - Explicit data provision instead of guessing
- **Maintainability** - Clear separation of concerns
- **Extensibility** - Easy to add new schema types and data sources
