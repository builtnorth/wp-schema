# WP Schema

A comprehensive schema generation package for WordPress with SEO optimization, logo support, and intelligent content detection.

## Features

- **SEO-Focused**: Generates JSON-LD structured data for search engines
- **Logo Support**: Comprehensive logo handling with multiple formats
- **Hook-Based**: Flexible schema generation through WordPress hooks
- **Block Support**: Generate schema from individual Gutenberg blocks
- **Business Schemas**: Specialized for local business and organization data
- **WordPress Integration**: Seamless integration with WordPress core

## Installation

```bash
composer require builtnorth/wp-schema
```

## Quick Start

```php
use BuiltNorth\Schema\SchemaGenerator;

// Generate organization schema with logo
$org_data = [
    'name' => 'Acme Corporation',
    'logo' => 'https://example.com/logo.png',
    'telephone' => '+1-555-123-4567',
    'address' => [
        'streetAddress' => '123 Main St',
        'addressLocality' => 'New York',
        'addressRegion' => 'NY'
    ]
];

$schema = SchemaGenerator::render($org_data, 'organization');
echo '<script type="application/ld+json">' . $schema . '</script>';
```

## Schema Types

### Organization Schema

Comprehensive organization markup with logo support, contact info, and business details.

### LocalBusiness Schema

Specialized for local businesses with business-specific fields:

- Restaurant, Store, Automotive, Beauty, Medical, etc.
- Location and contact information
- Business hours and services
- Payment methods and accessibility

### WebSite Schema

Website markup with search functionality and navigation support.

### Article Schema

Article markup for blog posts and content pages.

### Product Schema

Product markup for e-commerce and service offerings.

### FAQ Schema

FAQ markup with automatic question/answer detection.

### Person Schema

Person markup for team members and authors.

## Logo Support

### Multiple Formats

```php
// Simple URL
'logo' => 'https://example.com/logo.png'

// Structured object
'logo' => [
    'url' => 'https://example.com/logo.png',
    'width' => 300,
    'height' => 100,
    'alt' => 'Company Logo'
]

// Multiple formats
'logos' => [
    ['url' => 'https://example.com/logo.png'],
    ['url' => 'https://example.com/logo-dark.png']
]

// WordPress customizer integration
// Automatically detects logo from WordPress customizer
```

## Usage Examples

### Local Business

```php
$restaurant_data = [
    'name' => 'The Grand Restaurant',
    'category' => 'restaurant',
    'logo' => 'https://example.com/restaurant-logo.png',
    'address' => [
        'streetAddress' => '123 Main Street',
        'addressLocality' => 'New York',
        'addressRegion' => 'NY',
        'postalCode' => '10001'
    ],
    'telephone' => '+1-555-123-4567',
    'business_hours' => [
        'Monday' => '9:00 AM - 10:00 PM',
        'Tuesday' => '9:00 AM - 10:00 PM'
    ],
    'servesCuisine' => 'Italian',
    'acceptsReservations' => true
];

$schema = SchemaGenerator::render($restaurant_data, 'local_business');
```

### Website with Search

```php
$website_data = [
    'name' => 'My Website',
    'description' => 'A comprehensive website about technology',
    'logo' => 'https://example.com/website-logo.png',
    'search_enabled' => true,
    'social_media' => [
        'facebook' => 'https://facebook.com/mywebsite',
        'twitter' => 'https://twitter.com/mywebsite'
    ]
];

$schema = SchemaGenerator::render($website_data, 'website');
```

### WordPress Integration

```php
// Generate schema from WordPress post
$article_schema = SchemaGenerator::render(get_the_ID(), 'article');

// Generate organization schema with WordPress logo
$org_schema = SchemaGenerator::render([
    'name' => get_bloginfo('name'),
    'description' => get_bloginfo('description')
], 'organization');
```

## Content Sources

### HTML Content

```php
$html = '<div class="faq-item"><h3>Question?</h3><p>Answer.</p></div>';
$schema = SchemaGenerator::render($html, 'faq');
```

### JSON Data

```php
$json = '{"questions": ["Q1", "Q2"], "answers": ["A1", "A2"]}';
$schema = SchemaGenerator::render($json, 'faq');
```

### Array Data

```php
$data = [
    'name' => 'Company Name',
    'logo' => 'https://example.com/logo.png',
    'description' => 'Company description'
];
$schema = SchemaGenerator::render($data, 'organization');
```

### WordPress Post

```php
$schema = SchemaGenerator::render(get_the_ID(), 'article');
```

## Automatic Integrations

The system includes pre-built integrations for popular WordPress plugins and blocks that work out of the box:

### Available Integrations

- **WooCommerce** - Automatic Product, Review, and Organization schema
- **WordPress Core** - Article, WebPage, and ImageObject schema for core content
- **Core Blocks** - Schema data for Gutenberg blocks (images, videos, etc.)

- **Easy Digital Downloads** - Product schema for digital downloads
- **The Events Calendar** - Event schema for calendar events
- **WP Recipe Maker** - Recipe schema for cooking recipes
- **Advanced Custom Fields** - Schema from ACF custom fields
- **Custom Post Type UI** - Schema for custom post types
- **Polaris Blocks** - Schema data for Polaris Blocks plugin (accordion, map, contact info, social media, etc.)

### Managing Integrations

```php
// Check available integrations
$integrations = \BuiltNorth\Schema\Defaults\DefaultIntegrations::get_available_integrations();

// Disable an integration
\BuiltNorth\Schema\Defaults\DefaultIntegrations::toggle_integration('woocommerce', false);

// Check if integration is enabled
$enabled = \BuiltNorth\Schema\Defaults\DefaultIntegrations::is_integration_enabled('woocommerce');
```

See [Defaults/README.md](inc/Defaults/README.md) for detailed integration documentation.

## Hook-Based Generation

The package uses WordPress hooks for flexible schema generation:

- **Post-Level Hooks**: Override schema types and provide custom data for posts
- **Block-Level Hooks**: Provide schema data from individual Gutenberg blocks (schema type determined by post type)
- **Content Hooks**: Modify content and data before schema generation
- **Schema Hooks**: Override final schema output

## Extending

### Hook-Based Extension (Recommended)

The package now supports a comprehensive hook system that allows plugins and themes to:

- Override schema types for posts, blocks, or specific content
- Provide custom data for schema generation
- Modify detected patterns and extracted data
- Handle custom schema types not built into the system
- Control block-level schema generation
- Collect schemas from multiple sources within a single post

See [HOOKS.md](HOOKS.md) for complete documentation and examples.

### Adding New Schema Types

1. Create a new generator in `Generators/`
2. Extend `BaseGenerator` class
3. Implement required methods
4. Register in `SchemaGenerator.php`

### Adding Custom Data Sources

Use the hook system to provide data for custom content types:

```php
// Provide data for custom post type
add_filter('wp_schema_data_for_post', function($custom_data, $post_id, $schema_type, $options) {
    if ($schema_type === 'MyCustomType') {
        return [
            'name' => get_the_title($post_id),
            'customField' => get_post_meta($post_id, '_custom_field', true)
        ];
    }
    return null;
}, 10, 4);
```

## Requirements

- PHP 8.1+
- WordPress 6.0+

## License

GPL-2.0-or-later

## Contributing

Please read our contributing guidelines and submit pull requests for any improvements.

## Support

For support and questions, please open an issue on GitHub.
