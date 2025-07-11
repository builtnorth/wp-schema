# SchemaGenerator Utility

A universal, content-agnostic structured data generator for WordPress sites. Automatically detects content types and patterns to generate JSON-LD schema markup for SEO optimization.

## Overview

The SchemaGenerator utility provides a flexible, pattern-based approach to generating structured data from various content sources. It works across different WordPress setups, themes, and content types without requiring specific HTML structures. Now with **comprehensive logo support** and **enhanced schema types** for better SEO coverage.

## Features

- **Content-Agnostic**: Works with HTML, JSON, arrays, post meta, and mixed data
- **Auto-Detection**: Automatically detects content types and patterns
- **Pattern Recognition**: Finds FAQ, article, product, organization, and business patterns in any HTML structure
- **Multiple Schema Types**: Supports FAQ, Article, Product, Organization, Person, LocalBusiness, and WebSite schemas
- **Comprehensive Logo Support**: Handles logos in multiple formats (URL, object, array, WordPress customizer)
- **Universal**: Works with Gutenberg blocks, traditional themes, and custom HTML
- **Extensible**: Easy to add new schema types and extraction patterns

## Architecture

```
SchemaGenerator/
├── SchemaGenerator.php          # Main orchestrator class
├── Extractors/                 # Content extraction logic
│   ├── ContentExtractor.php    # HTML content extraction with enhanced logo support
│   ├── JsonExtractor.php       # JSON data extraction
│   ├── PostMetaExtractor.php   # WordPress post extraction
│   └── ArrayExtractor.php      # Array data extraction
├── Generators/                 # Schema generation logic
│   ├── FaqGenerator.php        # FAQ schema generation
│   ├── ArticleGenerator.php    # Article schema generation
│   ├── ProductGenerator.php    # Product schema generation
│   ├── OrganizationGenerator.php # Organization schema with comprehensive logo support
│   ├── PersonGenerator.php     # Person schema generation
│   ├── LocalBusinessGenerator.php # LocalBusiness schema with business-specific fields
│   ├── WebSiteGenerator.php    # WebSite schema with search functionality
│   └── BaseGenerator.php       # Abstract base class
├── Detectors/                  # Auto-detection logic
│   ├── ContentTypeDetector.php # Auto-detect content type
│   └── PatternDetector.php     # Auto-detect patterns
└── README.md                   # This documentation
```

## Usage

### Basic Usage

```php
use BuiltNorth\Utility\Utility;

// Generate Organization schema with logo support
$schema = Utility::schema_generator($organization_data, 'organization');

// Generate LocalBusiness schema with comprehensive business info
$schema = Utility::schema_generator($business_data, 'local_business');

// Generate WebSite schema with search functionality
$schema = Utility::schema_generator($website_data, 'website');
```

### Logo Support Examples

#### Simple Logo URL

```php
$data = [
    'name' => 'Acme Corporation',
    'logo' => 'https://example.com/logo.png'
];
$schema = Utility::schema_generator($data, 'organization');
```

#### Structured Logo Object

```php
$data = [
    'name' => 'Acme Corporation',
    'logo' => [
        'url' => 'https://example.com/logo.png',
        'width' => 300,
        'height' => 100,
        'alt' => 'Acme Corporation Logo'
    ]
];
$schema = Utility::schema_generator($data, 'organization');
```

#### Multiple Logo Formats

```php
$data = [
    'name' => 'Acme Corporation',
    'logos' => [
        ['url' => 'https://example.com/logo.png', 'width' => 300, 'height' => 100],
        ['url' => 'https://example.com/logo-dark.png', 'width' => 300, 'height' => 100]
    ]
];
$schema = Utility::schema_generator($data, 'organization');
```

#### WordPress Customizer Logo

```php
// Automatically detects logo from WordPress customizer
$data = ['name' => 'Acme Corporation'];
$schema = Utility::schema_generator($data, 'organization');
```

### LocalBusiness Schema Examples

#### Restaurant

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
    'acceptsReservations' => true,
    'hasMenu' => 'https://example.com/menu'
];
$schema = Utility::schema_generator($restaurant_data, 'local_business');
```

#### Retail Store

```php
$store_data = [
    'name' => 'Acme Electronics',
    'category' => 'store',
    'logo' => 'https://example.com/store-logo.png',
    'address' => [
        'streetAddress' => '456 Commerce Ave',
        'addressLocality' => 'Los Angeles',
        'addressRegion' => 'CA',
        'postalCode' => '90210'
    ],
    'telephone' => '+1-555-987-6543',
    'paymentAccepted' => ['Cash', 'Credit Card', 'PayPal'],
    'currenciesAccepted' => 'USD'
];
$schema = Utility::schema_generator($store_data, 'local_business');
```

### WebSite Schema Examples

#### Basic Website

```php
$website_data = [
    'name' => 'My Website',
    'description' => 'A comprehensive website about technology',
    'logo' => 'https://example.com/website-logo.png',
    'social_media' => [
        'facebook' => 'https://facebook.com/mywebsite',
        'twitter' => 'https://twitter.com/mywebsite'
    ]
];
$schema = Utility::schema_generator($website_data, 'website');
```

#### Website with Search

```php
$website_data = [
    'name' => 'My Website',
    'search_enabled' => true,
    'search_url' => 'https://example.com/search?q={search_term_string}',
    'logo' => 'https://example.com/website-logo.png'
];
$schema = Utility::schema_generator($website_data, 'website');
```

## Schema Types

### Organization Schema

Enhanced organization markup with comprehensive logo support:

- **Logo Support**: Multiple formats (URL, object, array, WordPress customizer)
- **Contact Information**: Phone, email, contact points
- **Address**: Structured postal address
- **Social Media**: Multiple platform support
- **Business Details**: Founding date, employee count, specialties
- **Subsidiaries**: Parent/child organization relationships

### LocalBusiness Schema

Comprehensive local business markup with business-specific fields:

- **Business Types**: Restaurant, Store, Automotive, Beauty, Medical, etc.
- **Location**: Address and geo coordinates
- **Contact**: Phone, email, contact points
- **Hours**: Business hours and availability
- **Services**: Business-specific offerings
- **Payment**: Accepted payment methods
- **Accessibility**: Wheelchair access, etc.

### WebSite Schema

Website markup with search and navigation support:

- **Search Functionality**: Automatic search action generation
- **Navigation**: Site navigation and breadcrumbs
- **Publisher**: Organization or person information
- **Social Media**: Multiple platform links
- **Language**: Site language detection
- **Breadcrumbs**: Automatic breadcrumb generation

### Enhanced Logo Support

The schema system now supports logos in multiple formats:

1. **Simple URL**: `'logo' => 'https://example.com/logo.png'`
2. **Structured Object**:
    ```php
    'logo' => [
        'url' => 'https://example.com/logo.png',
        'width' => 300,
        'height' => 100,
        'alt' => 'Company Logo'
    ]
    ```
3. **Multiple Formats**: Array of logo objects for different contexts
4. **WordPress Integration**: Automatic detection from customizer
5. **Meta Tags**: Support for Open Graph and other meta logo tags

## Pattern Recognition

### Enhanced Logo Patterns

The utility now recognizes logos in various HTML structures:

- `<img class="logo">` elements
- `<img alt="logo">` elements
- `<img src="*logo*">` elements
- `<div class="logo">` containers
- `<header>` logo elements
- Open Graph meta tags
- Custom logo meta tags

### LocalBusiness Patterns

- **Business Information**: Company names, categories, types
- **Contact Information**: Phone numbers, email addresses
- **Location Data**: Addresses, coordinates, maps
- **Business Hours**: Opening times, schedules
- **Restaurant Data**: Menus, cuisine types, reservations
- **Retail Data**: Payment methods, product catalogs

### WebSite Patterns

- **Website Information**: Site names, descriptions
- **Search Functionality**: Search forms, query parameters
- **Navigation**: Menus, breadcrumbs, site structure
- **Social Media**: Platform links, sharing buttons
- **Publisher Data**: Organization or author information

## Auto-Detection

### Enhanced Content Type Detection

- **Array**: Detects PHP arrays
- **JSON**: Detects valid JSON strings
- **Post ID**: Detects numeric post IDs
- **HTML**: Detects HTML content (default)
- **Logo Detection**: Automatically finds logos in content

### Enhanced Pattern Detection

- **Organization**: Detects company info, contact, logo patterns
- **LocalBusiness**: Detects business info, location, hours patterns
- **WebSite**: Detects site info, search, navigation patterns
- **Logo**: Detects various logo formats and structures

## Examples

### Gutenberg Block Usage

```php
// In organization block render.php
if (!empty($attributes['organizationSchema']) && $attributes['organizationSchema']) {
    $org_data = [
        'name' => $attributes['companyName'],
        'logo' => $attributes['logo'],
        'description' => $attributes['description'],
        'telephone' => $attributes['phone'],
        'email' => $attributes['email']
    ];
    $org_schema = Utility::schema_generator($org_data, 'organization');
    if (!empty($org_schema)) {
        echo '<script type="application/ld+json">' . $org_schema . '</script>';
    }
}
```

### Traditional Theme Usage

```php
// In header.php for website schema
$website_schema = Utility::schema_generator([
    'name' => get_bloginfo('name'),
    'description' => get_bloginfo('description'),
    'search_enabled' => true
], 'website');
SchemaGenerator::output_schema_script($website_schema);

// In single.php for article schema
$article_schema = Utility::schema_generator(get_the_ID(), 'article');
SchemaGenerator::output_schema_script($article_schema);
```

### Local Business Page

```php
// Any business page
$business_data = [
    'name' => 'Local Business Name',
    'category' => 'restaurant',
    'logo' => get_theme_mod('custom_logo'),
    'address' => [
        'streetAddress' => '123 Business St',
        'addressLocality' => 'City',
        'addressRegion' => 'State',
        'postalCode' => '12345'
    ],
    'telephone' => '+1-555-123-4567',
    'business_hours' => [
        'Monday' => '9:00 AM - 5:00 PM',
        'Tuesday' => '9:00 AM - 5:00 PM'
    ]
];
$schema = Utility::schema_generator($business_data, 'local_business');
```

## Extending

### Adding New Schema Types

1. Create a new generator in `Generators/`
2. Extend `BaseGenerator` class
3. Implement required methods
4. Register in `SchemaGenerator.php`
5. Add extraction logic to `ContentExtractor.php`
6. Add pattern detection to `PatternDetector.php`

### Adding New Logo Formats

1. Update `process_logo()` method in generators
2. Add logo extraction patterns to `ContentExtractor.php`
3. Add logo detection patterns to `PatternDetector.php`

### Adding New Business Types

1. Update `determine_business_type()` in `LocalBusinessGenerator.php`
2. Add business-specific fields in `add_business_specific_fields()`
3. Add extraction patterns to `ContentExtractor.php`

## Benefits

1. **Universal**: Works with any WordPress setup
2. **Smart**: Auto-detects content types and patterns
3. **Flexible**: Supports multiple data sources
4. **Comprehensive**: Enhanced logo support and new schema types
5. **Extensible**: Easy to add new schema types
6. **Simple**: One-line usage for most cases
7. **Maintainable**: Modular, testable architecture
8. **SEO-Friendly**: Generates proper JSON-LD markup with comprehensive coverage

## Requirements

- PHP 8.1+
- WordPress 6.0+
- BuiltNorth\Utility package

## License

GPL-2.0-or-later
