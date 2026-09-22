# WP Schema

Generates the JSON-LD `@graph` that WordPress prints in `<head>`, assembled from
independent *providers* that each contribute one or more nodes.

**This package owns graph assembly, not content decisions.** It knows how to
build a connected graph with resolvable `@id` references and emit it once per
request. What a given post type *means* — that a location is a `Hotel`, that a
coupon is an `Offer` — is decided by the plugin that owns that data, which
registers its own provider or filters an existing node. wp-schema ships sensible
defaults for core WordPress content and gets out of the way for everything else.

## Installation

```bash
composer require builtnorth/wp-schema
```

## Boot

```php
use BuiltNorth\WPSchema\Schema;

if (class_exists(Schema::class)) {
	Schema::boot();
}
```

`Schema::boot()` defers to `init`. `Schema::initialize()` runs immediately if you
already control your own timing. Output is attached to `wp_head` at priority 3
(`Services/OutputService.php:32`).

**Boot order matters.** Providers must be registered before the graph is built.
`Schema::registerProvider()` handles this for you — it registers immediately if
`wp_schema_framework_register_providers` has already fired, and defers to that
action if it hasn't (`Schema.php:36-47`), so it is safe at any point. A consumer
that calls `App::initialize()` directly instead should boot late enough that
other plugins have registered first.

```php
use BuiltNorth\WPSchema\Schema;

Schema::registerProvider('my_plugin_thing', MyPlugin\Schema\ThingProvider::class);
```

`register_provider()` returns `false` rather than throwing if the class is
missing, does not implement `SchemaProviderInterface`, or if the framework has
not initialized yet (`App.php:130-154`). A silently absent node usually means one
of those three.

## Writing a provider

```php
namespace MyPlugin\Schema;

use BuiltNorth\WPSchema\Contracts\SchemaProviderInterface;
use BuiltNorth\WPSchema\Graph\SchemaPiece;
use BuiltNorth\WPSchema\Services\SchemaIds;

class ThingProvider implements SchemaProviderInterface
{
    public function can_provide(string $context): bool
    {
        return $context === 'singular' && get_post_type() === 'my_thing';
    }

    public function get_pieces(string $context): array
    {
        $post = get_queried_object();

        $piece = new SchemaPiece(
            SchemaIds::entity_id($post, '#thing'),
            'Thing',
            [],
            'thing'
        );

        $piece->set('name', get_the_title($post));

        foreach (SchemaIds::page_links($post) as $property => $reference) {
            $piece->set($property, $reference);
        }

        return [$piece];
    }

    public function get_priority(): int
    {
        return 20;
    }
}
```

Providers are sorted by `get_priority()` ascending, so **lower runs first**
(`Services/ProviderRegistry.php:45`). Core providers use 5 for the site-wide
nodes, 15–20 for page and entity nodes, and 100 for the generic fallback.

If your provider emits the entity for a post type, opt that post type out of the
generic fallback so the graph does not carry two entities for one post:

```php
add_filter('wp_schema_framework_generic_skip_post_types', function (array $post_types): array {
    $post_types[] = 'my_thing';
    return $post_types;
});
```

This is keyed by *post type* rather than schema type deliberately — the fallback
cannot know by type, since one post type may resolve to `Hotel`, `Store`, or
`GasStation` depending on per-post settings.

## Graph shape

Every home and singular request gets a **page node** whose `@id` is the bare
permalink, no fragment. It is typed `WebPage` regardless of what the post's
schema type resolves to — the resolved type belongs to the *entity* node
(`Providers/WebPageProvider.php:11-26`).

The **entity** node uses `@id` = `{permalink}#{fragment}` and links *up* to the
page via both `isPartOf` and `mainEntityOfPage`. The page never references the
entity.

When the resolved type is a `WebPage` **subtype** (`ContactPage`, `AboutPage`,
`PrivacyPolicyPage`, `TermsOfServicePage`, `CheckoutPage`, `ProfilePage`,
`FAQPage`, `CollectionPage`, `MediaGallery`), the page node becomes multi-typed
rather than gaining a sibling: `"@type": ["WebPage","ContactPage"]`. A page that
is also a contact page is one thing with two types, not two things.
`WebPageProvider` always owns the node and appends the subtype;
`PageTypeProvider` contributes the subtype-specific properties to it through
`wp_schema_framework_piece_id_webpage` and emits nothing of its own.

**Site-wide nodes use absolute `@id`s** — `{home_url}/#organization` and
`{home_url}/#website`. This is deliberate and not redundancy: a bare
`#organization` would resolve against each page's own URL and never match the
node it points at. Always use `SchemaIds::organization_id()` and
`SchemaIds::website_id()` when referencing them rather than writing the fragment
by hand.

`SchemaIds` (`Services/SchemaIds.php`) centralizes these conventions:
`organization_id()`, `website_id()`, `webpage_id($post)`,
`home_webpage_id()`, `entity_id($post, $fragment)`, and `page_links($post)`.

### One piece per `@id`

`SchemaGraph::add_piece()` keys pieces by `@id`, so a second piece claiming an
existing `@id` **replaces** the first and triggers `_doing_it_wrong`
(`Graph/SchemaGraph.php:36-48`). Merging is deliberately not attempted — it would
require per-property rules (scalar vs. list) the graph has no way to infer.

To contribute properties to a node another provider owns, filter it:

```php
add_filter('wp_schema_framework_piece_id_organization', function ($piece, $context) {
    return $piece->set('openingHoursSpecification', $hours);
}, 10, 2);
```

### Piece names

Each piece carries a short handle alongside its `@id`, used to build the
`wp_schema_framework_piece_id_{name}` hook. It is derived by stripping the home
URL and slugifying, so both `https://example.com/#organization` and
`#organization` yield the handle `organization` — and the hook name is identical
on every site (`Graph/SchemaPiece.php:72-84`).

Pass a handle explicitly when the derived one would be unstable, such as an `@id`
containing a post ID:

```php
// Handle stays "review-summary" instead of becoming "review-4812"
new SchemaPiece("https://example.com/#review-{$post->ID}", 'Review', [], 'review-summary');
```

## Contexts

`ContextDetector` resolves exactly one context per request, in this order
(`Services/ContextDetector.php:19-38`): `home`, `attachment`, `singular`,
`archive`, `search`, `404`, `unknown`.

Two ordering consequences worth knowing: the front page matches `home` before
`singular`, so a static front page is never `singular`; and `is_archive() ||
is_home()` is tested before `is_search()`, so the blog posts index reports
`archive`.

**No schema is emitted for `404` or `unknown`**, nor in admin, feeds, robots, or
trackback requests (`Services/ContextDetector.php:43-57`). `search` does emit.

## Extending

The graph can be modified at four levels, applied in this order
(`Graph/SchemaGraph.php:118-144`, then `Services/OutputService.php:83-101`):

1. `wp_schema_framework_pieces` — the whole piece collection
2. `wp_schema_framework_piece_{type}` — one node by lowercased `@type`
3. `wp_schema_framework_piece_id_{name}` — one node by its handle
4. `wp_schema_framework_graph` / `wp_schema_framework_json_output` — the final
   array and the encoded string

> **`wp_schema_framework_pieces` passes `SchemaPiece` objects, keyed by `@id` —
> not plain arrays.** The filtered result is iterated and `get_type()` is called
> on each element, so injecting a raw array causes a fatal error. Append with
> `$pieces[$piece->get_id()] = $piece;` and return the array.

See **[docs/hooks-reference.md](docs/hooks-reference.md)** for every hook with
verified parameters, and **[docs/conflict-detection.md](docs/conflict-detection.md)**
for how the product and event providers stand down when WooCommerce or The
Events Calendar already
emits that schema.

## Schema type registry

`SchemaTypeRegistry` backs schema-type dropdowns in admin UIs. It ships 260
type entries; each has `label` and `value`, and most (but not all) also carry
`category`, `subcategory`, and sometimes `parent`
(`Services/SchemaTypeRegistry.php:19-350`). Code reading those keys must treat
them as optional.

```php
use BuiltNorth\WPSchema\Schema;

Schema::getAvailableTypes();               // all 260 entries
Schema::getCategorizedTypes();             // grouped by category → subcategory
Schema::getOrganizationTypes();            // business/place/identity types only
Schema::getCategorizedOrganizationTypes(); // grouped under display names
Schema::getContentTypes();                 // post/page-appropriate types
Schema::getPostTypeMappings();             // post type → schema type
Schema::getSchemaTypeForPostType('post');  // 'Article'; falls back to 'Article'
Schema::isValidType('Hotel');              // membership check against the registry
```

Note that `getSchemaTypeForPostType()` returns `'Article'` for *any* unmapped
post type (`Services/SchemaTypeRegistry.php:392`) — it is not a "no mapping"
signal. The `wp_schema_framework_post_type_mapping` filter used by providers
defaults to an empty string instead, which is the real "unmapped" value.

Customize the registry with `wp_schema_framework_type_registry_types`:

```php
add_filter('wp_schema_framework_type_registry_types', function (array $types): array {
    $types[] = [
        'label'       => 'Coworking Space',
        'value'       => 'CoworkingSpace',
        'category'    => 'Organization',
        'subcategory' => 'LocalBusiness',
        'parent'      => 'LocalBusiness',
    ];

    return $types;
});
```

Returning a completely different array replaces the registry outright.

## WP-CLI

Inspect the graph without an HTTP request, and without a page cache in the way.
The command emulates a front-end request through WordPress's own rewrite
resolution and builds the graph in-process, so `dump` output and the live page
cannot disagree.

```bash
wp schema check                          # home page
wp schema check /locations/acme-store/   # any path, e.g. /?s=term
wp schema check --post=5151              # by post ID
wp schema check --all                    # home, blog page, newest post of every
                                         # public post type, every CPT archive
wp schema check --all --format=json      # machine-readable
wp schema dump /                         # the exact JSON-LD wp_head would print
```

`check` reports every node and fails on any `{"@id": …}` reference — however
deeply nested — that does not resolve to a node in the same graph. It exits `1`
when any checked page has errors. `--all` skips the `attachment` post type
(`CLI/SchemaCommand.php:25`).

The path is a **positional** argument because `--url` and `--path` are WP-CLI
globals and never reach the command.

## API surface

`BuiltNorth\WPSchema\Schema` is the supported entry point — boot, provider
registration, and every type-registry getter. `App` is the underlying singleton;
reach for it only for what the facade does not expose (`get_registry()`,
`get_graph_builder()`, `get_output_service()`, `get_context_detector()`,
`get_type_registry()`, `is_initialized()`).

`SchemaGraph` and `SchemaPiece` (`inc/Graph/`) are the graph primitives.
`SchemaPiece` exposes `set()`, `get()`, `has()`, `remove()`, `merge()`,
`from_array()`, `to_array()`, `add_reference()`, `get_id()`, `get_type()`, and
`get_name()`; the mutators return `$this` and chain.

## Requirements

PHP 8.0 or later, per `composer.json`. The package declares no WordPress version
constraint, though it calls `str_contains`/`str_starts_with` and expects a
reasonably current WordPress.

## Contributing

See [CONTRIBUTING.md](docs/CONTRIBUTING.md).

## License

GPL-2.0-or-later. See [LICENSE.md](LICENSE.md).

## Disclaimer

THIS SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

The schema markup generated by this package is not guaranteed to result in rich snippets or enhanced search results. Search engines determine rich snippet eligibility based on many factors including content quality, site authority, and their own algorithms. Always validate your schema output using official testing tools and follow search engine guidelines.

This package has not been fully tested across all WordPress configurations and use cases. The generated schema may not be accurate or complete for all scenarios. Users are responsible for validating and testing the schema output for their specific implementations.
