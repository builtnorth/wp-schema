<?php
/**
 * WP Schema Hook Examples
 * 
 * This file demonstrates how to use the new hook-based schema system
 * to replace autodetection with more flexible and powerful schema generation.
 */

// Example 1: Custom Review Block Schema
// This shows how to create a custom review block that automatically generates Review schema

function example_review_block_schema() {
    // Register a custom review block
    register_block_type('example/review-block', [
        'attributes' => [
            'rating' => ['type' => 'number', 'default' => 5],
            'author' => ['type' => 'string', 'default' => ''],
            'product' => ['type' => 'string', 'default' => ''],
            'title' => ['type' => 'string', 'default' => ''],
            'content' => ['type' => 'string', 'default' => '']
        ],
        'render_callback' => 'example_render_review_block'
    ]);
}
add_action('init', 'example_review_block_schema');

// Provide schema data for the review block
add_filter('wp_schema_data_for_block', function($custom_data, $block, $schema_type, $options) {
    if ($block['blockName'] === 'example/review-block') {
        $attrs = $block['attrs'] ?? [];
        
        return [
            'name' => $attrs['title'] ?? '',
            'reviewBody' => $attrs['content'] ?? '',
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => $attrs['rating'] ?? 5,
                'bestRating' => 5,
                'worstRating' => 1
            ],
            'author' => [
                '@type' => 'Person',
                'name' => $attrs['author'] ?? 'Anonymous'
            ],
            'itemReviewed' => [
                '@type' => 'Product',
                'name' => $attrs['product'] ?? ''
            ],
            'datePublished' => current_time('c')
        ];
    }
    
    return null; // Use default extraction for other blocks
}, 10, 4);

// Example 2: Custom Post Type with Schema Override
// This shows how to override schema types for custom post types

function example_custom_post_type_schema() {
    // Register a custom post type for recipes
    register_post_type('recipe', [
        'labels' => [
            'name' => 'Recipes',
            'singular_name' => 'Recipe'
        ],
        'public' => true,
        'supports' => ['title', 'editor', 'thumbnail']
    ]);
    
    // Add custom meta fields
    add_post_meta_box('recipe_details', 'Recipe Details', 'recipe');
}
add_action('init', 'example_custom_post_type_schema');

// Override schema type for recipe post type
add_filter('wp_schema_type_for_post', function($schema_type, $post_id, $options) {
    $post_type = get_post_type($post_id);
    
    if ($post_type === 'recipe') {
        return 'Recipe';
    }
    
    return $schema_type; // Use default detection for other post types
}, 10, 3);

// Provide custom data for recipe posts
add_filter('wp_schema_data_for_post', function($custom_data, $post_id, $schema_type, $options) {
    if ($schema_type === 'Recipe') {
        $cook_time = get_post_meta($post_id, '_recipe_cook_time', true);
        $prep_time = get_post_meta($post_id, '_recipe_prep_time', true);
        $servings = get_post_meta($post_id, '_recipe_servings', true);
        $ingredients = get_post_meta($post_id, '_recipe_ingredients', true);
        $instructions = get_post_meta($post_id, '_recipe_instructions', true);
        
        $recipe_data = [
            'name' => get_the_title($post_id),
            'description' => get_the_excerpt($post_id),
            'image' => get_the_post_thumbnail_url($post_id, 'full'),
            'url' => get_permalink($post_id),
            'datePublished' => get_the_date('c', $post_id),
            'author' => [
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', get_post_field('post_author', $post_id))
            ]
        ];
        
        // Add cooking time
        if ($cook_time) {
            $recipe_data['cookTime'] = 'PT' . $cook_time . 'M';
        }
        
        // Add prep time
        if ($prep_time) {
            $recipe_data['prepTime'] = 'PT' . $prep_time . 'M';
        }
        
        // Add total time
        if ($cook_time && $prep_time) {
            $total_time = $cook_time + $prep_time;
            $recipe_data['totalTime'] = 'PT' . $total_time . 'M';
        }
        
        // Add servings
        if ($servings) {
            $recipe_data['recipeYield'] = $servings . ' servings';
        }
        
        // Add ingredients
        if ($ingredients) {
            $ingredients_array = array_filter(array_map('trim', explode("\n", $ingredients)));
            $recipe_data['recipeIngredient'] = $ingredients_array;
        }
        
        // Add instructions
        if ($instructions) {
            $instructions_array = array_filter(array_map('trim', explode("\n", $instructions)));
            $recipe_data['recipeInstructions'] = array_map(function($instruction) {
                return [
                    '@type' => 'HowToStep',
                    'text' => $instruction
                ];
            }, $instructions_array);
        }
        
        return $recipe_data;
    }
    
    return null; // Use default extraction for other schema types
}, 10, 4);

// Example 3: FAQ Block with Automatic Detection
// This shows how to create a FAQ block that automatically generates FAQPage schema

function example_faq_block_schema() {
    // Register a custom FAQ block
    register_block_type('example/faq-block', [
        'attributes' => [
            'questions' => ['type' => 'array', 'default' => []],
            'answers' => ['type' => 'array', 'default' => []]
        ],
        'render_callback' => 'example_render_faq_block'
    ]);
}
add_action('init', 'example_faq_block_schema');

// Override schema type for FAQ block
add_filter('wp_schema_type_for_block', function($schema_type, $block, $options) {
    if ($block['blockName'] === 'example/faq-block') {
        return 'FAQPage';
    }
    
    return $schema_type; // Use default detection for other blocks
}, 10, 3);

// Provide schema data for FAQ block
add_filter('wp_schema_data_for_block', function($custom_data, $block, $schema_type, $options) {
    if ($block['blockName'] === 'example/faq-block' && $schema_type === 'FAQPage') {
        $attrs = $block['attrs'] ?? [];
        $questions = $attrs['questions'] ?? [];
        $answers = $attrs['answers'] ?? [];
        
        $faq_items = [];
        
        for ($i = 0; $i < count($questions); $i++) {
            if (!empty($questions[$i]) && !empty($answers[$i])) {
                $faq_items[] = [
                    '@type' => 'Question',
                    'name' => $questions[$i],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $answers[$i]
                    ]
                ];
            }
        }
        
        return [
            'mainEntity' => $faq_items
        ];
    }
    
    return null; // Use default extraction for other blocks
}, 10, 4);

// Example 4: Product Block with WooCommerce Integration
// This shows how to integrate with WooCommerce for product schema

function example_product_block_schema() {
    // Register a custom product block
    register_block_type('example/product-block', [
        'attributes' => [
            'product_id' => ['type' => 'number', 'default' => 0],
            'show_price' => ['type' => 'boolean', 'default' => true],
            'show_rating' => ['type' => 'boolean', 'default' => true]
        ],
        'render_callback' => 'example_render_product_block'
    ]);
}
add_action('init', 'example_product_block_schema');

// Override schema type for product block
add_filter('wp_schema_type_for_block', function($schema_type, $block, $options) {
    if ($block['blockName'] === 'example/product-block') {
        return 'Product';
    }
    
    return $schema_type; // Use default detection for other blocks
}, 10, 3);

// Provide schema data for product block
add_filter('wp_schema_data_for_block', function($custom_data, $block, $schema_type, $options) {
    if ($block['blockName'] === 'example/product-block' && $schema_type === 'Product') {
        $attrs = $block['attrs'] ?? [];
        $product_id = $attrs['product_id'] ?? 0;
        
        if ($product_id && function_exists('wc_get_product')) {
            $product = wc_get_product($product_id);
            
            if ($product) {
                $product_data = [
                    'name' => $product->get_name(),
                    'description' => $product->get_description(),
                    'image' => wp_get_attachment_image_url($product->get_image_id(), 'full'),
                    'url' => get_permalink($product_id),
                    'sku' => $product->get_sku(),
                    'brand' => [
                        '@type' => 'Brand',
                        'name' => get_bloginfo('name')
                    ]
                ];
                
                // Add price information
                if ($attrs['show_price'] && $product->get_price()) {
                    $product_data['offers'] = [
                        '@type' => 'Offer',
                        'price' => $product->get_price(),
                        'priceCurrency' => get_woocommerce_currency(),
                        'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                        'url' => get_permalink($product_id)
                    ];
                }
                
                // Add rating information
                if ($attrs['show_rating'] && $product->get_average_rating()) {
                    $product_data['aggregateRating'] = [
                        '@type' => 'AggregateRating',
                        'ratingValue' => $product->get_average_rating(),
                        'reviewCount' => $product->get_review_count(),
                        'bestRating' => 5,
                        'worstRating' => 1
                    ];
                }
                
                return $product_data;
            }
        }
        
        // Fallback for non-WooCommerce products
        return [
            'name' => $attrs['title'] ?? '',
            'description' => $attrs['description'] ?? ''
        ];
    }
    
    return null; // Use default extraction for other blocks
}, 10, 4);

// Example 5: Event Block with Date/Time Handling
// This shows how to create an event block with proper date/time schema

function example_event_block_schema() {
    // Register a custom event block
    register_block_type('example/event-block', [
        'attributes' => [
            'title' => ['type' => 'string', 'default' => ''],
            'description' => ['type' => 'string', 'default' => ''],
            'start_date' => ['type' => 'string', 'default' => ''],
            'end_date' => ['type' => 'string', 'default' => ''],
            'location' => ['type' => 'string', 'default' => ''],
            'organizer' => ['type' => 'string', 'default' => '']
        ],
        'render_callback' => 'example_render_event_block'
    ]);
}
add_action('init', 'example_event_block_schema');

// Override schema type for event block
add_filter('wp_schema_type_for_block', function($schema_type, $block, $options) {
    if ($block['blockName'] === 'example/event-block') {
        return 'Event';
    }
    
    return $schema_type; // Use default detection for other blocks
}, 10, 3);

// Provide schema data for event block
add_filter('wp_schema_data_for_block', function($custom_data, $block, $schema_type, $options) {
    if ($block['blockName'] === 'example/event-block' && $schema_type === 'Event') {
        $attrs = $block['attrs'] ?? [];
        
        $event_data = [
            'name' => $attrs['title'] ?? '',
            'description' => $attrs['description'] ?? ''
        ];
        
        // Add start date
        if ($attrs['start_date']) {
            $event_data['startDate'] = date('c', strtotime($attrs['start_date']));
        }
        
        // Add end date
        if ($attrs['end_date']) {
            $event_data['endDate'] = date('c', strtotime($attrs['end_date']));
        }
        
        // Add location
        if ($attrs['location']) {
            $event_data['location'] = [
                '@type' => 'Place',
                'name' => $attrs['location']
            ];
        }
        
        // Add organizer
        if ($attrs['organizer']) {
            $event_data['organizer'] = [
                '@type' => 'Organization',
                'name' => $attrs['organizer']
            ];
        }
        
        return $event_data;
    }
    
    return null; // Use default extraction for other blocks
}, 10, 4);

// Example 6: Collect Schemas from All Blocks in a Post
// This shows how to collect and output schemas from multiple blocks

function example_collect_block_schemas($post_id) {
    // Collect schemas from all blocks
    $schemas = \BuiltNorth\Schema\SchemaGenerator::collect_schemas_from_blocks($post_id);
    
    // Output each schema
    foreach ($schemas as $schema) {
        \BuiltNorth\Schema\SchemaGenerator::output_schema_script($schema);
    }
}

// Hook into wp_head to output schemas
add_action('wp_head', function() {
    if (is_singular()) {
        example_collect_block_schemas(get_the_ID());
    }
});

// Example 7: Modify Final Schema
// This shows how to add common properties to all schemas

add_filter('wp_schema_final_schema', function($schema, $content, $type, $options) {
    // Add publisher information to all schemas
    $schema['publisher'] = [
        '@type' => 'Organization',
        'name' => get_bloginfo('name'),
        'url' => home_url('/'),
        'logo' => [
            '@type' => 'ImageObject',
            'url' => get_site_icon_url()
        ]
    ];
    
    // Add site URL to all schemas
    $schema['url'] = $schema['url'] ?? home_url('/');
    
    return $schema;
}, 10, 4);

// Example 8: Skip Certain Blocks
// This shows how to control which blocks are processed

add_filter('wp_schema_should_process_block', function($should_process, $block, $post_id, $options) {
    $block_name = $block['blockName'] ?? '';
    
    // Skip certain blocks that don't need schema
    $skip_blocks = [
        'core/spacer',
        'core/separator',
        'core/columns',
        'core/column'
    ];
    
    if (in_array($block_name, $skip_blocks)) {
        return false;
    }
    
    // Only process paragraph blocks if they have specific attributes
    if ($block_name === 'core/paragraph') {
        $attrs = $block['attrs'] ?? [];
        return !empty($attrs['hasSchema']);
    }
    
    return $should_process;
}, 10, 4);

// Example 9: Custom Schema Type
// This shows how to handle custom schema types not built into the system

add_filter('wp_schema_generate_custom_type', function($schema, $type, $data, $options) {
    if ($type === 'CustomType') {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'CustomType',
            'customProperty' => $data['customProperty'] ?? '',
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? ''
        ];
    }
    
    return null; // Use fallback generation for other types
}, 10, 4);

// Example 10: Content Modification
// This shows how to modify content before processing

add_filter('wp_schema_content_before_processing', function($content, $type, $options) {
    // Clean up content for better pattern detection
    $content = wp_strip_all_tags($content);
    $content = preg_replace('/\s+/', ' ', $content);
    $content = trim($content);
    
    return $content;
}, 10, 3);

// Helper functions for block rendering (simplified examples)

function example_render_review_block($attributes) {
    $rating = $attributes['rating'] ?? 5;
    $author = $attributes['author'] ?? '';
    $product = $attributes['product'] ?? '';
    $title = $attributes['title'] ?? '';
    $content = $attributes['content'] ?? '';
    
    return sprintf(
        '<div class="review-block">
            <h3>%s</h3>
            <div class="rating">Rating: %d/5</div>
            <div class="content">%s</div>
            <div class="author">By: %s</div>
            <div class="product">Product: %s</div>
        </div>',
        esc_html($title),
        $rating,
        wp_kses_post($content),
        esc_html($author),
        esc_html($product)
    );
}

function example_render_faq_block($attributes) {
    $questions = $attributes['questions'] ?? [];
    $answers = $attributes['answers'] ?? [];
    
    $output = '<div class="faq-block">';
    
    for ($i = 0; $i < count($questions); $i++) {
        if (!empty($questions[$i]) && !empty($answers[$i])) {
            $output .= sprintf(
                '<div class="faq-item">
                    <h3>%s</h3>
                    <p>%s</p>
                </div>',
                esc_html($questions[$i]),
                wp_kses_post($answers[$i])
            );
        }
    }
    
    $output .= '</div>';
    
    return $output;
}

function example_render_product_block($attributes) {
    $product_id = $attributes['product_id'] ?? 0;
    
    if ($product_id && function_exists('wc_get_product')) {
        $product = wc_get_product($product_id);
        
        if ($product) {
            return sprintf(
                '<div class="product-block">
                    <h3>%s</h3>
                    <p>%s</p>
                    <div class="price">%s</div>
                </div>',
                esc_html($product->get_name()),
                esc_html($product->get_description()),
                $product->get_price_html()
            );
        }
    }
    
    return '<div class="product-block">Product not found</div>';
}

function example_render_event_block($attributes) {
    $title = $attributes['title'] ?? '';
    $description = $attributes['description'] ?? '';
    $start_date = $attributes['start_date'] ?? '';
    $end_date = $attributes['end_date'] ?? '';
    $location = $attributes['location'] ?? '';
    $organizer = $attributes['organizer'] ?? '';
    
    return sprintf(
        '<div class="event-block">
            <h3>%s</h3>
            <p>%s</p>
            <div class="event-details">
                <div class="date">%s - %s</div>
                <div class="location">%s</div>
                <div class="organizer">%s</div>
            </div>
        </div>',
        esc_html($title),
        wp_kses_post($description),
        esc_html($start_date),
        esc_html($end_date),
        esc_html($location),
        esc_html($organizer)
    );
} 