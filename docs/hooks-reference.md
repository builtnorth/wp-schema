# WP Schema Framework - Complete Hooks & Filters Reference

## Actions

### Core Actions

#### `wp_schema_framework_register_providers`
Fired when the framework is ready to register providers.

**Parameters:**
- `$app` (App) - The main App instance

**Usage:**
```php
add_action('wp_schema_framework_register_providers', function($app) {
    $app->get_registry()->register('custom', new CustomProvider());
});
```

#### `wp_schema_framework_ready`
Fired when the framework is fully initialized and ready.

**Parameters:**
- `$app` (App) - The main App instance

**Usage:**
```php
add_action('wp_schema_framework_ready', function($app) {
    // Framework is ready, do custom initialization
});
```

#### `wp_schema_framework_before_output`
Fired before schema is output to the page.

**Parameters:**
- `$context` (string) - Current page context

**Usage:**
```php
add_action('wp_schema_framework_before_output', function($context) {
    // Perform actions before schema output
});
```

#### `wp_schema_framework_after_output`
Fired after schema has been output to the page.

**Parameters:**
- `$context` (string) - Current page context
- `$graph` (SchemaGraph) - The graph that was output

**Usage:**
```php
add_action('wp_schema_framework_after_output', function($context, $graph) {
    // Perform actions after schema output
}, 10, 2);
```

## Filters

### Global Control Filters

#### `wp_schema_framework_output_enabled`
Enable or disable all schema output.

**Parameters:**
- `$enabled` (bool) - Whether output is enabled (default: true)

**Usage:**
```php
// Disable schema on specific pages
add_filter('wp_schema_framework_output_enabled', function($enabled) {
    if (is_page('no-schema')) {
        return false;
    }
    return $enabled;
});
```

#### `wp_schema_framework_context`
Override the detected page context.

**Parameters:**
- `$context` (string) - Detected context ('home', 'singular', 'archive', 'search', 'error')

**Usage:**
```php
add_filter('wp_schema_framework_context', function($context) {
    if (is_page('special')) {
        return 'custom_context';
    }
    return $context;
});
```

### Graph & Piece Filters

#### `wp_schema_framework_pieces`
Modify the complete array of schema pieces before assembly.

**Parameters:**
- `$pieces` (array) - Array of SchemaPiece objects
- `$context` (string) - Current page context

**Usage:**
```php
add_filter('wp_schema_framework_pieces', function($pieces, $context) {
    // Add custom piece
    $pieces[] = new SchemaPiece('#custom', 'CustomType');
    return $pieces;
}, 10, 2);
```

#### `wp_schema_framework_graph`
Modify the final schema graph before output.

**Parameters:**
- `$graph` (array) - Complete schema graph array

**Usage:**
```php
add_filter('wp_schema_framework_graph', function($graph) {
    // Modify final output
    return $graph;
});
```

#### `wp_schema_framework_json_output`
Modify the final JSON-LD string before it's output.

**Parameters:**
- `$json` (string) - JSON-LD string
- `$graph_data` (array) - Original graph data array

**Usage:**
```php
add_filter('wp_schema_framework_json_output', function($json, $graph_data) {
    // Modify JSON string (e.g., minify, add custom formatting)
    return $json;
}, 10, 2);
```

#### `wp_schema_framework_piece_{type}`
Modify a specific schema piece by type.

**Parameters:**
- `$piece` (SchemaPiece) - The schema piece
- `$context` (string) - Current page context

**Available types:** `article`, `webpage`, `organization`, `person`, `event`, `product`, etc.

**Usage:**
```php
add_filter('wp_schema_framework_piece_article', function($piece, $context) {
    $piece->set('customProperty', 'value');
    return $piece;
}, 10, 2);
```

#### `wp_schema_framework_piece_id_{id}`
Modify a specific schema piece by ID.

**Parameters:**
- `$piece` (SchemaPiece) - The schema piece
- `$context` (string) - Current page context

**Note:** Piece IDs are sanitized for hook names (`#` removed and `/`/`:` replaced with `_`), so `#organization` maps to `organization`.

**Usage:**
```php
add_filter('wp_schema_framework_piece_id_organization', function($piece, $context) {
    $piece->set('telephone', '+1234567890');
    return $piece;
}, 10, 2);
```

### Provider Data Filters

#### `wp_schema_framework_organization_data`
Modify organization schema data.

**Parameters:**
- `$data` (array) - Organization schema array
- `$context` (string) - Current page context

#### `wp_schema_framework_organization_type`
Override the organization schema type.

**Parameters:**
- `$type` (string) - Organization type (default: 'Organization')

**Usage:**
```php
add_filter('wp_schema_framework_organization_type', function($type) {
    return 'LocalBusiness';
});
```

#### `wp_schema_framework_website_data`
Modify website schema data.

**Parameters:**
- `$data` (array) - Website schema array
- `$context` (string) - Current page context

#### `wp_schema_framework_website_can_provide`
Control whether WebSite schema should be output.

**Parameters:**
- `$can_provide` (bool) - Whether to output (default: true)
- `$context` (string) - Current page context

#### `wp_schema_framework_article_data`
Modify article schema data.

**Parameters:**
- `$data` (array) - Article schema array
- `$post_id` (int) - Post ID (0 if not applicable)
- `$post` (WP_Post|null) - Post object

#### `wp_schema_framework_webpage_data`
Modify webpage schema data.

**Parameters:**
- `$data` (array) - WebPage schema array
- `$post_id` (int) - Post ID
- `$post` (WP_Post) - Post object

#### `wp_schema_framework_author_data`
Modify author/person schema data.

**Parameters:**
- `$data` (array) - Person schema array
- `$author_id` (int) - Author user ID
- `$context` (string) - Current page context

#### `wp_schema_framework_archive_data`
Modify archive page schema data.

**Parameters:**
- `$data` (array) - Archive schema array
- `$context` (string) - Current page context

#### `wp_schema_framework_search_results_data`
Modify search results page schema data.

**Parameters:**
- `$data` (array) - Search results schema array
- `$context` (string) - Current page context
- `$search_query` (string) - Search query string

#### `wp_schema_framework_media_data`
Modify media attachment schema data.

**Parameters:**
- `$data` (array) - Media schema array
- `$context` (string) - Current page context
- `$attachment_id` (int) - Attachment ID

#### `wp_schema_framework_page_type_data`
Modify specialized page type schema data.

**Parameters:**
- `$data` (array) - Page type schema array
- `$context` (string) - Current page context
- `$schema_type` (string) - Detected schema type

### Product Schema Filters

#### `wp_schema_framework_product_data`
Modify product schema data.

**Parameters:**
- `$data` (array) - Product schema array
- `$context` (string) - Current page context
- `$post_id` (int) - Product post ID

#### `wp_schema_framework_is_product`
Determine if a post should be treated as a product.

**Parameters:**
- `$is_product` (bool) - Whether it's a product
- `$post_id` (int) - Post ID
- `$context` (string) - Current page context

#### `wp_schema_framework_get_product_data`
Provide custom product data.

**Parameters:**
- `$data` (array|null) - Product data array or null
- `$post_id` (int) - Product post ID

#### `wp_schema_framework_woocommerce_product_data`
Modify WooCommerce product data.

**Parameters:**
- `$data` (array) - Product data array
- `$product` (WC_Product) - WooCommerce product object

#### `wp_schema_framework_woocommerce_schema_active`
Control whether WooCommerce's own schema is considered active.

**Parameters:**
- `$active` (bool) - Whether WooCommerce schema is active

#### `wp_schema_framework_edd_product_data`
Modify Easy Digital Downloads product data.

**Parameters:**
- `$data` (array) - Product data array
- `$download` (EDD_Download) - EDD download object

#### `wp_schema_framework_bigcommerce_product_data`
Modify BigCommerce product data.

**Parameters:**
- `$data` (array) - Product data array
- `$product_id` (int) - Product post ID

### Event Schema Filters

#### `wp_schema_framework_event_data`
Modify event schema data.

**Parameters:**
- `$data` (array) - Event schema array
- `$context` (string) - Current page context
- `$post_id` (int) - Event post ID

#### `wp_schema_framework_is_event`
Determine if a post should be treated as an event.

**Parameters:**
- `$is_event` (bool) - Whether it's an event
- `$post_id` (int) - Post ID
- `$context` (string) - Current page context

#### `wp_schema_framework_get_event_data`
Provide custom event data.

**Parameters:**
- `$data` (array|null) - Event data array or null
- `$post_id` (int) - Event post ID

#### `wp_schema_framework_tribe_events_data`
Modify The Events Calendar event data.

**Parameters:**
- `$data` (array) - Event data array
- `$event` (WP_Post) - Event post object

#### `wp_schema_framework_tribe_events_schema_active`
Control whether The Events Calendar's schema is considered active.

**Parameters:**
- `$active` (bool) - Whether Tribe Events schema is active

#### `wp_schema_framework_events_manager_data`
Modify Events Manager event data.

**Parameters:**
- `$data` (array) - Event data array
- `$em_event` (EM_Event) - Events Manager event object

#### `wp_schema_framework_mec_data`
Modify Modern Events Calendar event data.

**Parameters:**
- `$data` (array) - Event data array
- `$event` (array) - MEC event array

#### `wp_schema_framework_event_organiser_data`
Modify Event Organiser event data.

**Parameters:**
- `$data` (array) - Event data array
- `$event_id` (int) - Event post ID

### Post Type Mapping Filters

#### `wp_schema_framework_post_type_override`
Override the schema type for a specific post.

**Parameters:**
- `$schema_type` (string) - Schema type to use
- `$post_id` (int) - Post ID
- `$post_type` (string) - WordPress post type
- `$post` (WP_Post) - Post object

**Usage:**
```php
add_filter('wp_schema_framework_post_type_override', function($type, $post_id, $post_type, $post) {
    if (get_post_meta($post_id, 'is_how_to', true)) {
        return 'HowTo';
    }
    return $type;
}, 10, 4);
```

#### `wp_schema_framework_post_type_mapping`
Map WordPress post types to schema types.

**Parameters:**
- `$schema_type` (string) - Default schema type
- `$post_type` (string) - WordPress post type

#### `wp_schema_framework_post_description`
Provide custom description for posts.

**Parameters:**
- `$description` (string) - Post description
- `$post_id` (int) - Post ID
- `$post` (WP_Post) - Post object

#### `wp_schema_framework_homepage_type`
Override schema type for the homepage.

**Parameters:**
- `$type` (string) - Schema type (default: 'WebSite')

#### `wp_schema_framework_homepage_data`
Modify homepage schema data.

**Parameters:**
- `$data` (array) - Homepage schema array

### Archive & Search Filters

#### `wp_schema_framework_archive_item_type`
Override schema type for archive items.

**Parameters:**
- `$type` (string) - Schema type
- `$post_type` (string) - WordPress post type

#### `wp_schema_framework_search_item_type`
Override schema type for search result items.

**Parameters:**
- `$type` (string) - Schema type
- `$post_type` (string) - WordPress post type

### Specialized Page Type Filters

#### `wp_schema_framework_faq_items`
Provide FAQ items for FAQPage schema.

**Parameters:**
- `$items` (array) - Array of FAQ items
- `$post_id` (int) - Post ID

**Usage:**
```php
add_filter('wp_schema_framework_faq_items', function($items, $post_id) {
    return [
        ['question' => 'Q1?', 'answer' => 'A1'],
        ['question' => 'Q2?', 'answer' => 'A2'],
    ];
}, 10, 2);
```

#### `wp_schema_framework_collection_items`
Provide items for CollectionPage schema.

**Parameters:**
- `$items` (array) - Array of collection items
- `$post_id` (int) - Post ID

#### `wp_schema_framework_gallery_items`
Provide images for ImageGallery schema.

**Parameters:**
- `$items` (array) - Array of image URLs
- `$post_id` (int) - Post ID

#### `wp_schema_framework_gallery_image_count`
Provide image count for galleries.

**Parameters:**
- `$count` (int) - Number of images
- `$post_id` (int) - Post ID

### Type Registry Filters

#### `wp_schema_framework_available_types`
Modify available schema types for UI.

**Parameters:**
- `$types` (array) - Array of type definitions

#### `wp_schema_framework_type_registry_types`
Modify the complete type registry.

**Parameters:**
- `$types` (array) - All registered schema types

#### `wp_schema_framework_post_type_mappings`
Modify default post type to schema type mappings.

**Parameters:**
- `$mappings` (array) - Associative array of post_type => schema_type

### Generic Schema Filters

#### `wp_schema_framework_generic_data`
Modify generic schema data.

**Parameters:**
- `$data` (array) - Generic schema array
- `$post_id` (int) - Post ID (0 if not applicable)
- `$post` (WP_Post|null) - Post object

#### `wp_schema_framework_{lowercase_type}_data`
Dynamic filter for any schema type (lowercase).

**Parameters:**
- `$data` (array) - Schema data array
- `$post_id` (int) - Post ID
- `$post` (WP_Post|null) - Post object

**Example types:** `howto_data`, `recipe_data`, `qapage_data`, etc.

## Best Practices

1. **Always return the filtered value** - Don't forget to return the modified data
2. **Check context** - Use the context parameter to apply filters selectively
3. **Use proper priority** - Lower numbers run first (default is 10)
4. **Validate data** - Ensure your modifications follow schema.org specifications
5. **Test thoroughly** - Use Google's Rich Results Test to validate output

## Common Use Cases

### Adding Custom Properties
```php
add_filter('wp_schema_framework_article_data', function($data, $post_id, $post) {
    // Add custom property
    $data['customProperty'] = get_post_meta($post_id, 'custom_field', true);
    return $data;
}, 10, 3);
```

### Conditional Schema Output
```php
add_filter('wp_schema_framework_output_enabled', function($enabled) {
    // Disable on certain pages
    if (is_page(['privacy-policy', 'terms'])) {
        return false;
    }
    return $enabled;
});
```

### Override Post Schema Type
```php
add_filter('wp_schema_framework_post_type_override', function($type, $post_id, $post_type, $post) {
    // Use HowTo for posts in 'tutorials' category
    if (has_category('tutorials', $post_id)) {
        return 'HowTo';
    }
    return $type;
}, 10, 4);
```

### Add Organization Contact Info
```php
add_filter('wp_schema_framework_organization_data', function($data) {
    $data['telephone'] = '+1-555-123-4567';
    $data['email'] = 'info@example.com';
    $data['contactPoint'] = [
        '@type' => 'ContactPoint',
        'telephone' => '+1-555-123-4567',
        'contactType' => 'customer service'
    ];
    return $data;
});
```
