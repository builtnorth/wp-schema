# WP Schema

A comprehensive schema generation package for WordPress with SEO optimization, logo support, and intelligent content detection.

## Features

- **SEO-Focused**: Generates JSON-LD structured data for search engines
- **Logo Support**: Comprehensive logo handling with multiple formats
- **Content-Agnostic**: Works with HTML, JSON, arrays, post meta, and mixed data
- **Auto-Detection**: Intelligently detects content types and patterns
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

## Pattern Recognition

The package automatically detects patterns in content:

- **Logo Patterns**: `<img class="logo">`, `<img alt="logo">`, meta tags
- **Business Patterns**: Contact info, addresses, business hours
- **Content Patterns**: FAQ structures, article content, product information

## Extending

### Adding New Schema Types

1. Create a new generator in `Generators/`
2. Extend `BaseGenerator` class
3. Implement required methods
4. Register in `SchemaGenerator.php`

### Adding New Extractors

1. Create a new extractor in `Extractors/`
2. Implement extraction logic
3. Register in `SchemaGenerator.php`

## Requirements

- PHP 8.1+
- WordPress 6.0+

## License

GPL-2.0-or-later

## Contributing

Please read our contributing guidelines and submit pull requests for any improvements.

## Support

For support and questions, please open an issue on GitHub.
