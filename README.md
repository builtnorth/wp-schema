# WP Schema

A comprehensive, WordPress-first schema generation framework with a clean provider-based architecture.

## Architecture

WP Schema follows a clean, modular architecture:

- **Core Framework**: Provider registration, schema assembly, and output management
- **Provider System**: Hook-based registration for extensible schema generation
- **Clean References**: Schema graphs with @id references and automatic deduplication
- **WordPress Integration**: Deep integration with WordPress core data and features
- **@graph Format**: Modern JSON-LD output using Google's recommended @graph structure

## Features

- **Simple Provider Interface**: Easy to implement schema providers
- **Comprehensive Coverage**: Built-in providers for all major WordPress contexts
- **Registration Priority System**: Predictable schema ordering with priority-based registration
- **Flexible Filtering**: Multiple filter hooks for customization at every level
- **Reference Resolution**: Clean @id references for building complex schema graphs
- **WordPress Core Integration**: Automatic schema for posts, pages, archives, media, and more
- **Type Registry**: Centralized management of schema.org types with UI support

## Installation

```bash
# Via Composer
composer require builtnorth/wp-schema
```

## Quick Start

Initialize the framework in your plugin or theme:

```php
// Initialize wp-schema
if (class_exists('BuiltNorth\WPSchema\App')) {
	add_action('init', function() {
		BuiltNorth\WPSchema\App::initialize();
	});
}
```

Once initialized, the framework automatically outputs schema via HTML `<script type="application/ld+json">` tags in the document head.

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

### Schema Type Override

Override the schema type for specific posts:

```php
add_filter('wp_schema_post_type_override', function($type, $post_id, $post_type, $post) {
    if (get_post_meta($post_id, 'page_type', true) === 'contact') {
        return 'ContactPage';
    }
    return $type;
}, 10, 4);
```

## Provider Interface

Create schema providers by implementing `SchemaProviderInterface`:

```php
<?php

namespace MyPlugin\Schema;

use BuiltNorth\WPSchema\Contracts\SchemaProviderInterface;

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

### Core Content Providers

- **OrganizationProvider**: Organization/LocalBusiness schema with support for all organization types
- **WebsiteProvider**: WebSite schema with site-wide metadata
- **ArticleProvider**: Article, BlogPosting, and NewsArticle schema for posts
- **AuthorProvider**: Person schema for post authors
- **NavigationProvider**: SiteNavigationElement schema from WordPress menus

### Page Type Providers

- **PageTypeProvider**: Specialized page types (ContactPage, AboutPage, FAQPage, etc.)
- **ArchiveProvider**: CollectionPage and ItemList for category, tag, and date archives
- **SearchResultsProvider**: SearchResultsPage with search action and results
- **MediaProvider**: ImageObject, VideoObject, and AudioObject for attachments

### Enhancement Providers

- **CommentProvider**: Comment schema added to posts and pages
- **LogoProvider**: Organization logo from WordPress site logo/custom logo
- **SiteIconProvider**: Site icon/favicon added to WebSite schema

## Schema Output

The package outputs clean schema with proper relationships using the @graph format:

```json
{
    "@context": "https://schema.org",
    "@graph": [
        {
            "@type": "Organization",
            "@id": "https://example.com/#organization",
            "name": "My Organization",
            "logo": {
                "@type": "ImageObject",
                "url": "https://example.com/logo.png"
            }
        },
        {
            "@type": "WebSite",
            "@id": "https://example.com/#website",
            "name": "My Site",
            "publisher": { "@id": "https://example.com/#organization" },
            "image": {
                "@type": "ImageObject",
                "url": "https://example.com/icon.png"
            }
        },
        {
            "@type": "Article",
            "@id": "https://example.com/post/#article",
            "headline": "Article Title",
            "author": { "@id": "https://example.com/#author-1" },
            "publisher": { "@id": "https://example.com/#organization" },
            "comment": [
                {
                    "@type": "Comment",
                    "author": { "@type": "Person", "name": "Commenter" },
                    "text": "Great article!"
                }
            ]
        },
        {
            "@type": "Person",
            "@id": "https://example.com/#author-1",
            "name": "Author Name",
            "url": "https://example.com/author/authorname/"
        }
    ]
}
```

## Contexts

The system recognizes these contexts for schema generation:

- `home` - Front page
- `singular` - Individual posts/pages
- `archive` - Archive pages (categories, tags, dates, authors, custom taxonomies)
- `search` - Search results pages
- `404` - 404 error pages
- `attachment` - Media/attachment pages

## Available Hooks

### Actions

- `wp_schema_register_providers` - Register custom providers
- `wp_schema_ready` - Fired when framework is fully initialized
- `wp_schema_before_output` - Before schema is output
- `wp_schema_after_output` - After schema is output

### Filters

- `wp_schema_pieces` - Modify final schema pieces array
- `wp_schema_graph` - Modify complete schema graph before output
- `wp_schema_piece_{type}` - Modify specific schema piece (e.g., `wp_schema_piece_article`)
- `wp_schema_post_type_override` - Override schema type for posts/pages
- `wp_schema_available_types` - Modify available schema types for UI
- `wp_schema_organization_type_mapping` - Customize organization type mappings
- `wp_schema_organization_data` - Modify organization schema data
- `wp_schema_website_data` - Modify website schema data
- `wp_schema_article_data` - Modify article schema data
- `wp_schema_archive_data` - Modify archive schema data
- `wp_schema_search_results_data` - Modify search results schema data
- `wp_schema_media_data` - Modify media schema data
- `wp_schema_page_type_data` - Modify page type schema data
- `wp_schema_context` - Override detected context
- `wp_schema_output_enabled` - Enable/disable schema output

### Schema Type Registry

Access available schema types for UI elements:

```php
$types = apply_filters('wp_schema_available_types', []);
// Returns array of ['label' => 'Article', 'value' => 'Article'] items
```

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

## Disclaimer

THIS SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

The schema markup generated by this package is not guaranteed to result in rich snippets or enhanced search results. Search engines determine rich snippet eligibility based on many factors including content quality, site authority, and their own algorithms. Always validate your schema output using official testing tools and follow search engine guidelines.

This package is currently in beta (v0.2.0-beta) and has not been fully tested across all WordPress configurations and use cases. The generated schema may not be accurate or complete for all scenarios. Users are responsible for validating and testing the schema output for their specific implementations.
