# Conflict Detection in WP Schema Framework

## Overview

The WP Schema Framework includes intelligent conflict detection to prevent duplicate schema markup when popular plugins are already outputting their own structured data.

## Supported Plugins

### E-commerce
- **WooCommerce**: Automatically detects when WooCommerce is outputting Product schema and disables our Product provider to prevent duplicates.

### Events
- **The Events Calendar**: Automatically detects when The Events Calendar is outputting Event schema and disables our Event provider to prevent duplicates.

## How It Works

1. **Automatic Detection**: When a supported plugin is active and outputting schema, our providers automatically disable themselves.
2. **No Configuration Needed**: Works out of the box with zero configuration.
3. **Developer Override**: Can be overridden via filters if you want to use our schema instead.

## Developer Filters

### Force WP Schema to Output Product Schema

```php
// Force our Product schema even when WooCommerce is active
add_filter('wp_schema_framework_woocommerce_schema_active', '__return_false');
```

### Force WP Schema to Output Event Schema

```php
// Force our Event schema even when The Events Calendar is active
add_filter('wp_schema_framework_tribe_events_schema_active', '__return_false');
```

### Custom Product Detection

```php
// Force a post to be treated as a product
add_filter('wp_schema_framework_is_product', function($is_product, $post_id, $context) {
    if (get_post_type($post_id) === 'my_custom_product') {
        return true;
    }
    return $is_product;
}, 10, 3);
```

### Custom Event Detection

```php
// Force a post to be treated as an event
add_filter('wp_schema_framework_is_event', function($is_event, $post_id, $context) {
    if (get_post_type($post_id) === 'my_custom_event') {
        return true;
    }
    return $is_event;
}, 10, 3);
```

## Testing Conflict Detection

To verify conflict detection is working:

1. Install WooCommerce or The Events Calendar
2. View a product or event page source
3. Search for JSON-LD script tags
4. Verify only one Product/Event schema appears

## Adding Conflict Detection for Other Plugins

If you're developing a provider that might conflict with existing plugins:

```php
private function is_plugin_schema_enabled(): bool
{
    // Check if plugin is disabling its schema via filter
    if (apply_filters('plugin_disable_schema', false)) {
        return false;
    }
    
    // Check if plugin's schema class exists and is active
    if (class_exists('Plugin_Schema_Class')) {
        // Allow override via our filter
        return apply_filters('wp_schema_framework_plugin_schema_active', true);
    }
    
    return false;
}

public function can_provide(string $context): bool
{
    // ... other checks ...
    
    if ($this->is_plugin_schema_enabled()) {
        return false; // Let the plugin handle it
    }
    
    // ... continue with provision
}
```

## Benefits

1. **No Duplicate Schema**: Prevents Google Search Console errors about duplicate structured data
2. **Better SEO**: Clean, single source of truth for search engines
3. **Plugin Compatibility**: Works seamlessly with popular WordPress plugins
4. **Developer Friendly**: Easy to override when custom behavior is needed