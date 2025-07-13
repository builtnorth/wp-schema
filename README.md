# WP Schema

A simple, WordPress-first schema generation framework inspired by Yoast's approach with provider-based architecture.

## Architecture

WP Schema follows a clean, modular architecture:

- **Core Framework**: Provider registration, schema assembly, and output management
- **Provider System**: Hook-based registration for extensible schema generation
- **Yoast-Style References**: Clean schema graphs with @id references and deduplication
- **WordPress Integration**: Seamless integration with WordPress core data

## Features

- **Simple Provider Interface**: Easy to implement schema providers
- **Registration Order Priority**: Predictable schema ordering without complex priority systems
- **Filter Fallback**: Simple filter hooks for basic plugin compatibility
- **Reference Resolution**: Yoast-style @id references for clean schema graphs
- **WordPress Core Integration**: Built-in providers for WebSite, WebPage, and Navigation
- **Organization Data**: Polaris Framework integration for business information

## Installation

```bash
# Via Composer (if published)
composer require builtnorth/wp-schema

# Or include in your WordPress project
require_once 'path/to/wp-schema/wp-schema.php';
```

## Quick Start

The package auto-initializes and provides schema via HTML `<script type="application/ld+json">` tags.

### For Plugin Developers

Register your schema providers via hook:

```php
add_action('wp_schema_register_providers', function($provider_manager) {
    $provider_manager->register(
        'my_plugin_provider',
        'MyPlugin\\Schema\\MySchemaProvider'
    );
});
```

### Simple Filter Approach

For basic schema additions:

```php
add_filter('wp_schema_pieces', function($pieces, $context, $options) {
    if ($context === 'singular' && get_post_type() === 'event') {
        $pieces[] = [
            '@type' => 'Event',
            'name' => get_the_title(),
            'startDate' => get_post_meta(get_the_ID(), 'event_date', true)
        ];
    }
    return $pieces;
}, 10, 3);
```

## Provider Interface

Create schema providers by implementing `SchemaProviderInterface`:

```php
<?php

namespace MyPlugin\Schema;

use BuiltNorth\Schema\Contracts\SchemaProviderInterface;

class MySchemaProvider implements SchemaProviderInterface
{
    public function can_provide(string $context, array $options = []): bool
    {
        // Return true if this provider can generate schema for the current context
        return $context === 'singular' && get_post_type() === 'my_post_type';
    }
    
    public function get_pieces(string $context, array $options = []): array
    {
        // Return array of schema pieces
        return [
            [
                '@type' => 'Thing',
                '@id' => get_permalink() . '#my-thing',
                'name' => get_the_title()
            ]
        ];
    }
    
    public function get_priority(): int
    {
        // Return priority for ordering (lower = higher priority)
        return 20;
    }
}
```

## Built-in Providers

### WordPress Core Providers

- **WordPressCoreProvider**: WebSite and WebPage schema from WordPress core data
- **CoreNavigationProvider**: SiteNavigationElement schema from WordPress navigation

### Polaris Integration Providers

- **PolarisOrganizationProvider**: Restaurant/Organization schema from Polaris Framework data

## Schema Output

The package outputs clean, Yoast-style schema with proper relationships:

```json
[
  {
    "@type": "Restaurant",
    "@id": "https://example.com/#organization",
    "name": "My Restaurant",
    "address": { ... }
  },
  {
    "@type": "WebSite", 
    "@id": "https://example.com/#website",
    "name": "My Site",
    "hasPart": [{"@id": "https://example.com/#navigation"}]
  },
  {
    "@type": "WebPage",
    "@id": "https://example.com/page/#webpage", 
    "name": "Page Title",
    "isPartOf": {"@id": "https://example.com/#website"},
    "breadcrumb": {"@id": "https://example.com/#breadcrumb"}
  },
  {
    "@type": "SiteNavigationElement",
    "@id": "https://example.com/#navigation",
    "name": "Primary Navigation"
  },
  {
    "@type": "BreadcrumbList",
    "@id": "https://example.com/#breadcrumb", 
    "itemListElement": [ ... ]
  }
]
```

## Contexts

The system recognizes these contexts for schema generation:

- `home` - Front page
- `singular` - Individual posts/pages  
- `archive` - Archive pages
- `search` - Search results
- `404` - 404 error pages

## Future Roadmap

### wp-schema-integrations Package (Planned)

We plan to create a separate `wp-schema-integrations` package that will provide schema for popular WordPress plugins that don't integrate directly with wp-schema:

**Planned Integrations:**
- **WooCommerce** - Product, Review, and Store schema
- **Advanced Custom Fields** - Schema from ACF field data
- **Contact Form 7** - ContactPoint schema for contact forms
- **Gravity Forms** - Form and ContactPoint schema
- **The Events Calendar** - Event schema
- **Easy Digital Downloads** - Product schema for digital products
- **WP Recipe Maker** - Recipe schema
- **Yoast SEO** - Compatibility layer

This approach keeps the core framework clean while providing comprehensive coverage for popular plugins.

**Plugin-Specific Integrations:**
- **Polaris Blocks** - Schema providers built into the polaris-blocks plugin itself
- **Custom Plugins** - Developers can build schema providers directly into their own plugins

## Requirements

- PHP 8.1+
- WordPress 6.0+

## Contributing

This package follows WordPress coding standards and uses a simple, WordPress-first approach. When contributing:

1. Keep the core framework minimal and focused
2. Use WordPress hooks and patterns
3. Follow the provider interface for extensions
4. Write clear, semantic schema output

## License

GPL-2.0-or-later

## Support

For support and questions, please open an issue on GitHub.