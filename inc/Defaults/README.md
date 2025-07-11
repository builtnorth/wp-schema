# Default Integrations

The `Defaults` directory contains pre-built integrations for popular WordPress plugins and blocks. These integrations automatically provide schema markup for common use cases without requiring custom code.

## Overview

Default integrations are automatically initialized when the schema generator starts up. Each integration:

1. **Checks availability** - Only loads if the required plugin/functionality is present
2. **Registers hooks** - Connects to the schema generation system via WordPress hooks
3. **Provides data** - Automatically generates appropriate schema data for compatible schema types
4. **Can be disabled** - Each integration can be individually enabled/disabled

**Note:** Schema types are determined by post type, not by individual blocks. Blocks only contribute data to the schema.

## Available Integrations

### WooCommerce Integration

- **File**: `WooCommerceIntegration.php`
- **Provides**: Product, Review, and Organization schema
- **Requires**: WooCommerce plugin
- **Features**:
    - Automatic Product schema for WooCommerce products
    - Price, availability, and rating information
    - Organization schema for shop pages
    - Support for WooCommerce blocks

### WordPress Core Integration

- **File**: `WordPressCoreIntegration.php`
- **Provides**: Schema for core post types and blocks
- **Requires**: WordPress core
- **Features**:
    - Article schema for blog posts
    - WebPage schema for pages
    - ImageObject schema for attachments
    - Support for core Gutenberg blocks

### Core Blocks Integration

- **File**: `CoreBlocksIntegration.php`
- **Provides**: Schema data for WordPress core Gutenberg blocks
- **Requires**: WordPress core
- **Features**:
    - Text content for Article/WebPage schema types
    - Image data for ImageObject/ImageGallery schema types
    - Video data for VideoObject schema types
    - Audio data for AudioObject schema types

### Easy Digital Downloads Integration

- **File**: `EasyDigitalDownloadsIntegration.php`
- **Provides**: Product schema for digital downloads
- **Requires**: Easy Digital Downloads plugin
- **Status**: Placeholder (ready for implementation)

### The Events Calendar Integration

- **File**: `EventsCalendarIntegration.php`
- **Provides**: Event schema for calendar events
- **Requires**: The Events Calendar plugin
- **Status**: Placeholder (ready for implementation)

### WP Recipe Maker Integration

- **File**: `WPRecipeMakerIntegration.php`
- **Provides**: Recipe schema for cooking recipes
- **Requires**: WP Recipe Maker plugin
- **Status**: Placeholder (ready for implementation)

### Advanced Custom Fields Integration

- **File**: `ACFIntegration.php`
- **Provides**: Schema from ACF custom fields
- **Requires**: Advanced Custom Fields plugin
- **Status**: Placeholder (ready for implementation)

### Custom Post Type UI Integration

- **File**: `CPTUIIntegration.php`
- **Provides**: Schema for custom post types
- **Requires**: Custom Post Type UI plugin
- **Status**: Placeholder (ready for implementation)

### Polaris Blocks Integration

- **File**: `PolarisBlocksIntegration.php`
- **Provides**: Schema data for Polaris Blocks plugin blocks
- **Requires**: Polaris Blocks plugin
- **Features**:
    - FAQ data for FAQPage/WebPage/Article schema types
    - Place data for Place/LocalBusiness/Organization schema types
    - Contact data for ContactPoint/Organization/LocalBusiness schema types
    - Social media data for Organization/Person/LocalBusiness schema types
    - Business hours data for OpeningHoursSpecification/LocalBusiness/Organization schema types
    - Breadcrumb data for BreadcrumbList/WebPage/Article schema types
    - Offer data for Offer/Product/Service schema types
    - List data for ItemList/WebPage/Article schema types
    - Image data for ImageGallery/WebPage/Article schema types
    - Logo data for ImageObject/Organization/LocalBusiness schema types

## Managing Integrations

### Check Available Integrations

```php
$integrations = \BuiltNorth\Schema\Defaults\DefaultIntegrations::get_available_integrations();

foreach ($integrations as $key => $integration) {
    echo "Integration: {$integration['name']}\n";
    echo "Available: " . ($integration['available'] ? 'Yes' : 'No') . "\n";
    echo "Description: {$integration['description']}\n\n";
}
```

### Enable/Disable Integrations

```php
// Disable WooCommerce integration
\BuiltNorth\Schema\Defaults\DefaultIntegrations::toggle_integration('woocommerce', false);

// Enable WooCommerce integration
\BuiltNorth\Schema\Defaults\DefaultIntegrations::toggle_integration('woocommerce', true);

// Check if integration is enabled
$enabled = \BuiltNorth\Schema\Defaults\DefaultIntegrations::is_integration_enabled('woocommerce');
```

### Integration Methods

Each integration class provides these methods:

- `is_available()` - Check if the required plugin/functionality is present
- `get_description()` - Get human-readable description
- `get_supported_schema_types()` - Get array of supported schema types
- `enable()` - Enable this integration
- `disable()` - Disable this integration
- `is_enabled()` - Check if integration is enabled

## Creating Custom Integrations

To create a new integration:

1. **Extend BaseIntegration**:

```php
class MyCustomIntegration extends BaseIntegration
{
    protected static $integration_name = 'my_custom_integration';

    protected static function register_hooks()
    {
        // Register your hooks here
        add_filter('wp_schema_type_for_post', [self::class, 'override_schema_type'], 10, 3);
        add_filter('wp_schema_data_for_post', [self::class, 'provide_data'], 10, 4);
    }

    // Implement required methods...
}
```

2. **Add to DefaultIntegrations**:

```php
// In DefaultIntegrations::init()
if (class_exists('MyCustomPlugin')) {
    MyCustomIntegration::init();
}
```

3. **Register in get_available_integrations()**:

```php
// In DefaultIntegrations::get_available_integrations()
'my_custom_integration' => [
    'name' => 'My Custom Plugin',
    'available' => class_exists('MyCustomPlugin'),
    'description' => 'Schema for My Custom Plugin'
]
```

## Integration Architecture

### BaseIntegration Class

Provides common functionality for all integrations:

- Automatic enable/disable checking
- Hook registration management
- Integration status tracking

### DefaultIntegrations Manager

Manages all integrations:

- Initializes available integrations
- Provides integration status information
- Handles enable/disable functionality

### Hook System

Integrations connect to the schema generation system via WordPress hooks:

- `wp_schema_type_for_post` - Override schema type for posts (post type determines schema type)
- `wp_schema_data_for_post` - Provide custom data for posts
- `wp_schema_data_for_block` - Provide custom data for blocks (blocks contribute data, not schema type)
- `wp_schema_final_schema` - Modify final schema output

## Benefits

1. **Zero Configuration** - Works out of the box for popular plugins
2. **Automatic Detection** - Only loads when needed plugins are present
3. **Flexible** - Can be disabled or customized as needed
4. **Extensible** - Easy to add new integrations
5. **Performance** - Lightweight, only loads what's needed
6. **Maintainable** - Each integration is self-contained

## Future Integrations

Planned integrations for future releases:

- Yoast SEO integration
- RankMath integration
- Elementor integration
- Beaver Builder integration
- Divi integration
- Contact Form 7 integration
- Gravity Forms integration
- Ninja Forms integration
